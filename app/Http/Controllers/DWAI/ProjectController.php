<?php

namespace App\Http\Controllers\DWAI;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index()
    {
        return Project::where('user_id', auth()->id())->get();
    }

    public function store(Request $request)
    {
        $project = Project::create([
            'user_id' => auth()->id(),
            'name' => $request->name,
            'description' => $request->description,
            'type' => $request->type ?? 'story',
        ]);
        
        ActivityLog::projectCreated(auth()->id(), $project);
        
        return response()->json($project);
    }

    public function show(int $id)
    {
        $project = Project::where('user_id', auth()->id())->findOrFail($id);
        return response()->json($project->toArray());
    }

    public function update(Request $request, int $id)
    {
        $project = Project::where('user_id', auth()->id())->findOrFail($id);
        $project->update($request->only(['name', 'description', 'type', 'visual_style_description']));
        return response()->json($project);
    }

    public function destroy(int $id)
    {
        $project = Project::where('user_id', auth()->id())->findOrFail($id);
        $project->delete();
        return response()->json(['deleted' => true]);
    }
}
