<?php

namespace App\Http\Controllers\DWAI;

use App\Http\Controllers\Controller;
use App\Models\Session;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    public function index(int $projectId)
    {
        return Session::where('project_id', $projectId)
            ->where('user_id', auth()->id())
            ->get();
    }

    public function store(Request $request, int $projectId)
    {
        $session = Session::create([
            'user_id' => auth()->id(),
            'project_id' => $projectId,
            'name' => $request->name,
            'description' => $request->description,
            'type' => $request->type ?? 'writing',
            'status' => 'active',
        ]);
        
        ActivityLog::sessionStarted(auth()->id(), $session);
        
        return response()->json($session);
    }

    public function show(int $id)
    {
        $session = Session::where('user_id', auth()->id())->findOrFail($id);
        return response()->json($session->toArray());
    }

    public function update(Request $request, int $id)
    {
        $session = Session::where('user_id', auth()->id())->findOrFail($id);
        $session->update($request->only(['name', 'description', 'notes', 'temp_notes', 'draft_text']));
        return response()->json($session);
    }

    public function destroy(int $id)
    {
        $session = Session::where('user_id', auth()->id())->findOrFail($id);
        $session->update(['status' => 'archived']);
        return response()->json(['archived' => true]);
    }
}
