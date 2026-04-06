<?php

namespace App\Services\DWAI;

use App\Services\GlobalSearchService;
use App\Services\SemanticSearchService;

/**
 * Unified Search - combines keyword and semantic search
 */
class UnifiedSearchService
{
    protected GlobalSearchService $keyword;
    protected SemanticSearchService $semantic;

    public function __construct()
    {
        $this->keyword = app(GlobalSearchService::class);
        $this->semantic = app(SemanticSearchService::class);
    }

    /**
     * Search across everything.
     */
    public function search(string $query, int $userId, array $options = []): array
    {
        $options['limit'] = $options['limit'] ?? 20;
        
        // Get keyword results
        $keywordResults = $this->keyword->search($query, $userId, $options);
        
        // Get semantic results
        $semanticResults = [];
        if (!empty($options['semantic'])) {
            $semanticResults = $this->semantic->findRelevantContext($query, $userId, [
                'limit' => $options['limit'],
                'types' => $options['types'] ?? ['canon', 'references'],
            ]);
        }
        
        return [
            'query' => $query,
            'keyword' => $keywordResults['all'] ?? [],
            'semantic' => $semanticResults['ranked'] ?? [],
            'sources' => array_keys(array_filter($keywordResults)),
        ];
    }

    /**
     * Get context for AI from search.
     */
    public function getContext(string $query, int $userId): string
    {
        $semantic = $this->semantic->getContextSummary($query, $userId, 5);
        
        $keyword = $this->keyword->search($query, $userId, ['limit' => 5]);
        
        $context = $semantic;
        
        if (!empty($keyword['all'])) {
            $context .= "\n\n=== KEYWORD MATCHES ===\n";
            foreach ($keyword['all'] as $match) {
                $context .= "- [{$match['category']}] {$match['title']}\n";
            }
        }
        
        return $context;
    }
}
