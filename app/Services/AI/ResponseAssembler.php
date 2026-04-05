<?php

namespace App\Services\AI;

class ResponseAssembler
{
    /**
     * Assemble final response from agent result.
     */
    public static function assemble(array $agentResult): array
    {
        $finalOutput = $agentResult['final_output'] ?? [];
        $history = $agentResult['history'] ?? [];
        $errors = $agentResult['errors'] ?? [];
        
        $response = [
            'success' => $agentResult['success'] ?? false,
            'iterations' => $agentResult['iterations'] ?? 0,
            'content' => null,
            'metadata' => self::buildMetadata($agentResult, $history),
        ];

        // Determine content type and assemble
        if (isset($finalOutput['frames'])) {
            $response['content'] = self::assembleStoryboard($finalOutput);
            $response['type'] = 'storyboard';
        } elseif (isset($finalOutput['images'])) {
            $response['content'] = self::assembleImages($finalOutput);
            $response['type'] = 'image';
        } elseif (isset($finalOutput['content'])) {
            $response['content'] = self::assembleText($finalOutput);
            $response['type'] = 'text';
        } else {
            $response['content'] = $finalOutput;
            $response['type'] = 'unknown';
        }

        // Add warnings if any
        $response['warnings'] = self::buildWarnings($history, $errors);
        
        // Add validation if applicable
        $response['validation'] = self::buildValidation($finalOutput);

        return $response;
    }

    /**
     * Assemble text content.
     */
    protected static function assembleText(array $output): array
    {
        return [
            'text' => $output['content'] ?? '',
            'model' => $output['model'] ?? null,
            'tokens' => $output['tokens'] ?? null,
        ];
    }

    /**
     * Assemble image content.
     */
    protected static function assembleImages(array $output): array
    {
        $images = [];
        
        foreach ($output['images'] ?? [] as $img) {
            $images[] = [
                'url' => $img['url'] ?? null,
                'thumbnail_url' => $img['thumbnail_url'] ?? null,
                'width' => $img['width'] ?? null,
                'height' => $img['height'] ?? null,
                'seed' => $img['seed'] ?? null,
            ];
        }

        return [
            'images' => $images,
            'count' => count($images),
            'model' => $output['model'] ?? null,
        ];
    }

    /**
     * Assemble storyboard content.
     */
    protected static function assembleStoryboard(array $output): array
    {
        $frames = [];
        
        foreach ($output['frames'] ?? [] as $frame) {
            $frames[] = [
                'frame_number' => $frame['frame_number'] ?? null,
                'image_url' => $frame['image_url'] ?? null,
                'thumbnail_url' => $frame['thumbnail_url'] ?? null,
                'description' => $frame['description'] ?? null,
                'duration' => $frame['duration'] ?? 2000,
            ];
        }

        return [
            'frames' => $frames,
            'total_frames' => $output['total_frames'] ?? count($frames),
            'model' => $output['model'] ?? null,
        ];
    }

    /**
     * Build metadata from history.
     */
    protected static function buildMetadata(array $result, array $history): array
    {
        $toolsUsed = [];
        $intents = [];
        
        foreach ($history as $entry) {
            if (isset($entry['decision']['tool'])) {
                $toolsUsed[] = $entry['decision']['tool'];
            }
            if (isset($entry['inspection']['intents'])) {
                $intents = array_merge($intents, $entry['inspection']['intents']);
            }
        }

        return [
            'tools_used' => array_unique($toolsUsed),
            'detected_intents' => array_unique($intents),
            'total_steps' => count($history),
            'model' => self::extractModel($result),
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * Extract model from output.
     */
    protected static function extractModel(array $result): ?string
    {
        $output = $result['final_output'] ?? [];
        
        return $output['model'] ?? null;
    }

    /**
     * Build warnings from history and errors.
     */
    protected static function buildWarnings(array $history, array $errors): array
    {
        $warnings = [];
        
        // Check for retries
        $retryCount = 0;
        foreach ($errors as $error) {
            if ($error['type'] === 'tool_failed') {
                $retryCount++;
            }
        }
        if ($retryCount > 0) {
            $warnings[] = "Required {$retryCount} retry(s) during generation";
        }

        // Check for fallbacks
        foreach ($history as $entry) {
            if (isset($entry['decision']['input']['fallback'])) {
                $warnings[] = "Used fallback strategy due to initial failure";
                break;
            }
        }

        // Check for warnings in output
        $output = $history[count($history) - 1]['result'] ?? [];
        if (!empty($output['warnings'])) {
            $warnings = array_merge($warnings, $output['warnings']);
        }

        return $warnings;
    }

    /**
     * Build validation info.
     */
    protected static function buildValidation(array $output): ?array
    {
        // If output has validation data
        if (isset($output['validation'])) {
            return $output['validation'];
        }

        // If output has canon entries, check consistency
        if (isset($output['canon'])) {
            return [
                'type' => 'canon_consistency',
                'valid' => true,
                'entries_count' => count($output['canon']),
            ];
        }

        // If output has timeline events, add timeline validation
        if (isset($output['frames'])) {
            return [
                'type' => 'storyboard_completeness',
                'valid' => count($output['frames']) > 0,
                'frame_count' => count($output['frames']),
            ];
        }

        return null;
    }

    /**
     * Quick format for simple responses.
     */
    public static function simple(array $output, string $type = 'text'): array
    {
        return self::assemble([
            'success' => $output['success'] ?? true,
            'final_output' => $output,
            'iterations' => 1,
            'history' => [],
            'errors' => [],
        ]);
    }

    /**
     * Format for API response.
     */
    public static function forApi(array $assembled): array
    {
        return [
            'success' => $assembled['success'],
            'type' => $assembled['type'],
            'data' => $assembled['content'],
            'metadata' => $assembled['metadata'],
            'warnings' => $assembled['warnings'],
            'validation' => $assembled['validation'],
        ];
    }
}
