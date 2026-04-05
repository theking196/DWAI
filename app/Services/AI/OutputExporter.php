<?php

namespace App\Services\AI;

use App\Models\AIOutput;
use Illuminate\Support\Facades\Storage;

class OutputExporter
{
    /**
     * Export output in specified format.
     */
    public function export(AIOutput $output, string $format): array
    {
        return match($format) {
            'txt' => $this->exportAsText($output),
            'md' => $this->exportAsMarkdown($output),
            'json' => $this->exportAsJson($output),
            'image' => $this->exportImage($output),
            'storyboard_pdf' => $this->exportStoryboard($output),
            default => ['success' => false, 'error' => 'Unknown format'],
        };
    }

    /**
     * Export as plain text.
     */
    protected function exportAsText(AIOutput $output): array
    {
        $content = $output->result ?? '';
        
        if (is_array($content)) {
            $content = json_encode($content, JSON_PRETTY_PRINT);
        }

        return [
            'success' => true,
            'content' => strip_tags($content),
            'mime' => 'text/plain',
            'extension' => 'txt',
        ];
    }

    /**
     * Export as markdown.
     */
    protected function exportAsMarkdown(AIOutput $output): array
    {
        $content = $output->result ?? '';
        
        if (is_array($content)) {
            $content = json_encode($content, JSON_PRETTY_PRINT);
        }

        // Add metadata header
        $markdown = "---\n";
        $markdown .= "Title: AI Output #{$output->id}\n";
        $markdown .= "Type: {$output->type}\n";
        $markdown .= "Model: {$output->model}\n";
        $markdown .= "Created: {$output->created_at}\n";
        $markdown .= "---\n\n";
        $markdown .= $content;

        return [
            'success' => true,
            'content' => $markdown,
            'mime' => 'text/markdown',
            'extension' => 'md',
        ];
    }

    /**
     * Export as JSON.
     */
    protected function exportAsJson(AIOutput $output): array
    {
        $data = [
            'id' => $output->id,
            'type' => $output->type,
            'prompt' => $output->prompt,
            'result' => $output->result,
            'model' => $output->model,
            'metadata' => $output->metadata,
            'created_at' => $output->created_at->toISOString(),
        ];

        return [
            'success' => true,
            'content' => json_encode($data, JSON_PRETTY_PRINT),
            'mime' => 'application/json',
            'extension' => 'json',
        ];
    }

    /**
     * Export image (download).
     */
    protected function exportImage(AIOutput $output): array
    {
        $result = $output->result;
        
        if (is_array($result) && isset($result[0]['url'])) {
            $url = $result[0]['url'];
            
            return [
                'success' => true,
                'url' => $url,
                'filename' => "output-{$output->id}-image.png",
                'mime' => 'image/png',
            ];
        }

        return ['success' => false, 'error' => 'No image to export'];
    }

    /**
     * Export storyboard as document.
     */
    protected function exportStoryboard(AIOutput $output): array
    {
        $result = $output->result;
        
        if (!is_array($result) || !isset($result['frames'])) {
            return ['success' => false, 'error' => 'No storyboard to export'];
        }

        // Create markdown document
        $doc = "# Storyboard\n\n";
        $doc .= "Generated: {$output->created_at}\n";
        $doc .= "Model: {$output->model}\n\n";
        $doc .= "---\n\n";

        foreach ($result['frames'] as $frame) {
            $doc .= "## Frame {$frame['frame_number']}\n\n";
            $doc .= "**Description:** {$frame['description']}\n\n";
            if (isset($frame['image_url'])) {
                $doc .= "![Frame {$frame['frame_number']}]({$frame['image_url']})\n\n";
            }
            $doc .= "---\n\n";
        }

        return [
            'success' => true,
            'content' => $doc,
            'mime' => 'text/markdown',
            'extension' => 'md',
        ];
    }

    /**
     * Save export to file.
     */
    public function exportToFile(AIOutput $output, string $format): ?string
    {
        $export = $this->export($output, $format);
        
        if (!$export['success']) {
            return null;
        }

        $filename = "output-{$output->id}-{$output->type}.{$export['extension']}";
        $path = "exports/{$output->session->project_id}/{$filename}";
        
        Storage::disk('local')->put($path, $export['content']);
        
        return $path;
    }
}
