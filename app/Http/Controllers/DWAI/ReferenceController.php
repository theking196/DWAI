<?php

namespace App\Http\Controllers\DWAI;

use App\Http\Controllers\Controller;
use App\Models\ReferenceImage;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ReferenceController extends Controller
{
    public function index(int $projectId)
    {
        return ReferenceImage::where('project_id', $projectId)
            ->where('user_id', auth()->id())
            ->get();
    }

    public function store(Request $request, int $projectId)
    {
        $path = $request->file('image')->store("references/{$projectId}");
        
        $ref = ReferenceImage::create([
            'user_id' => auth()->id(),
            'project_id' => $projectId,
            'title' => $request->title ?? $request->file('image')->getClientOriginalName(),
            'description' => $request->description,
            'path' => $path,
            'url' => asset('storage/' . $path),
            'tags' => $request->tags ?? [],
            'is_style_reference' => $request->is_style_reference ?? false,
        ]);
        
        ActivityLog::referenceUploaded(auth()->id(), $ref);
        
        return response()->json($ref);
    }

    public function destroy(int $id)
    {
        $ref = ReferenceImage::where('user_id', auth()->id())->findOrFail($id);
        $ref->delete();
        return response()->json(['deleted' => true]);
    }
}
