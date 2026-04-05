<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ReferenceImage;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    /**
     * Upload project visual style image.
     */
    public function projectStyle(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'image' => 'required|image|mimes:jpg,jpeg,png,gif,webp|max:10240',
        ]);
        
        $project = Project::findOrFail($validated['project_id']);
        
        $file = $validated['image'];
        $filename = Str::slug($project->name) . '-style-' . time() . '.' . $file->getClientOriginalExtension();
        
        $path = $file->storeAs('projects', $filename, 'public');
        
        $project->update(['visual_style_image' => $path]);
        
        return response()->json([
            'success' => true,
            'path' => $path,
            'url' => Storage::url($path),
        ]);
    }

    /**
     * Upload reference image for a project.
     */
    public function reference(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'image' => 'required|image|mimes:jpg,jpeg,png,gif,webp|max:10240',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'type' => 'nullable|string|max:50',
            'is_primary' => 'nullable|boolean',|max:50',
        ]);
        
        $project = Project::findOrFail($validated['project_id']);
        
        $file = $validated['image'];
        $title = $validated['title'] ?? $file->getClientOriginalName();
        $filename = Str::slug($title) . '-' . time() . '.' . $file->getClientOriginalExtension();
        
        $path = $file->storeAs('references', $filename, 'public');
        
        $reference = ReferenceImage::create([
            'project_id' => $project->id,
            'title' => $title,
            'description' => $validated['description'] ?? null,
            'path' => $path,
            'type' => $validated['type'] ?? 'custom',
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ]);
        
        return response()->json([
            'success' => true,
            'reference' => [
                'id' => $reference->id,
                'title' => $reference->title,
                'path' => $reference->path,
                'url' => Storage::url($reference->path),
            ],
        ]);
    }

    /**
     * Delete a reference image.
     */
    public function deleteReference(int $id): JsonResponse
    {
        $reference = ReferenceImage::findOrFail($id);
        
        // Delete file from storage
        if (Storage::disk('public')->exists($reference->path)) {
            Storage::disk('public')->delete($reference->path);
        }
        
        $reference->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Reference deleted',
        ]);
    }

    /**
     * Upload session AI output (text or image).
     */
    public function sessionOutput(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => 'required|exists:sessions,id',
            'result' => 'required',
            'type' => 'required|string|'type' => 'nullable|string|max:50',
            'is_primary' => 'nullable|boolean',
            'model' => 'nullable|string|'type' => 'nullable|string|max:50',
            'is_primary' => 'nullable|boolean',
        ]);
        
        $data = [
            'session_id' => $validated['session_id'],
            'prompt' => $validated['result'],
            'result' => $validated['result'],
            'type' => $validated['type'],
            'model' => $validated['model'] ?? 'gpt-4',
        ];
        
        // If type is image and result is a base64 or uploaded file
        if ($validated['type'] === 'image' && $request->hasFile('result_file')) {
            $file = $request->file('result_file');
            $filename = 'output-' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('outputs', $filename, 'public');
            $data['result'] = $path;
            $data['metadata'] = [
                'url' => Storage::url($path),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ];
        }
        
        $output = \App\Models\AIOutput::create($data);
        
        return response()->json([
            'success' => true,
            'output' => $output,
        ]);
    }
}