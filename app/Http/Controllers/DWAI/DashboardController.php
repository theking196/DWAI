<?php

namespace App\Http\Controllers\DWAI;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Session;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $userId = Auth::id();
        
        $stats = [
            'projects' => Project::where('user_id', $userId)->count(),
            'active_sessions' => Session::where('user_id', $userId)->where('status', 'active')->count(),
            'recent_activity' => ActivityLog::recent($userId, 10),
        ];
        
        return view('dwai.dashboard', $stats);
    }

    public function stats()
    {
        $userId = Auth::id();
        
        return response()->json([
            'projects' => Project::where('user_id', $userId)->count(),
            'sessions' => Session::where('user_id', $userId)->count(),
            'active_sessions' => Session::where('user_id', $userId)->where('status', 'active')->count(),
        ]);
    }
}
