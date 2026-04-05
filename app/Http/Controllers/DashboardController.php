<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Session;
use App\Models\CanonEntry;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Show the dashboard with latest data.
     */
    public function show(): View
    {
        $stats = [
            'projects' => Project::count(),
            'sessions' => Session::count(),
            'canon' => CanonEntry::count(),
        ];
        
        $recentProjects = Project::orderBy('updated_at', 'desc')
            ->limit(6)
            ->get();
        
        $recentSessions = Session::with('project')
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();
        
        return view('pages.dashboard', compact(
            'stats',
            'recentProjects', 
            'recentSessions'
        ));
    }
}
