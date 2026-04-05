<?php

namespace App\Observers;

use App\Models\ReferenceImage;
use App\Services\AI\EmbeddingGenerator;

class ReferenceImageObserver
{
    protected EmbeddingGenerator $generator;

    public function __construct(EmbeddingGenerator $generator)
    {
        $this->generator = $generator;
    }

    public function created(ReferenceImage $ref): void
    {
        try {
            $this->generator->generateFor('reference', $ref->id);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed embedding for reference', ['error' => $e->getMessage()]);
        }
    }

    public function updated(ReferenceImage $ref): void
    {
        if ($ref->isDirty(['title', 'description'])) {
            try {
                $this->generator->regenerateFor('reference', $ref->id);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed regen embedding for reference', ['error' => $e->getMessage()]);
            }
        }
    }

    public function deleted(ReferenceImage $ref): void
    {
        \App\Models\Embedding::where('entity_type', 'reference')->where('entity_id', $ref->id)->delete();
    }
}
