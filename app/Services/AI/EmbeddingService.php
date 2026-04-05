<?php

namespace App\Services\AI;

use App\Models\Embedding;
use Illuminate\Support\Facades\Log;

class EmbeddingService
{
    protected string $provider = 'mock';
    protected int $dimensions = 1536;

    public function __construct()
    {
        $this->provider = config('ai.embedding_provider', 'mock');
        $this->dimensions = config('ai.embedding_dimensions', 1536);
    }

    /**
     * Generate embedding for text.
     */
    public function generate(string $text): array
    {
        if ($this->provider === 'mock') {
            return $this->mockEmbedding($text);
        }

        // Future: OpenAI, Cohere, etc.
        return ['success' => false, 'error' => 'Provider not implemented'];
    }

    /**
     * Generate mock embedding for development.
     */
    protected function mockEmbedding(string $text): array
    {
        // Generate consistent mock vector based on text hash
        $hash = md5($text);
        $vector = array_fill(0, $this->dimensions, 0);
        
        // Fill with pseudo-random values based on hash
        for ($i = 0; $i < $this->dimensions; $i++) {
            $char = substr($hash, ($i * 2) % strlen($hash), 1);
            $vector[$i] = (ord($char) - 128) / 128;
        }

        return [
            'success' => true,
            'vector' => base64_encode(serialize($vector)),
            'dimensions' => $this->dimensions,
            'model' => 'mock-embed-v1',
            'text_length' => strlen($text),
        ];
    }

    /**
     * Create and process embedding for entity.
     */
    public function createForEntity(string $entityType, int $entityId): ?Embedding
    {
        $embedding = match($entityType) {
            'canon' => $this->createForCanon($entityId),
            'reference' => $this->createForReference($entityId),
            'project' => $this->createForProject($entityId),
            'session' => $this->createForSession($entityId),
            default => null,
        };

        if (!$embedding) return null;

        // Generate embedding
        $result = $this->generate($embedding->chunk_text);
        
        if ($result['success']) {
            $embedding->markProcessed($result['vector'], $result['model']);
        } else {
            $embedding->markFailed($result['error'] ?? 'Generation failed');
        }

        return $embedding;
    }

    protected function createForCanon(int $id): ?Embedding
    {
        $canon = \App\Models\CanonEntry::find($id);
        return $canon ? Embedding::forCanon($canon) : null;
    }

    protected function createForReference(int $id): ?Embedding
    {
        $ref = \App\Models\ReferenceImage::find($id);
        return $ref ? Embedding::forReference($ref) : null;
    }

    protected function createForProject(int $id): ?Embedding
    {
        $project = \App\Models\Project::find($id);
        return $project ? Embedding::forProject($project) : null;
    }

    protected function createForSession(int $id): ?Embedding
    {
        $session = \App\Models\Session::find($id);
        return $session ? Embedding::forSession($session) : null;
    }

    /**
     * Search similar embeddings.
     */
    public function searchSimilar(string $query, string $entityType, int $projectId, int $limit = 5): array
    {
        $queryEmbedding = $this->generate($query);
        
        if (!$queryEmbedding['success']) {
            return ['success' => false, 'error' => 'Failed to generate query embedding'];
        }

        // Get project embeddings
        $embeddings = Embedding::whereHas('user', function ($q) use ($projectId) {
            $q->where('project_id', $projectId);
        })->where('entity_type', $entityType)->where('status', 'processed')->get();

        // Simple similarity (cosine-ish)
        $results = [];
        foreach ($embeddings as $emb) {
            $similarity = $this->cosineSimilarity(
                unserialize(base64_decode($queryEmbedding['vector'])),
                unserialize(base64_decode($emb->embedding_vector))
            );
            $results[] = ['id' => $emb->id, 'entity_id' => $emb->entity_id, 'similarity' => $similarity];
        }

        // Sort by similarity
        usort($results, fn($a, $b) => $b['similarity'] <=> $a['similarity']);
        
        return ['success' => true, 'results' => array_slice($results, 0, $limit)];
    }

    /**
     * Simple cosine similarity calculation.
     */
    protected function cosineSimilarity(array $a, array $b): float
    {
        $dot = 0;
        $magA = 0;
        $magB = 0;
        
        for ($i = 0; $i < count($a); $i++) {
            $dot += $a[$i] * $b[$i];
            $magA += $a[$i] * $a[$i];
            $magB += $b[$i] * $b[$i];
        }
        
        if ($magA === 0 || $magB === 0) return 0;
        
        return $dot / (sqrt($magA) * sqrt($magB));
    }

    /**
     * Get embedding for entity.
     */
    public function getForEntity(string $entityType, int $entityId): ?Embedding
    {
        return Embedding::where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->where('status', 'processed')
            ->first();
    }
}
