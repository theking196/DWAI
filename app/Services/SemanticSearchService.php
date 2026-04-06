<?php

namespace App\Services;

use App\Models\Embedding;
use App\Models\CanonEntry;
use App\Models\ReferenceImage;
use App\Models\AIOutput;
use App\Models\TimelineEvent;

class SemanticSearchService
{
    /**
     * Find relevant context by meaning, not keywords.
     */
    public function findRelevantContext(string $query, int $userId, array $options = []): array
    {
        $limit = $options['limit'] ?? 10;
        $minScore = $options['min_score'] ?? 0.5;
        $types = $options['types'] ?? ['canon', 'references', 'outputs', 'timeline'];

        // Generate embedding for query (would use AI provider in production)
        $queryEmbedding = $this->generateEmbedding($query);

        $results = [];

        foreach ($types as $type) {
            $results[$type] = $this->searchType($type, $queryEmbedding, $userId, $limit, $minScore);
        }

        // Sort by score across all types
        $results['ranked'] = $this->rankResults($results, $limit);

        return $results;
    }

    /**
     * Generate embedding for text (mock - in production use AI provider).
     */
    protected function generateEmbedding(string $text): array
    {
        // In production: call AI provider to get vector
        // Mock: simple hash-based representation
        $hash = md5($text);
        $vector = [];
        
        for ($i = 0; $i < 384; $i++) {
            $vector[] = (ord($hash[$i % strlen($hash)]) - 127.5) / 127.5;
        }
        
        return $vector;
    }

    /**
     * Calculate cosine similarity between two vectors.
     */
    protected function cosineSimilarity(array $a, array $b): float
    {
        $dotProduct = 0;
        $normA = 0;
        $normB = 0;

        $len = min(count($a), count($b));
        
        for ($i = 0; $i < $len; $i++) {
            $dotProduct += $a[$i] * $b[$i];
            $normA += $a[$i] * $a[$i];
            $normB += $b[$i] * $b[$i];
        }

        if ($normA == 0 || $normB == 0) {
            return 0;
        }

        return $dotProduct / (sqrt($normA) * sqrt($normB));
    }

    /**
     * Search a specific entity type.
     */
    protected function searchType(string $type, array $queryEmbedding, int $userId, int $limit, float $minScore): array
    {
        $embeddings = Embedding::where('user_id', $userId)
            ->where('entity_type', $type)
            ->where('status', 'completed')
            ->get();

        $results = [];

        foreach ($embeddings as $embedding) {
            $vector = is_array($embedding->embedding_vector) 
                ? $embedding->embedding_vector 
                : json_decode($embedding->embedding_vector, true);

            if (!$vector) continue;

            $score = $this->cosineSimilarity($queryEmbedding, $vector);

            if ($score >= $minScore) {
                $results[] = [
                    'embedding_id' => $embedding->id,
                    'entity_type' => $type,
                    'entity_id' => $embedding->entity_id,
                    'score' => round($score, 3),
                    'text' => $embedding->chunk_text,
                    'metadata' => $embedding->metadata,
                ];
            }
        }

        // Sort by score and limit
        usort($results, fn($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($results, 0, $limit);
    }

    /**
     * Rank results across all types.
     */
    protected function rankResults(array $results, int $limit): array
    {
        $ranked = [];

        foreach ($results as $type => $items) {
            if ($type === 'ranked') continue;
            
            foreach ($items as $item) {
                $item['category'] = $type;
                $ranked[] = $item;
            }
        }

        usort($ranked, fn($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($ranked, 0, $limit);
    }

    /**
     * Get context summary for AI prompt.
     */
    public function getContextSummary(string $query, int $userId, int $maxItems = 5): string
    {
        $results = $this->findRelevantContext($query, $userId, [
            'limit' => $maxItems,
            'min_score' => 0.3,
        ]);

        if (empty($results['ranked'])) {
            return '';
        }

        $summary = "=== RELEVANT CONTEXT ===\n\n";
        
        foreach ($results['ranked'] as $item) {
            $summary .= "[{$item['category']}] Score: {$item['score']}\n";
            $summary .= substr($item['text'], 0, 300) . "\n\n";
        }

        return $summary;
    }

    /**
     * Index entity for semantic search.
     */
    public function indexEntity(string $type, int $entityId, ?string $text = null): Embedding
    {
        $model = match($type) {
            'canon' => CanonEntry::find($entityId),
            'reference' => ReferenceImage::find($entityId),
            'output' => AIOutput::find($entityId),
            'timeline' => TimelineEvent::find($entityId),
            default => null,
        };

        if (!$model) {
            throw new \Exception("Entity not found: {$type}/{$entityId}");
        }

        // Get text to embed
        $text = $text ?? match($type) {
            'canon' => $model->title . ' ' . ($model->content ?? ''),
            'reference' => $model->title . ' ' . ($model->description ?? ''),
            'output' => $model->prompt . ' ' . ($model->result ?? ''),
            'timeline' => $model->title . ' ' . ($model->description ?? ''),
            default => '',
        };

        // Generate embedding
        $vector = $this->generateEmbedding($text);

        // Check for existing
        $embedding = Embedding::where('entity_type', $type)
            ->where('entity_id', $entityId)
            ->first();

        if ($embedding) {
            $embedding->update([
                'embedding_vector' => json_encode($vector),
                'chunk_text' => substr($text, 0, 2000),
                'status' => 'completed',
            ]);
        } else {
            $embedding = Embedding::create([
                'user_id' => $model->user_id,
                'entity_type' => $type,
                'entity_id' => $entityId,
                'embedding_vector' => json_encode($vector),
                'chunk_text' => substr($text, 0, 2000),
                'dimensions' => count($vector),
                'model' => 'semantic-mock-v1',
                'status' => 'completed',
            ]);
        }

        return $embedding;
    }
}
