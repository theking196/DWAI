<?php

namespace App\Services\DWAI;

use App\Services\SemanticSearchService;

class VectorSearchService
{
    protected SemanticSearchService $semantic;

    public function __construct()
    {
        $this->semantic = app(SemanticSearchService::class);
    }

    public function search(string $query, int $userId, array $options = []): array
    {
        return $this->semantic->findRelevantContext($query, $userId, [
            'limit' => $options['limit'] ?? 10,
            'min_score' => $options['min_score'] ?? 0.5,
            'types' => $options['types'] ?? ['canon', 'references', 'outputs', 'timeline'],
        ]);
    }

    public function getContextSummary(string $query, int $userId, int $maxItems = 5): string
    {
        return $this->semantic->getContextSummary($query, $userId, $maxItems);
    }

    public function indexEntity(string $type, int $entityId): void
    {
        $this->semantic->indexEntity($type, $entityId);
    }

    public function reindexProject(int $projectId): int
    {
        $count = 0;
        
        foreach (['canon', 'reference', 'output'] as $type) {
            $this->semantic->indexEntity($type, $projectId);
            $count++;
        }
        
        return $count;
    }
}
