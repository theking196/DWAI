<?php

namespace App\Services\AI;

use App\Models\CanonEntry;
use App\Models\ReferenceImage;
use App\Models\Session;

class SemanticSearchService
{
    protected EmbeddingGenerator $generator;

    public function __construct(EmbeddingGenerator $generator)
    {
        $this->generator = $generator;
    }

    /**
     * Search canon entries by meaning.
     */
    public function searchCanon(string $query, int $projectId, int $limit = 5): array
    {
        $results = $this->generator->semanticSearch($query, $projectId, [
            'types' => ['canon'],
            'limit' => $limit,
        ]);

        // Enrich with canon data
        $enriched = [];
        foreach ($results as $result) {
            $canon = CanonEntry::find($result['entity_id']);
            if ($canon) {
                $enriched[] = [
                    'id' => $canon->id,
                    'title' => $canon->title,
                    'type' => $canon->type,
                    'content_preview' => substr($canon->content ?? '', 0, 150),
                    'importance' => $canon->importance,
                    'tags' => $canon->tags,
                    'relevance_score' => $result['similarity'],
                ];
            }
        }

        return $enriched;
    }

    /**
     * Search reference images by meaning.
     */
    public function searchReferences(string $query, int $projectId, int $limit = 5): array
    {
        $results = $this->generator->semanticSearch($query, $projectId, [
            'types' => ['reference'],
            'limit' => $limit,
        ]);

        $enriched = [];
        foreach ($results as $result) {
            $ref = ReferenceImage::find($result['entity_id']);
            if ($ref) {
                $enriched[] = [
                    'id' => $ref->id,
                    'title' => $ref->title,
                    'description' => $ref->description,
                    'url' => $ref->url,
                    'is_primary' => $ref->is_primary,
                    'relevance_score' => $result['similarity'],
                ];
            }
        }

        return $enriched;
    }

    /**
     * Search session notes by meaning.
     */
    public function searchSessions(string $query, int $projectId, int $limit = 5): array
    {
        $results = $this->generator->semanticSearch($query, $projectId, [
            'types' => ['session'],
            'limit' => $limit,
        ]);

        $enriched = [];
        foreach ($results as $result) {
            $session = Session::find($result['entity_id']);
            if ($session) {
                $enriched[] = [
                    'id' => $session->id,
                    'name' => $session->name,
                    'notes_preview' => substr($session->notes ?? $session->temp_notes ?? '', 0, 150),
                    'status' => $session->status,
                    'relevance_score' => $result['similarity'],
                ];
            }
        }

        return $enriched;
    }

    /**
     * Unified search across all knowledge.
     */
    public function searchAll(string $query, int $projectId, array $options = []): array
    {
        $limits = $options['limits'] ?? ['canon' => 3, 'references' => 2, 'sessions' => 2];
        
        return [
            'query' => $query,
            'results' => [
                'canon' => $this->searchCanon($query, $projectId, $limits['canon']),
                'references' => $this->searchReferences($query, $projectId, $limits['references']),
                'sessions' => $this->searchSessions($query, $projectId, $limits['sessions']),
            ],
            'metadata' => [
                'searched_at' => now()->toISOString(),
                'project_id' => $projectId,
            ],
        ];
    }

    /**
     * Get context for AI - only relevant results above threshold.
     */
    public function getRelevantContext(string $query, int $projectId, float $threshold = 0.5): array
    {
        $allResults = $this->searchAll($query, $projectId);
        
        $context = [
            'canon' => [],
            'references' => [],
            'sessions' => [],
        ];

        // Filter by threshold
        foreach ($allResults['results']['canon'] as $item) {
            if ($item['relevance_score'] >= $threshold) {
                $context['canon'][] = $item;
            }
        }
        foreach ($allResults['results']['references'] as $item) {
            if ($item['relevance_score'] >= $threshold) {
                $context['references'][] = $item;
            }
        }
        foreach ($allResults['results']['sessions'] as $item) {
            if ($item['relevance_score'] >= $threshold) {
                $context['sessions'][] = $item;
            }
        }

        return [
            'query' => $query,
            'context' => $context,
            'total_matches' => count($context['canon']) + count($context['references']) + count($context['sessions']),
            'threshold' => $threshold,
        ];
    }
}
