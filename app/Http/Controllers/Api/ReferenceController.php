<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ReferenceImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReferenceController extends Controller
{
    /**
     * Get references for a project (for AI generation).
     */
    public function forProject(int $projectId): JsonResponse
    {
        $project = Project::findOrFail($projectId);
        
        // Get primary reference
        $primary = $project->referenceImages()
            ->where('is_primary', true)
            ->select('id', 'title', 'path', 'type', 'is_primary')
            ->first();
        
        // Get all references
        $references = $project->referenceImages()
            ->select('id', 'title', 'path', 'type', 'is_primary')
            ->get()
            ->map(fn($ref) => [
                'id' => $ref->id,
                'title' => $ref->title,
                'path' => $ref->path,
                'url' => Storage::url($ref->path),
                'type' => $ref->type,
                'is_primary' => $ref->is_primary,
            ]);
        
        return response()->json([
            'success' => true,
            'primary' => $primary ? [
                'id' => $primary->id,
                'title' => $primary->title,
                'url' => Storage::url($primary->path),
            ] : null,
            'references' => $references,
            'count' => $references->count(),
        ]);
    }
    
    /**
     * Set a reference image as primary.
     */
    public function setPrimary(Request $request, int $projectId): JsonResponse
    {
        $project = Project::findOrFail($projectId);
        
        $validated = $request->validate([
            'reference_id' => 'required|exists:reference_images,id',
        ]);
        
        // Remove primary from all other references
        $project->referenceImages()->update(['is_primary' => false]);
        
        // Set new primary
        $reference = $project->referenceImages()->findOrFail($validated['reference_id']);
        $reference->update(['is_primary' => true]);
        
        return response()->json([
            'success' => true,
            'message' => 'Primary reference updated',
            'primary' => [
                'id' => $reference->id,
                'title' => $reference->title,
                'url' => Storage::url($reference->path),
            ],
        ]);
    }
}
