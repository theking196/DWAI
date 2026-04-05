<?php

namespace App\Observers;

use App\Models\Session;
use App\Services\AI\EmbeddingGenerator;

class SessionObserver
{
    protected EmbeddingGenerator $generator;

    public function __construct(EmbeddingGenerator $generator)
    {
        $this->generator = $generator;
    }

    public function created(Session $session): void
    {
        try {
            $this->generator->generateFor('session', $session->id);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed embedding for session', ['error' => $e->getMessage()]);
        }
    }

    public function updated(Session $session): void
    {
        if ($session->isDirty(['name', 'notes', 'temp_notes', 'draft_text'])) {
            try {
                $this->generator->regenerateFor('session', $session->id);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed regen embedding for session', ['error' => $e->getMessage()]);
            }
        }
    }

    public function deleted(Session $session): void
    {
        \App\Models\Embedding::where('entity_type', 'session')->where('entity_id', $session->id)->delete();
    }
}
