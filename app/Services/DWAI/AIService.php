<?php

namespace App\Services\DWAI;

use App\Models\Session;
use App\Models\AIOutput;
use App\Services\AI\AIService as BaseAIService;
use App\Services\DWJobs;

class AIService
{
    protected BaseAIService $aiService;

    public function __construct()
    {
        $this->aiService = app(BaseAIService::class);
    }

    public function generateText(Session $session, string $prompt, array $options = []): AIOutput
    {
        // Queue for async processing
        DWJobs::generateText($session->id, $prompt, $options);
        
        // Return pending output (will be updated by job)
        return AIOutput::create([
            'session_id' => $session->id,
            'prompt' => $prompt,
            'type' => 'text',
            'status' => 'pending',
        ]);
    }

    public function generateImage(Session $session, string $prompt, array $options = []): AIOutput
    {
        DWJobs::generateImage($session->id, $prompt, $options);
        
        return AIOutput::create([
            'session_id' => $session->id,
            'prompt' => $prompt,
            'type' => 'image',
            'status' => 'pending',
        ]);
    }

    public function getOutput(int $outputId): ?AIOutput
    {
        return AIOutput::find($outputId);
    }

    public function getSessionOutputs(int $sessionId): array
    {
        return AIOutput::where('session_id', $sessionId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($o) => $o->getSummary())
            ->toArray();
    }
}
