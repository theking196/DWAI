<?php

namespace App\Services\AI;

use App\Models\Embedding;
use Illuminate\Support\Facades\Log;

class EmbeddingGenerator
{
    protected EmbeddingService $embeddingService;

    public function __construct(EmbeddingService $embeddingService)
    {
        $this->embeddingService = $embeddingService;
    }

    /**
     * Generate embeddings for all project content.
     */
    public function generateForProject(int $projectId): array
    {
        $project = \App\Models\Project::findOrFail($projectId);
        $results = ['canon' => 0, 'references' => 0, 'sessions' => 0, 'errors' => []];

        // 1. Generate for canon entries
        foreach ($project->canonEntries as $canon) {
            try {
                $this->embeddingService->createForEntity('canon', $canon->id);
                $results['canon']++;
            } catch (\Exception $e) {
                $results['errors'][] = "Canon {$canon->id}: " . $e->getMessage();
            }
        }

        // 2. Generate for reference images
        foreach ($project->referenceImages as $ref) {
            try {
                $this->embeddingService->createForEntity('reference', $ref->id);
                $results['references']++;
            } catch (\Exception $e) {
                $results['errors'][] = "Reference {$ref->id}: " . $e->getMessage();
            }
        }

        // 3. Generate for sessions
        foreach ($project->sessions as $session) {
            try {
                $this->embeddingService->createForEntity('session', $session->id);
                $results['sessions']++;
            } catch (\Exception $e) {
                $results['errors'][] = "Session {$session->id}: " . $e->getMessage();
            }
        }

        // 4. Generate for project itself
        try {
            $this->embeddingService->createForEntity('project', $project->id);
        } catch (\Exception $e) {
            $results['errors'][] = "Project: " . $e->getMessage();
        }

        Log::info("Embedding generation completed", $results);
        
        return $results;
    }

    /**
     * Generate embedding for single entity.
     */
    public function generateFor(string $entityType, int $entityId): ?Embedding
    {
        return $this->embeddingService->createForEntity($entityType, $entityId);
    }

    /**
     * Regenerate embeddings for entity (replace existing).
     */
    public function regenerateFor(string $entityType, int $entityId): ?Embedding
    {
        // Delete existing
        Embedding::where('entity_type', $entityType)->where('entity_id', $entityId)->delete();
        
        // Create new
        return $this->embeddingService->createForEntity($entityType, $entityId);
    }

    /**
     * Semantic search across project knowledge.
     */
    public function semanticSearch(string $query, int $projectId, array $options = []): array
    {
        $types = $options['types'] ?? ['canon', 'reference', 'session'];
        $limit = $options['limit'] ?? 10;
        
        $allResults = [];
        
        foreach ($types as $type) {
            $results = $this->embeddingService->searchSimilar($query, $type, $projectId, $limit);
            
            if ($results['success']) {
                foreach ($results['results'] as $r) {
                    $r['entity_type'] = $type;
                    $allResults[] = $r;
                }
            }
        }

        // Sort by similarity
        usort($allResults, fn($a, $b) => $b['similarity'] <=> $a['similarity']);
        
        return array_slice($allResults, 0, $limit);
    }

    /**
     * Get entities with embeddings for a project.
     */
    public function getProjectEmbeddingStats(int $projectId): array
    {
        $project = \App\Models\Project::findOrFail($projectId);
        
        return [
            'project' => Embedding::where('entity_type', 'project')->where('entity_id', $projectId)->count(),
            'canon' => $project->canonEntries()->whereHas('embeddings', fn($q) => $q->where('status', 'processed'))->count(),
            'references' => $project->referenceImages()->whereHas('embeddings', fn($q) => $q->where('status', 'processed'))->count(),
            'sessions' => $project->sessions()->whereHas('embeddings', fn($q) => $q->where('status', 'processed'))->count(),
            'total_processed' => Embedding::where('status', 'processed')->count(),
            'total_pending' => Embedding::where('status', 'pending')->count(),
        ];
    }
}
