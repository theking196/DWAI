<?php

namespace App\Http\Controllers\Api;

use App\Models\ReferenceImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReferenceImageController extends ApiController
{
    /**
     * List reference images for an entity.
     */
    public function index(Request $request): JsonResponse
    {
        $entityType = $request->get('entity_type');
        $entityId = $request->get('entity_id');

        if (!$entityType || !$entityId) {
            return $this->error('entity_type and entity_id required');
        }

        $images = ReferenceImage::getForEntity($entityType, $entityId);
        
        return $this->success($images);
    }

    /**
     * Upload reference image.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'image' => 'required|image|max:10240', // 10MB
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'session_id' => 'nullable|exists:sessions,id',
            'canon_entry_id' => 'nullable|exists:canon_entries,id',
            'style_group_id' => 'nullable|integer',
            'is_primary' => 'boolean',
        ]);

        $project = \App\Models\Project::findOrFail($validated['project_id']);
        if ($project->user_id !== auth()->id()) {
            return $this->error('Unauthorized', 403);
        }

        // Store file
        $file = $validated['image'];
        $path = $file->store('references/' . $project->id, 'public');

        $image = ReferenceImage::create([
            'user_id' => auth()->id(),
            'project_id' => $validated['project_id'],
            'session_id' => $validated['session_id'] ?? null,
            'canon_entry_id' => $validated['canon_entry_id'] ?? null,
            'style_group_id' => $validated['style_group_id'] ?? null,
            'title' => $validated['title'] ?? $file->getClientOriginalName(),
            'description' => $validated['description'] ?? null,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'is_primary' => $validated['is_primary'] ?? false,
        ]);

        return $this->success($image, 'Image uploaded', 201);
    }

    /**
     * Update reference image.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $image = ReferenceImage::findOrFail($id);
        
        if ($image->user_id !== auth()->id()) {
            return $this->error('Unauthorized', 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'is_primary' => 'boolean',
        ]);

        $image->update($validated);

        if (!empty($validated['is_primary'])) {
            $image->setAsPrimary();
        }

        return $this->success($image);
    }

    /**
     * Set as primary.
     */
    public function setPrimary(int $id): JsonResponse
    {
        $image = ReferenceImage::findOrFail($id);
        
        if ($image->user_id !== auth()->id()) {
            return $this->error('Unauthorized', 403);
        }

        $image->setAsPrimary();
        
        return $this->success($image, 'Set as primary');
    }

    /**
     * Delete reference image.
     */
    public function destroy(int $id): JsonResponse
    {
        $image = ReferenceImage::findOrFail($id);
        
        if ($image->user_id !== auth()->id()) {
            return $this->error('Unauthorized', 403);
        }

        // Delete file
        if ($image->file_path && Storage::disk('public')->exists($image->file_path)) {
            Storage::disk('public')->delete($image->file_path);
        }

        $image->delete();
        
        return $this->success(null, 'Image deleted');
    }

    /**
     * Get count by project.
     */
    public function countByProject(int $projectId): JsonResponse
    {
        $project = \App\Models\Project::findOrFail($projectId);
        
        if ($project->user_id !== auth()->id()) {
            return $this->error('Unauthorized', 403);
        }

        $count = ReferenceImage::forProject($projectId)->count();
        
        return $this->success(['count' => $count]);
    }
}
