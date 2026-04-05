<?php

namespace App\Http\Controllers\Api;

use App\Models\Asset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AssetController extends ApiController
{
    /**
     * List/search assets.
     */
    public function index(Request $request): JsonResponse
    {
        $params = [
            'project_id' => $request->get('project_id'),
            'keyword' => $request->get('keyword'),
            'type' => $request->get('type'),
            'types' => $request->get('types') ? explode(',', $request->get('types')) : null,
            'tag' => $request->get('tag'),
            'session_id' => $request->get('session_id') ? (int)$request->get('session_id') : null,
            'canon_entry_id' => $request->get('canon_entry_id') ? (int)$request->get('canon_entry_id') : null,
            'min_size' => $request->get('min_size') ? (int)$request->get('min_size') : null,
            'max_size' => $request->get('max_size') ? (int)$request->get('max_size') : null,
            'extension' => $request->get('extension'),
            'sort_by' => $request->get('sort_by', 'created_at'),
            'sort_dir' => $request->get('sort_dir', 'desc'),
        ];

        $assets = Asset::search($params)->paginate($request->get('per_page', 20));
        
        return $this->success($assets);
    }

    /**
     * Upload asset.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'file' => 'required|file|max:51200', // 50MB
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'type' => 'nullable|in:image,audio,video,document,model,other',
            'tags' => 'nullable|array',
            'session_id' => 'nullable|exists:sessions,id',
            'canon_entry_id' => 'nullable|exists:canon_entries,id',
            'is_primary' => 'boolean',
        ]);

        $project = \App\Models\Project::findOrFail($validated['project_id']);
        if ($project->user_id !== auth()->id()) {
            return $this->error('Unauthorized', 403);
        }

        $file = $validated['file'];
        
        // Detect type from mime if not provided
        $type = $validated['type'] ?? Asset::detectType($file->getMimeType());
        $extension = $file->getClientOriginalExtension();
        
        // Store file
        $path = $file->store('assets/' . $project->id, 'public');
        $fileName = basename($path);

        $asset = Asset::create([
            'user_id' => auth()->id(),
            'project_id' => $validated['project_id'],
            'session_id' => $validated['session_id'] ?? null,
            'canon_entry_id' => $validated['canon_entry_id'] ?? null,
            'file_name' => $fileName,
            'original_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'extension' => $extension,
            'title' => $validated['title'] ?? $file->getClientOriginalName(),
            'description' => $validated['description'] ?? null,
            'type' => $type,
            'tags' => $validated['tags'] ?? [],
            'is_primary' => $validated['is_primary'] ?? false,
        ]);

        if (!empty($validated['is_primary'])) {
            $asset->setAsPrimary();
        }

        return $this->success($asset, 'Asset uploaded', 201);
    }

    /**
     * Show asset.
     */
    public function show(int $id): JsonResponse
    {
        $asset = Asset::findOrFail($id);
        
        if ($asset->user_id !== auth()->id()) {
            return $this->error('Unauthorized', 403);
        }

        return $this->success($asset);
    }

    /**
     * Update asset metadata.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $asset = Asset::findOrFail($id);
        
        if ($asset->user_id !== auth()->id()) {
            return $this->error('Unauthorized', 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'type' => 'sometimes|in:image,audio,video,document,model,other',
            'tags' => 'nullable|array',
            'is_primary' => 'boolean',
        ]);

        $asset->update($validated);

        if (!empty($validated['is_primary'])) {
            $asset->setAsPrimary();
        }

        return $this->success($asset);
    }

    /**
     * Delete asset.
     */
    public function destroy(int $id): JsonResponse
    {
        $asset = Asset::findOrFail($id);
        
        if ($asset->user_id !== auth()->id()) {
            return $this->error('Unauthorized', 403);
        }

        // Delete file
        if ($asset->file_path && Storage::disk('public')->exists($asset->file_path)) {
            Storage::disk('public')->delete($asset->file_path);
        }

        $asset->delete();
        
        return $this->success(null, 'Asset deleted');
    }

    /**
     * Get organized assets.
     */
    public function organized(int $projectId): JsonResponse
    {
        $project = \App\Models\Project::findOrFail($projectId);
        
        if ($project->user_id !== auth()->id()) {
            return $this->error('Unauthorized', 403);
        }

        $organized = Asset::getOrganized($projectId);
        
        return $this->success($organized);
    }

    /**
     * Get project stats.
     */
    public function stats(int $projectId): JsonResponse
    {
        $project = \App\Models\Project::findOrFail($projectId);
        
        if ($project->user_id !== auth()->id()) {
            return $this->error('Unauthorized', 403');
        }

        $stats = Asset::getProjectStats($projectId);
        
        return $this->success($stats);
    }

    /**
     * Add tag to asset.
     */
    public function addTag(Request $request, int $id): JsonResponse
    {
        $asset = Asset::findOrFail($id);
        
        if ($asset->user_id !== auth()->id()) {
            return $this->error('Unauthorized', 403);
        }

        $request->validate(['tag' => 'required|string']);
        
        $asset->addTag($request->tag);
        
        return $this->success($asset);
    }

    /**
     * Get available tags for project.
     */
    public function projectTags(int $projectId): JsonResponse
    {
        $project = \App\Models\Project::findOrFail($projectId);
        
        if ($project->user_id !== auth()->id()) {
            return $this->error('Unauthorized', 403', 403);
        }

        $assets = Asset::where('project_id', $projectId)->whereNotNull('tags')->get();
        $tags = [];
        
        foreach ($assets as $asset) {
            if ($asset->tags) {
                $tags = array_merge($tags, $asset->tags);
            }
        }

        return $this->success(['tags' => array_unique($tags)]);
    }
}
