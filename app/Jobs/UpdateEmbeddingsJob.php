<?php

namespace App\Jobs;

use App\Models\CanonEntry;
use App\Models\ReferenceImage;
use App\Models\AIOutput;
use App\Models\TimelineEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateEmbeddingsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $backoff = 120;

    public function __construct(
        public int $userId,
        public string $entityType,
        public ?int $entityId = null,
        public ?int $projectId = null
    ) {}

    public function handle(): void
    {
        $service = app(\App\Services\SemanticSearchService::class);

        $query = match($this->entityType) {
            'canon' => CanonEntry::query(),
            'reference' => ReferenceImage::query(),
            'output' => AIOutput::query(),
            'timeline' => TimelineEvent::query(),
            default => null,
        };

        if (!$query) return;

        if ($this->userId) {
            $query->where('user_id', $this->userId);
        }
        if ($this->projectId) {
            $query->where('project_id', $this->projectId);
        }
        if ($this->entityId) {
            $query->where('id', $this->entityId);
        }

        foreach ($query->get() as $entity) {
            try {
                $service->indexEntity($this->entityType, $entity->id);
            } catch (\Exception $e) {
                // Log but continue
                \Log::error("Embedding failed: {$this->entityType}/{$entity->id} - " . $e->getMessage());
            }
        }
    }
}
