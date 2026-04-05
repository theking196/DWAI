<?php

namespace App\Jobs;

use App\Models\AIOutput;
use App\Models\Session;
use App\Services\AI\AIService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job for generating AI output asynchronously.
 * 
 * Supports both text and image generation.
 */
class GenerateAIOutput implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying.
     */
    public int $backoff = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $sessionId,
        public string $prompt,
        public string $type = 'text',
        public array $options = []
    ) {}

    /**
     * Execute the job.
     */
    public function handle(AIService $ai): void
    {
        $session = Session::with('project')->findOrFail($this->sessionId);
        
        Log::info('AI Job Started', [
            'session_id' => $this->sessionId,
            'type' => $this->type,
            'prompt' => substr($this->prompt, 0, 100),
        ]);

        try {
            // Mark as processing
            $output = $this->createPendingOutput();
            $output->markAsProcessing();

            // Generate based on type
            $result = match ($this->type) {
                'image' => $this->generateImage($ai, $session),
                default => $this->generateText($ai, $session),
            };

            // Mark as completed
            $output->markAsCompleted($result['result']);
            
            // Update session count
            $session->increment('output_count');

            Log::info('AI Job Completed', [
                'output_id' => $output->id,
                'type' => $this->type,
            ]);

        } catch (\Exception $e) {
            Log::error('AI Job Failed', [
                'session_id' => $this->sessionId,
                'error' => $e->getMessage(),
            ]);

            $this->handleFailure($e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate text output.
     */
    protected function generateText(AIService $ai, Session $session): array
    {
        $result = $ai->generateText($session, $this->prompt, $this->options);
        
        return [
            'result' => $result->result,
            'model' => $result->model,
        ];
    }

    /**
     * Generate image output.
     */
    protected function generateImage(AIService $ai, Session $session): array
    {
        $result = $ai->generateImage($session, $this->prompt, $this->options);
        
        return [
            'result' => $result->result,
            'model' => $result->model,
        ];
    }

    /**
     * Create pending output record.
     */
    protected function createPendingOutput(): AIOutput
    {
        return AIOutput::create([
            'session_id' => $this->sessionId,
            'prompt' => $this->prompt,
            'result' => null,
            'type' => $this->type,
            'model' => $this->options['model'] ?? 'gpt-4',
            'status' => 'pending',
            'metadata' => $this->options,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $this->handleFailure($exception->getMessage());
    }

    /**
     * Handle failure case.
     */
    protected function handleFailure(string $error): void
    {
        $output = AIOutput::where('session_id', $this->sessionId)
            ->where('prompt', $this->prompt)
            ->where('status', 'pending')
            ->first();

        if ($output) {
            $output->markAsFailed($error);
        }
    }
}
