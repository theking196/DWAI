<?php

namespace App\Jobs;

use App\Models\ReferenceImage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessUploadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $backoff = 30;

    public function __construct(
        public int $referenceImageId,
        public array $options = []
    ) {}

    public function handle(): void
    {
        $image = ReferenceImage::findOrFail($this->referenceImageId);

        try {
            // Generate thumbnail
            if ($this->options['thumbnail'] ?? true) {
                $this->generateThumbnail($image);
            }

            // Extract metadata
            if ($this->options['metadata'] ?? true) {
                $this->extractMetadata($image);
            }

            // Create embedding
            if ($this->options['embedding'] ?? false) {
                $this->createEmbedding($image);
            }

            // Link to canon
            if (!empty($this->options['link_to_canon'])) {
                $this->linkToCanon($image, $this->options['link_to_canon']);
            }

            // Mark as processed
            $image->markAsProcessed();

        } catch (\Exception $e) {
            $image->markAsFailed($e->getMessage());
            throw $e;
        }
    }

    protected function generateThumbnail(ReferenceImage $image): void
    {
        // In production: use intervention/image or similar
        // For now: mark thumbnail as needed
        $image->setMetadata('thumbnail_needed', true);
    }

    protected function extractMetadata(ReferenceImage $image): void
    {
        $metadata = [
            'processed_at' => now()->toISOString(),
            'processor' => 'ProcessUploadJob',
        ];

        // Basic metadata
        if ($image->file_size) {
            $metadata['file_size'] = $image->file_size;
        }
        if ($image->mime_type) {
            $metadata['mime_type'] = $image->mime_type;
        }

        $image->setMetadata('extracted', $metadata);
    }

    protected function createEmbedding(ReferenceImage $image): void
    {
        $service = app(\App\Services\SemanticSearchService::class);
        
        $text = $image->title . ' ' . ($image->description ?? '');
        $service->indexEntity('reference', $image->id, $text);
        
        $image->setMetadata('embedding_created', true);
    }

    protected function linkToCanon(ReferenceImage $image, int $canonId): void
    {
        $image->update(['canon_entry_id' => $canonId]);
    }
}
