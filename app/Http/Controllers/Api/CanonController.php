<?php

namespace App\Http\Controllers\Api;

use App\Models\CanonEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CanonController extends ApiController
{
    /**
     * Get organized canon for a project.
     */
    public function organized(int $projectId): JsonResponse
    {
        $project = \App\Models\Project::findOrFail($projectId);
        
        if ($project->user_id !== auth()->id()) {
            return $this->error('Unauthorized', 403);
        }

        $organized = CanonEntry::getOrganized($projectId);
        
        return $this->success($organized);
    }

    /**
     * Get canon by type.
     */
    public function byType(int $projectId, string $type): JsonResponse
    {
        $project = \App\Models\Project::findOrFail($projectId);
        
        if ($project->user_id !== auth()->id()) {
            return $this->error('Unauthorized', 403);
        }

        $entries = CanonEntry::where('project_id', $projectId)
            ->where('type', $type)
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->success($entries);
    }

    /**
     * Search canon.
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->get('q', '');
        $projectId = $request->get('project_id');
        
        $builder = CanonEntry::search($query);
        
        if ($projectId) {
            $builder->where('project_id', $projectId);
        }

        $results = $builder->with('project:id,name')->paginate(20);
        
        return $this->success($results);
    }

    /**
     * Get by importance.
     */
    public function byImportance(int $projectId, string $importance): JsonResponse
    {
        $project = \App\Models\Project::findOrFail($projectId);
        
        if ($project->user_id !== auth()->id()) {
            return $this->error('Unauthorized', 403);
        }

        $entries = CanonEntry::where('project_id', $projectId)
            ->where('importance', $importance)
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->success($entries);
    }
}
