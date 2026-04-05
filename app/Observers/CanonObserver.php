<?php

namespace App\Observers;

use App\Models\CanonEntry;
use App\Services\AI\EmbeddingGenerator;
use Illuminate\Support\Facades\Log;

class CanonObserver
{
    protected EmbeddingGenerator $generator;

    public function __construct(EmbeddingGenerator $generator)
    {
        $this->generator = $generator;
    }

    public function created(CanonEntry $entry): void
    {
        try {
            $this->generator->generateFor('canon', $entry->id);
        } catch (\Exception $e) {
            Log::error('Failed to create embedding for canon', ['error' => $e->getMessage()]);
        }
    }

    public function updated(CanonEntry $entry): void
    {
        if ($entry->isDirty(['title', 'content', 'type', 'tags'])) {
            try {
                $this->generator->regenerateFor('canon', $entry->id);
            } catch (\Exception $e) {
                Log::error('Failed to regenerate embedding for canon', ['error' => $e->getMessage()]);
            }
        }
    }

    public function deleted(CanonEntry $entry): void
    {
        \App\Models\Embedding::where('entity_type', 'canon')->where('entity_id', $entry->id)->delete();
    }
}
