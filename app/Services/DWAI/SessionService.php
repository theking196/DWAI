<?php

namespace App\Services\DWAI;

use App\Models\Session;
use App\Models\AIOutput;
use App\Models\ActivityLog;
use App\Services\DWJobs;

class SessionService
{
    public function create(int $projectId, array $data): Session
    {
        $session = Session::create([
            'user_id' => auth()->id(),
            'project_id' => $projectId,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'type' => $data['type'] ?? 'writing',
            'status' => 'active',
        ]);

        ActivityLog::sessionStarted(auth()->id(), $session);
        return $session;
    }

    public function updateNotes(Session $session, string $notes): Session
    {
        $session->update(['notes' => $notes]);
        return $session;
    }

    public function updateDraft(Session $session, string $draft): Session
    {
        $session->updateDraftText($draft);
        return $session;
    }

    public function appendToDraft(Session $session, string $content): Session
    {
        $current = $session->draft_text ?? '';
        $session->updateDraftText($current . "\n\n---\n\n" . $content);
        return $session;
    }

    public function addReference(Session $session, array $reference): void
    {
        $session->addSessionReference($reference);
    }

    public function archive(Session $session): void
    {
        $session->update(['status' => 'archived']);
    }

    public function getOutputs(Session $session): array
    {
        return AIOutput::where('session_id', $session->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($o) => $o->getSummary())
            ->toArray();
    }

    public function queueTextGeneration(Session $session, string $prompt, array $options = []): void
    {
        DWJobs::generateText($session->id, $prompt, $options);
    }

    public function queueImageGeneration(Session $session, string $prompt, array $options = []): void
    {
        DWJobs::generateImage($session->id, $prompt, $options);
    }
}
