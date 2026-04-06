<?php

namespace App\Services\DWAI;

use App\Models\Project;
use App\Models\Session;
use App\Models\CanonEntry;
use App\Models\ReferenceImage;
use App\Models\TimelineEvent;
use App\Models\AIOutput;
use App\Models\Conflict;
use App\Models\ActivityLog;

/**
 * Unified Data Service - connects all DWAI components
 */
class UnifiedDataService
{
    /**
     * Get complete project data with all related entities.
     */
    public function getProjectData(int $projectId): array
    {
        $project = Project::findOrFail($projectId);
        
        return [
            'project' => $project,
            'sessions' => $project->sessions()->where('status', '!=', 'archived')->get(),
            'canon' => $project->canonEntries()->orderBy('importance')->get(),
            'references' => $project->referenceImages()->get(),
            'timeline' => $project->timelineEvents()->orderBy('order_index')->get(),
            'conflicts' => Conflict::active($projectId),
            'stats' => app(ProjectService::class)->getStats($project),
            'recent_activity' => ActivityLog::forProject($projectId, 10),
        ];
    }

    /**
     * Get complete session data with all related entities.
     */
    public function getSessionData(int $sessionId): array
    {
        $session = Session::findOrFail($sessionId);
        $project = $session->project;
        
        return [
            'session' => $session,
            'project' => $project,
            'outputs' => $session->aiOutputs()->orderBy('created_at', 'desc')->get(),
            'memory' => app(MemoryService::class)->getSessionMemory($session),
            'canon' => $project->canonEntries()->get(),
            'references' => $project->referenceImages()->get(),
        ];
    }

    /**
     * Get full context for AI generation (project + session + canon + style).
     */
    public function getAIContext(int $sessionId): array
    {
        $session = Session::findOrFail($sessionId);
        $project = $session->project;
        
        return [
            'project' => [
                'name' => $project->name,
                'description' => $project->description,
                'type' => $project->type,
                'visual_style' => $session->hasStyleOverride() 
                    ? $session->getEffectiveStyle()
                    : [
                        'image_url' => $project->getVisualStyleUrl(),
                        'description' => $project->getVisualStyleDescription(),
                        'source' => 'project',
                      ],
            ],
            'session' => [
                'name' => $session->name,
                'notes' => $session->notes,
                'draft_text' => $session->draft_text,
                'references' => $session->session_references ?? [],
            ],
            'canon' => $project->canonEntries()
                ->orderBy('importance')
                ->limit(20)
                ->get()
                ->map(fn($c) => ['title' => $c->title, 'type' => $c->type, 'content' => substr($c->content, 0, 500)])
                ->toArray(),
            'references' => $project->referenceImages()
                ->limit(10)
                ->get()
                ->map(fn($r) => ['title' => $r->title, 'url' => $r->url])
                ->toArray(),
        ];
    }

    /**
     * Get timeline with canon references.
     */
    public function getTimelineWithCanon(int $projectId): array
    {
        $events = TimelineEvent::where('project_id', $projectId)
            ->orderBy('order_index')
            ->get();
            
        return $events->map(function ($event) {
            $related = [];
            
            // Get related canon if any
            if ($event->related_canon_id) {
                $related['canon'] = CanonEntry::find($event->related_canon_id);
            }
            
            return [
                'event' => $event,
                'related' => $related,
            ];
        })->toArray();
    }

    /**
     * Get dashboard data for user.
     */
    public function getDashboardData(int $userId): array
    {
        $projects = Project::where('user_id', $userId)->where('status', '!=', 'archived')->get();
        
        $data = [
            'projects' => $projects,
            'stats' => [
                'total_projects' => $projects->count(),
                'active_sessions' => Session::where('user_id', $userId)->where('status', 'active')->count(),
                'total_canon' => CanonEntry::where('user_id', $userId)->count(),
                'total_outputs' => AIOutput::whereHas('session', fn($q) => $q->where('user_id', $userId))->count(),
                'active_conflicts' => Conflict::where('user_id', $userId)->whereIn('status', ['detected', 'acknowledged'])->count(),
            ],
            'recent_activity' => ActivityLog::recent($userId, 15),
        ];
        
        return $data;
    }

    /**
     * Sync all data for a project.
     */
    public function syncProject(int $projectId): array
    {
        $project = Project::findOrFail($projectId);
        $results = ['project' => true];
        
        // Sync embeddings for canon
        try {
            $canon = $project->canonEntries()->get();
            foreach ($canon as $entry) {
                app(CanonService::class)->indexEntry($entry);
            }
            $results['canon_indexed'] = $canon->count();
        } catch (\Exception $e) {
            $results['canon_indexed'] = 0;
        }
        
        // Scan conflicts
        try {
            $results['conflicts_scanned'] = Conflict::syncFromDetection($projectId);
        } catch (\Exception $e) {
            $results['conflicts_scanned'] = 0;
        }
        
        return $results;
    }
}
