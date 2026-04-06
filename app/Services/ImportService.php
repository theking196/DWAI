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

    // ============================================================
    // Bulk Reference Image Import
    // ============================================================

    /**
     * Bulk import reference images with grouping.
     */
    public function bulkImportReferences(
        int $projectId,
        array $files,
        array $options = []
    ): array {
        $targetType = $options['target_type'] ?? 'project'; // project, session, style
        $targetId = $options['target_id'] ?? $projectId;
        $tags = $options['tags'] ?? ['imported'];
        $isStyleRef = $options['is_style_reference'] ?? false;

        $results = ['imported' => [], 'errors' => []];

        foreach ($files as $file) {
            try {
                $path = $file->store("references/{$projectId}");
                
                $ref = ReferenceImage::create([
                    'user_id' => auth()->id(),
                    'project_id' => $projectId,
                    'title' => $options['title_prefix'] 
                        ? $options['title_prefix'] . ' ' . count($results['imported'] + 1)
                        : $file->getClientOriginalName(),
                    'description' => $options['description'] ?? null,
                    'path' => $path,
                    'url' => asset('storage/' . $path),
                    'file_size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                    'tags' => $tags,
                    'is_style_reference' => $isStyleRef,
                    'metadata' => [
                        'target_type' => $targetType,
                        'target_id' => $targetId,
                        'imported_at' => now()->toISOString(),
                    ],
                ]);

                $results['imported'][] = [
                    'id' => $ref->id,
                    'title' => $ref->title,
                    'url' => $ref->url,
                    'is_style_reference' => $ref->is_style_reference,
                ];
            } catch (\Exception $e) {
                $results['errors'][] = [
                    'file' => $file->getClientOriginalName(),
                    'error' => $e->getMessage(),
                ];
            }
        }

        // If style reference, add to project style
        if ($isStyleRef && !empty($results['imported'])) {
            $project = Project::find($projectId);
            $existing = $project->style_images ?? [];
            
            foreach ($results['imported'] as $img) {
                $existing[] = [
                    'path' => ReferenceImage::find($img['id'])->path,
                    'title' => $img['title'],
                ];
            }
            
            $project->update(['style_images' => $existing]);
        }

        return $results;
    }

    /**
     * Import from directory.
     */
    public function importFromDirectory(
        int $projectId,
        string $directory,
        array $options = []
    ): array {
        if (!Storage::disk('local')->exists($directory)) {
            return ['imported' => [], 'errors' => ['Directory not found']];
        }

        $files = [];
        $contents = Storage::disk('local')->files($directory);
        
        foreach ($contents as $file) {
            if (in_array(pathinfo($file, PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $files[] = $file;
            }
        }

        // Would need UploadedFile handling for directory imports
        // This is a placeholder for the method signature
        return ['files_found' => count($files), 'directory' => $directory];
    }

    // ============================================================
    // Project Package Import (.dwai files)
    // ============================================================

    /**
     * Import a .dwai project package.
     */
    public function importProjectPackage(
        UploadedFile $file,
        array $options = []
    ): array {
        $content = file_get_contents($file->getRealPath());
        $data = json_decode($content, true);
        
        if (!$data || !isset($data['metadata']) || $data['metadata']['type'] !== 'dwai_project') {
            return ['success' => false, 'error' => 'Invalid project package'];
        }

        $merge = $options['merge'] ?? false;
        $importRefs = $options['import_references'] ?? true;
        $importCanon = $options['import_canon'] ?? true;
        $importSessions = $options['import_sessions'] ?? true;

        $projectId = null;
        $results = ['project' => null, 'sessions' => [], 'canon' => [], 'references' => []];

        // Import project
        if ($merge && isset($data['project']['name'])) {
            // Find existing or create
            $project = Project::where('name', $data['project']['name'])->first();
            if ($project) {
                $projectId = $project->id;
            }
        }
        
        if (!$projectId) {
            $project = Project::create([
                'user_id' => auth()->id(),
                'name' => $data['project']['name'],
                'description' => $data['project']['description'] ?? null,
                'type' => $data['project']['type'] ?? 'story',
                'visual_style_image' => $data['project']['visual_style_image'] ?? null,
                'visual_style_description' => $data['project']['visual_style_description'] ?? null,
                'style_images' => $data['project']['style_images'] ?? [],
            ]);
            $projectId = $project->id;
        }
        
        $results['project'] = ['id' => $projectId, 'name' => $data['project']['name']];

        // Import canon
        if ($importCanon && !empty($data['canon'])) {
            foreach ($data['canon'] as $c) {
                $canon = CanonEntry::create([
                    'user_id' => auth()->id(),
                    'project_id' => $projectId,
                    'title' => $c['title'],
                    'type' => $c['type'] ?? 'note',
                    'content' => $c['content'] ?? '',
                    'tags' => $c['tags'] ?? [],
                    'importance' => $c['importance'] ?? 'minor',
                ]);
                $results['canon'][] = ['id' => $canon->id, 'title' => $canon->title];
            }
        }

        // Import references
        if ($importRefs && !empty($data['references'])) {
            foreach ($data['references'] as $r) {
                $ref = ReferenceImage::create([
                    'user_id' => auth()->id(),
                    'project_id' => $projectId,
                    'title' => $r['title'],
                    'description' => $r['description'] ?? null,
                    'url' => $r['url'] ?? null,
                    'tags' => $r['tags'] ?? [],
                    'is_style_reference' => $r['is_style_reference'] ?? false,
                ]);
                $results['references'][] = ['id' => $ref->id, 'title' => $ref->title];
            }
        }

        // Import sessions
        if ($importSessions && !empty($data['sessions'])) {
            foreach ($data['sessions'] as $s) {
                $session = Session::create([
                    'user_id' => auth()->id(),
                    'project_id' => $projectId,
                    'name' => $s['name'],
                    'description' => $s['description'] ?? null,
                    'notes' => $s['notes'] ?? null,
                    'draft_text' => $s['draft_text'] ?? null,
                    'status' => $s['status'] ?? 'active',
                    'type' => $s['type'] ?? 'writing',
                ]);
                $results['sessions'][] = ['id' => $session->id, 'name' => $session->name];
            }
        }

        return [
            'success' => true,
            'project_id' => $projectId,
            'imported' => $results,
        ];
    }

    /**
     * Import session package.
     */
    public function importSessionPackage(
        int $projectId,
        UploadedFile $file,
        array $options = []
    ): array {
        $content = file_get_contents($file->getRealPath());
        $data = json_decode($content, true);
        
        if (!$data || !isset($data['metadata']) || $data['metadata']['type'] !== 'dwai_session') {
            return ['success' => false, 'error' => 'Invalid session package'];
        }

        // Create session
        $session = Session::create([
            'user_id' => auth()->id(),
            'project_id' => $projectId,
            'name' => $data['session']['name'],
            'description' => $data['session']['description'] ?? null,
            'notes' => $data['session']['notes'] ?? null,
            'draft_text' => $data['session']['draft_text'] ?? null,
            'status' => 'active',
            'type' => $data['session']['type'] ?? 'writing',
        ]);

        $results = ['session' => ['id' => $session->id, 'name' => $session->name]];

        // Import outputs
        if (!empty($data['outputs'])) {
            foreach ($data['outputs'] as $o) {
                AIOutput::create([
                    'session_id' => $session->id,
                    'prompt' => $o['prompt'],
                    'result' => $o['result'],
                    'type' => $o['type'],
                    'model' => $o['model'],
                    'status' => 'completed',
                ]);
            }
        }

        return [
            'success' => true,
            'session_id' => $session->id,
            'imported' => $results,
        ];
    }
