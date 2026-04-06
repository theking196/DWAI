<?php

namespace App\Services\DWAI;

use App\Models\ReferenceImage;
use App\Models\Project;
use App\Models\ActivityLog;
use Illuminate\Http\UploadedFile;

class ReferenceService
{
    public function upload(int $projectId, UploadedFile $file, array $data = []): ReferenceImage
    {
        $path = $file->store("references/{$projectId}");

        $ref = ReferenceImage::create([
            'user_id' => auth()->id(),
            'project_id' => $projectId,
            'title' => $data['title'] ?? $file->getClientOriginalName(),
            'description' => $data['description'] ?? null,
            'path' => $path,
            'url' => asset('storage/' . $path),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'tags' => $data['tags'] ?? [],
            'is_style_reference' => $data['is_style_reference'] ?? false,
        ]);

        ActivityLog::referenceUploaded(auth()->id(), $ref);

        if ($data['is_style_reference'] ?? false) {
            $this->addToProjectStyle($projectId, $ref);
        }

        return $ref;
    }

    public function search(int $projectId, string $query, array $filters = []): array
    {
        $qb = ReferenceImage::where('project_id', $projectId);

        if (!empty($query)) {
            $qb->where(fn($q) => $q->where('title', 'like', "%{$query}%")->orWhere('description', 'like', "%{$query}%"));
        }

        if ($filters['style_only'] ?? false) {
            $qb->where('is_style_reference', true);
        }

        return $qb->get()->toArray();
    }

    protected function addToProjectStyle(int $projectId, ReferenceImage $ref): void
    {
        $project = Project::find($projectId);
        $existing = $project->style_images ?? [];
        $existing[] = ['path' => $ref->path, 'title' => $ref->title];
        $project->update(['style_images' => $existing]);
    }
}
