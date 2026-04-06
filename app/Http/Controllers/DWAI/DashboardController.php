<?php

namespace App\Http\Controllers\DWAI;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Session;
use App\Models\CanonEntry;
use App\Models\AIOutput;
use App\Models\ActivityLog;
use App\Services\DWAI\UnifiedDataService;

class DashboardController extends Controller
{
    public function index()
    {
        $userId = auth()->id();
        
        $stats = [
            'projects' => Project::where('user_id', $userId)->where('status', '!=', 'archived')->count(),
            'sessions' => Session::where('user_id', $userId)->count(),
            'active_sessions' => Session::where('user_id', $userId)->where('status', 'active')->count(),
            'canon' => CanonEntry::where('user_id', $userId)->count(),
            'outputs' => AIOutput::whereHas('session', fn($q) => $q->where('user_id', $userId))->count(),
        ];
        
        $recentProjects = Project::where('user_id', $userId)
            ->where('status', '!=', 'archived')
            ->orderBy('updated_at', 'desc')
            ->limit(6)
            ->get();
        
        $recentActivity = ActivityLog::recent($userId, 10);
        
        $recentSessions = Session::where('user_id', $userId)
            ->where('status', 'active')
            ->with('project')
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();
        
        return view('dwai.dashboard', compact('stats', 'recentProjects', 'recentActivity', 'recentSessions'));
    }

    public function stats()
    {
        $userId = auth()->id();
        
        return response()->json([
            'projects' => Project::where('user_id', $userId)->count(),
            'sessions' => Session::where('user_id', $userId)->count(),
            'active_sessions' => Session::where('user_id', $userId)->where('status', 'active')->count(),
            'canon' => CanonEntry::where('user_id', $userId)->count(),
        ]);
    }
}
