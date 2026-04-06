<?php

namespace App\Jobs;

use App\Models\AIOutput;
use App\Models\Session;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateTextJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public int $sessionId,
        public string $prompt,
        public array $options = []
    ) {}

    public function handle(): void
    {
        $session = Session::findOrFail($this->sessionId);
        
        // Create pending output
        $output = AIOutput::create([
            'session_id' => $session->id,
            'prompt' => $this->prompt,
            'type' => 'text',
            'model' => $this->options['model'] ?? 'mock-text-v1',
            'status' => 'processing',
        ]);

        try {
            // Call AI service
            $service = app(\App\Services\AI\AIService::class);
            $result = $service->generateText($this->prompt, [
                'project' => $session->project,
                'session' => $session,
            ]);

            // Save result
            $output->markAsCompleted($result);
            
            // Optionally save to session draft
            if ($this->options['save_to_draft'] ?? false) {
                $output->saveToSessionDraft();
            }
        } catch (\Exception $e) {
            $output->markAsFailed($e->getMessage());
            throw $e;
        }
    }
}
