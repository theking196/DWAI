<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ReferenceImage;
use Illuminate\Http\JsonResponse;

class ReferenceController extends Controller
{
    /**
     * Get references for a project (for AI generation).
     */
    public function forProject(int $projectId): JsonResponse
    {
        $project = Project::findOrFail($projectId);
        
        $references = $project->referenceImages()
            ->select('id', 'title', 'path', 'type')
            ->get()
            ->map(fn($ref) => [
                'id' => $ref->id,
                'title' => $ref->title,
                'path' => $ref->path,
                'url' => \Storage::url($ref->path),
                'type' => $ref->type,
            ]);
        
        return response()->json([
            'success' => true,
            'references' => $references,
            'count' => $references->count(),
        ]);
    }
}
