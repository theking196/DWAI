<?php

namespace App\Services;

use App\Models\Project;
use App\Models\CanonEntry;
use App\Models\ReferenceImage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class ImportService
{
    /**
     * Import file as canon entry.
     */
    public function importAsCanon(
        int $projectId,
        UploadedFile $file,
        array $options = []
    ): array {
        $project = Project::findOrFail($projectId);
        
        $content = $this->extractContent($file);
        $title = $options['title'] ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        
        $canon = CanonEntry::create([
            'user_id' => auth()->id(),
            'project_id' => $projectId,
            'title' => $title,
            'type' => $options['type'] ?? 'note',
            'content' => $content,
            'tags' => $options['tags'] ?? ['imported'],
            'importance' => $options['importance'] ?? 'minor',
            'metadata' => [
                'imported_from' => $file->getClientOriginalName(),
                'imported_at' => now()->toISOString(),
                'original_type' => $file->getClientMimeType(),
            ],
        ]);

        return [
            'id' => $canon->id,
            'type' => 'canon',
            'title' => $canon->title,
        ];
    }

    /**
     * Import file as reference image.
     */
    public function importAsReference(
        int $projectId,
        UploadedFile $file,
        array $options = []
    ): array {
        $path = $file->store("references/{$projectId}");
        
        $ref = ReferenceImage::create([
            'user_id' => auth()->id(),
            'project_id' => $projectId,
            'title' => $options['title'] ?? $file->getClientOriginalName(),
            'description' => $options['description'] ?? null,
            'path' => $path,
            'url' => asset('storage/' . $path),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'tags' => $options['tags'] ?? ['imported'],
            'is_style_reference' => $options['is_style_reference'] ?? false,
            'metadata' => [
                'imported_at' => now()->toISOString(),
            ],
        ]);

        return [
            'id' => $ref->id,
            'type' => 'reference',
            'title' => $ref->title,
            'url' => $ref->url,
        ];
    }

    /**
     * Import file - auto-detect type.
     */
    public function importAuto(
        int $projectId,
        UploadedFile $file,
        array $options = []
    ): array {
        $mimeType = $file->getMimeType();
        
        // Text types -> canon
        if (str_starts_with($mimeType, 'text/') 
            || in_array($file->getClientOriginalExtension(), ['txt', 'md', 'markdown'])) {
            return $this->importAsCanon($projectId, $file, $options);
        }
        
        // Image types -> reference
        if (str_starts_with($mimeType, 'image/')) {
            return $this->importAsReference($projectId, $file, $options);
        }
        
        // Default to canon for unknown
        return $this->importAsCanon($projectId, $file, $options);
    }

    /**
     * Extract text content from file.
     */
    protected function extractContent(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        
        return match(strtolower($extension)) {
            'txt', 'md', 'markdown' => file_get_contents($file->getRealPath()),
            default => file_get_contents($file->getRealPath()),
        };
    }

    /**
     * Batch import multiple files.
     */
    public function batchImport(
        int $projectId,
        array $files,
        string $mode = 'auto'
    ): array {
        $results = ['imported' => [], 'errors' => []];
        
        foreach ($files as $file) {
            try {
                $result = match($mode) {
                    'canon' => $this->importAsCanon($projectId, $file),
                    'reference' => $this->importAsReference($projectId, $file),
                    default => $this->importAuto($projectId, $file),
                };
                $results['imported'][] = $result;
            } catch (\Exception $e) {
                $results['errors'][] = [
                    'file' => $file->getClientOriginalName(),
                    'error' => $e->getMessage(),
                ];
            }
        }
        
        return $results;
    }
}
