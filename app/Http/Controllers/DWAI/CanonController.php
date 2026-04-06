<?php

namespace App\Http\Controllers\DWAI;

use App\Http\Controllers\Controller;
use App\Models\CanonEntry;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class CanonController extends Controller
{
    public function index(int $projectId)
    {
        return CanonEntry::where('project_id', $projectId)
            ->where('user_id', auth()->id())
            ->orderBy('importance')
            ->get();
    }

    public function store(Request $request, int $projectId)
    {
        $canon = CanonEntry::create([
            'user_id' => auth()->id(),
            'project_id' => $projectId,
            'title' => $request->title,
            'type' => $request->type ?? 'note',
            'content' => $request->content ?? '',
            'tags' => $request->tags ?? [],
            'importance' => $request->importance ?? 'minor',
        ]);
        
        ActivityLog::canonEdited(auth()->id(), $canon);
        
        return response()->json($canon);
    }

    public function show(int $id)
    {
        $canon = CanonEntry::where('user_id', auth()->id())->findOrFail($id);
        return response()->json($canon);
    }

    public function update(Request $request, int $id)
    {
        $canon = CanonEntry::where('user_id', auth()->id())->findOrFail($id);
        $canon->update($request->only(['title', 'type', 'content', 'tags', 'importance']));
        
        ActivityLog::canonEdited(auth()->id(), $canon);
        
        return response()->json($canon);
    }

    public function destroy(int $id)
    {
        $canon = CanonEntry::where('user_id', auth()->id())->findOrFail($id);
        $canon->delete();
        return response()->json(['deleted' => true]);
    }
}
