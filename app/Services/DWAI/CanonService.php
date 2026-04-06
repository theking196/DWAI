<?php

namespace App\Services\DWAI;

use App\Models\CanonEntry;
use App\Models\CanonCandidate;
use App\Models\ActivityLog;
use App\Models\ChangeHistory;
use App\Services\SemanticSearchService;

class CanonService
{
    public function create(int $projectId, array $data): CanonEntry
    {
        $canon = CanonEntry::create([
            'user_id' => auth()->id(),
            'project_id' => $projectId,
            'title' => $data['title'],
            'type' => $data['type'] ?? 'note',
            'content' => $data['content'] ?? '',
            'tags' => $data['tags'] ?? [],
            'importance' => $data['importance'] ?? 'minor',
        ]);

        $this->indexEntry($canon);
        return $canon;
    }

    public function update(CanonEntry $canon, array $data): CanonEntry
    {
        $oldData = $canon->getOriginal();
        $canon->update($data);
        
        foreach ($data as $field => $newValue) {
            $oldValue = $oldData[$field] ?? null;
            if ($oldValue != $newValue) {
                ChangeHistory::recordCanonEdit(auth()->id(), $canon, [$field => $newValue]);
            }
        }

        $this->indexEntry($canon);
        return $canon;
    }

    public function search(int $projectId, string $query, array $filters = []): array
    {
        $qb = CanonEntry::where('project_id', $projectId);

        if (!empty($query)) {
            $qb->where(fn($q) => $q->where('title', 'like', "%{$query}%")->orWhere('content', 'like', "%{$query}%"));
        }

        if (!empty($filters['type'])) $qb->where('type', $filters['type']);
        if (!empty($filters['importance'])) $qb->where('importance', $filters['importance']);

        return $qb->orderBy('importance')->get()->toArray();
    }

    public function promoteFromSession($session, array $data): CanonCandidate
    {
        return CanonCandidate::createFromSession($session, $data);
    }

    public function indexEntry(CanonEntry $canon): void
    {
        try {
            app(SemanticSearchService::class)->indexEntity('canon', $canon->id);
        } catch (\Exception $e) {}
    }
}
