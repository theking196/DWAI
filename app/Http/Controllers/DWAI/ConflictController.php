<?php

namespace App\Http\Controllers\DWAI;

use App\Http\Controllers\Controller;
use App\Models\Conflict;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ConflictController extends Controller
{
    public function index(int $projectId)
    {
        return Conflict::active($projectId);
    }

    public function resolve(Request $request, int $id)
    {
        $conflict = Conflict::findOrFail($id);
        $conflict->resolve($request->notes);
        
        ActivityLog::conflictResolved(auth()->id(), $conflict);
        
        return response()->json(['resolved' => true]);
    }

    public function ignore(int $id)
    {
        $conflict = Conflict::findOrFail($id);
        $conflict->ignore();
        return response()->json(['ignored' => true]);
    }

    public function scan(int $projectId)
    {
        $count = Conflict::syncFromDetection($projectId);
        return response()->json(['conflicts_detected' => $count]);
    }
}
