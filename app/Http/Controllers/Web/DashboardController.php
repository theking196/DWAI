<?php

namespace App\Http\Controllers\Web;

use App\Models\Project;
use App\Models\Session;
use App\Models\CanonEntry;
use App\Models\ActivityLog;
use Illuminate\View\View;

class DashboardController extends WebController
{
    /**
     * Show the dashboard with project summaries.
     */
    public function show(): View
    {
        $user = auth()->user();

        // Get stats
        $stats = [
            'total_projects' => $user->projects()->count(),
            'active_projects' => $user->projects()->active()->count(),
            'archived_projects' => $user->projects()->archived()->count(),
            'total_sessions' => Session::whereHas('project', fn($q) => $q->where('user_id', $user->id))->count(),
            'total_canon' => CanonEntry::whereHas('project', fn($q) => $q->where('user_id', $user->id))->count(),
        ];

        // Get recent projects with summaries
        $recentProjects = $user->projects()
            ->withCount(['sessions', 'canonEntries', 'referenceImages'])
            ->orderBy('updated_at', 'desc')
            ->limit(6)
            ->get()
            ->map(fn($p) => $p->getBriefSummary());

        // Get recent activity
        $recentActivity = ActivityLog::where('user_id', $user->id)
            ->with('project')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn($log) => [
                'action' => $log->action,
                'entity_type' => $log->entity_type,
                'project_name' => $log->project?->name,
                'created_at' => $log->created_at,
            ]);

        // Get active projects for quick access
        $activeProjects = $user->projects()
            ->active()
            ->orderBy('updated_at', 'desc')
            ->limit(4)
            ->get()
            ->map(fn($p) => $p->getSummary());

        return $this->view('pages.dashboard', compact(
            'stats',
            'recentProjects',
            'recentActivity',
            'activeProjects'
        ));
    }
}
