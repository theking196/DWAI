<?php

namespace App\Jobs;

use App\Models\AIOutput;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(
        public int $outputId,
        public array $options = []
    ) {}

    public function handle(): void
    {
        $output = AIOutput::findOrFail($this->outputId);

        try {
            // Extract text if needed
            if ($this->options['extract_text'] ?? true) {
                $this->extractText($output);
            }

            // Create embedding
            if ($this->options['embedding'] ?? true) {
                $this->createEmbedding($output);
            }

            // Link to project/session
            if (!empty($this->options['project_id'])) {
                $output->session->update(['project_id' => $this->options['project_id']]);
            }

            $output->setMetadata('processed', true);

        } catch (\Exception $e) {
            $output->setMetadata('processing_error', $e->getMessage());
            throw $e;
        }
    }

    protected function extractText(AIOutput $output): void
    {
        $text = $output->result ?? '';
        
        if (is_array($text)) {
            $text = json_encode($text);
        }

        $output->setMetadata('extracted_text_length', strlen($text));
    }

    protected function createEmbedding(AIOutput $output): void
    {
        $service = app(\App\Services\SemanticSearchService::class);
        
        $text = $output->prompt . ' ' . ($output->result ?? '');
        $service->indexEntity('output', $output->id, substr($text, 0, 2000));
        
        $output->setMetadata('embedding_created', true);
    }
}
