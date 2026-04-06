<?php

namespace App\Services\DWAI;

use App\Models\Session;
use App\Models\CanonCandidate;

class MemoryService
{
    public function getSessionMemory(Session $session): array
    {
        return [
            'notes' => $session->temp_notes,
            'ai_reasoning' => $session->ai_reasoning,
            'draft_text' => $session->draft_text,
            'references' => $session->session_references ?? [],
        ];
    }

    public function updateSessionNotes(Session $session, string $notes): void
    {
        $session->updateTempNotes($notes);
    }

    public function updateAIMemory(Session $session, string $reasoning): void
    {
        $session->update(['ai_reasoning' => $reasoning]);
    }

    public function promoteToCanon(Session $session, array $data): CanonCandidate
    {
        return CanonCandidate::createFromSession($session, $data);
    }

    public function clearSessionMemory(Session $session): void
    {
        $session->update([
            'temp_notes' => null,
            'ai_reasoning' => null,
            'draft_text' => null,
            'session_references' => [],
        ]);
    }
}
