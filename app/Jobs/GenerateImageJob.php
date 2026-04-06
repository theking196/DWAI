<?php

namespace App\Jobs;

use App\Models\AIOutput;
use App\Models\Session;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(
        public int $sessionId,
        public string $prompt,
        public array $options = []
    ) {}

    public function handle(): void
    {
        $session = Session::findOrFail($this->sessionId);
        
        $output = AIOutput::create([
            'session_id' => $session->id,
            'prompt' => $this->prompt,
            'type' => 'image',
            'model' => $this->options['model'] ?? 'mock-image-v1',
            'status' => 'processing',
        ]);

        try {
            $service = app(\App\Services\AI\AIService::class);
            $result = $service->generateImage($this->prompt, [
                'project' => $session->project,
                'session' => $session,
            ]);

            $output->markAsCompleted(json_encode($result));
        } catch (\Exception $e) {
            $output->markAsFailed($e->getMessage());
            throw $e;
        }
    }
}
