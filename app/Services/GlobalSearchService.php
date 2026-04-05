<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Session;
use App\Models\CanonEntry;
use App\Models\ReferenceImage;
use App\Models\AIOutput;
use App\Models\Conflict;
use App\Models\TimelineEvent;

class GlobalSearchService
{
    /**
     * Search across all entities.
     */
    public function search(string $query, ?int $userId = null, array $options = []): array
    {
        $limit = $options['limit'] ?? 20;
        $types = $options['types'] ?? ['projects', 'sessions', 'canon', 'references', 'outputs', 'conflicts', 'timeline'];

        $results = [];

        if (in_array('projects', $types)) {
            $results['projects'] = $this->searchProjects($query, $userId, $limit);
        }

        if (in_array('sessions', $types)) {
            $results['sessions'] = $this->searchSessions($query, $userId, $limit);
        }

        if (in_array('canon', $types)) {
            $results['canon'] = $this->searchCanon($query, $userId, $limit);
        }

        if (in_array('references', $types)) {
            $results['references'] = $this->searchReferences($query, $userId, $limit);
        }

        if (in_array('outputs', $types)) {
            $results['outputs'] = $this->searchOutputs($query, $userId, $limit);
        }

        if (in_array('conflicts', $types)) {
            $results['conflicts'] = $this->searchConflicts($query, $userId, $limit);
        }

        if (in_array('timeline', $types)) {
            $results['timeline'] = $this->searchTimeline($query, $userId, $limit);
        }

        // Flatten for unified results
        $results['all'] = $this->flattenResults($results, $limit);

        return $results;
    }

    protected function searchProjects(string $query, ?int $userId, int $limit): array
    {
        $qb = Project::query();
        
        if ($userId) {
            $qb->where('user_id', $userId);
        }

        return $qb->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%");
            })
            ->limit($limit)
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'type' => 'project',
                'title' => $p->name,
                'subtitle' => $p->description,
                'url' => "/projects/{$p->id}",
            ])
            ->toArray();
    }

    protected function searchSessions(string $query, ?int $userId, int $limit): array
    {
        $qb = Session::query();
        
        if ($userId) {
            $qb->where('user_id', $userId);
        }

        return $qb->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('notes', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%");
            })
            ->with('project')
            ->limit($limit)
            ->get()
            ->map(fn($s) => [
                'id' => $s->id,
                'type' => 'session',
                'title' => $s->name,
                'subtitle' => $s->project?->name,
                'url' => "/sessions/{$s->id}",
            ])
            ->toArray();
    }

    protected function searchCanon(string $query, ?int $userId, int $limit): array
    {
        $qb = CanonEntry::query();
        
        if ($userId) {
            $qb->where('user_id', $userId);
        }

        return $qb->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('content', 'like', "%{$query}%")
                  ->orWhereJsonContains('tags', $query);
            })
            ->with('project')
            ->limit($limit)
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'type' => 'canon',
                'title' => $c->title,
                'subtitle' => $c->type . ' in ' . ($c->project?->name ?? 'project'),
                'url' => "/canon/{$c->id}",
            ])
            ->toArray();
    }

    protected function searchReferences(string $query, ?int $userId, int $limit): array
    {
        $qb = ReferenceImage::query();
        
        if ($userId) {
            $qb->where('user_id', $userId);
        }

        return $qb->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%");
            })
            ->with('project')
            ->limit($limit)
            ->get()
            ->map(fn($r) => [
                'id' => $r->id,
                'type' => 'reference',
                'title' => $r->title,
                'subtitle' => $r->project?->name,
                'url' => "/references/{$r->id}",
            ])
            ->toArray();
    }

    protected function searchOutputs(string $query, ?int $userId, int $limit): array
    {
        $qb = AIOutput::query();
        
        if ($userId) {
            $qb->whereHas('session', fn($q) => $q->where('user_id', $userId));
        }

        return $qb->where(function ($q) use ($query) {
                $q->where('prompt', 'like', "%{$query}%")
                  ->orWhere('result', 'like', "%{$query}%");
            })
            ->with('session.project')
            ->limit($limit)
            ->get()
            ->map(fn($o) => [
                'id' => $o->id,
                'type' => 'output',
                'title' => "Output #{$o->id} ({$o->type})",
                'subtitle' => $o->session?->project?->name,
                'url' => "/outputs/{$o->id}",
            ])
            ->toArray();
    }

    protected function searchConflicts(string $query, ?int $userId, int $limit): array
    {
        $qb = Conflict::query();
        
        if ($userId) {
            $qb->where('user_id', $userId);
        }

        return $qb->where(function ($q) use ($query) {
                $q->where('description', 'like', "%{$query}%")
                  ->orWhere('type', 'like', "%{$query}%");
            })
            ->with('project')
            ->limit($limit)
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'type' => 'conflict',
                'title' => "{$c->type} ({$c->severity})",
                'subtitle' => $c->description,
                'url' => "/conflicts/{$c->id}",
            ])
            ->toArray();
    }

    protected function searchTimeline(string $query, ?int $userId, int $limit): array
    {
        $qb = TimelineEvent::query();
        
        if ($userId) {
            $qb->whereHas('project', fn($q) => $q->where('user_id', $userId));
        }

        return $qb->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%");
            })
            ->with('project')
            ->limit($limit)
            ->get()
            ->map(fn($t) => [
                'id' => $t->id,
                'type' => 'timeline',
                'title' => $t->title,
                'subtitle' => $t->project?->name,
                'url' => "/timeline/{$t->id}",
            ])
            ->toArray();
    }

    protected function flattenResults(array $results, int $limit): array
    {
        $flattened = [];
        
        foreach ($results as $type => $items) {
            if ($type === 'all' || !is_array($items)) continue;
            
            foreach ($items as $item) {
                $item['category'] = $type;
                $flattened[] = $item;
            }
        }

        // Sort by relevance (simple: exact match first, then contains)
        usort($flattened, function ($a, $b) {
            $aExact = stripos($a['title'], request()->get('q', '')) !== false;
            $bExact = stripos($b['title'], request()->get('q', '')) !== false;
            
            if ($aExact && !$bExact) return -1;
            if (!$aExact && $bExact) return 1;
            return 0;
        });

        return array_slice($flattened, 0, $limit);
    }
}
