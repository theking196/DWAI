<?php

namespace App\Services;

use App\Models\Project;
use App\Models\CanonEntry;
use App\Models\ReferenceImage;
use App\Models\Session;

class LookupService
{
    /**
     * Find project by name.
     */
    public function findProject(string $name, int $userId): ?Project
    {
        return Project::where('user_id', $userId)
            ->where('name', 'like', "%{$name}%")
            ->first();
    }

    /**
     * Find canon by title.
     */
    public function findCanon(string $title, ?int $projectId = null, ?int $userId = null): ?CanonEntry
    {
        $qb = CanonEntry::where('title', 'like', "%{$title}%");
        
        if ($projectId) {
            $qb->where('project_id', $projectId);
        }
        if ($userId) {
            $qb->where('user_id', $userId);
        }
        
        return $qb->first();
    }

    /**
     * Find references by tag.
     */
    public function findReferencesByTag(string $tag, ?int $projectId = null, int $limit = 20): array
    {
        $qb = ReferenceImage::whereJsonContains('tags', $tag);
        
        if ($projectId) {
            $qb->where('project_id', $projectId);
        }
        
        return $qb->limit($limit)->get()
            ->map(fn($r) => [
                'id' => $r->id,
                'title' => $r->title,
                'url' => $r->url,
                'project_id' => $r->project_id,
            ])
            ->toArray();
    }

    /**
     * Find session by project.
     */
    public function findSessionsByProject(int $projectId, string $status = null): array
    {
        $qb = Session::where('project_id', $projectId);
        
        if ($status) {
            $qb->where('status', $status);
        }
        
        return $qb->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'status' => $s->status,
                'type' => $s->type,
                'created_at' => $s->created_at->toISOString(),
            ])
            ->toArray();
    }

    /**
     * Quick lookup - auto-detect type.
     */
    public function quickLookup(string $query, int $userId): array
    {
        $results = [];

        // Project by name
        $project = $this->findProject($query, $userId);
        if ($project) {
            $results['project'] = ['id' => $project->id, 'name' => $project->name];
        }

        // Canon by title
        $canon = $this->findCanon($query, null, $userId);
        if ($canon) {
            $results['canon'] = ['id' => $canon->id, 'title' => $canon->title, 'type' => $canon->type];
        }

        return $results;
    }
}
