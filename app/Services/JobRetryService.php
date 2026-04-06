<?php

namespace App\Services;

use App\Models\AIOutput;
use App\Models\ReferenceImage;
use Illuminate\Support\Facades\Log;

class JobRetryService
{
    /**
     * Handle job failure - log, mark, determine retry.
     */
    public function handleFailure(
        string $jobType,
        int $entityId,
        \Throwable $e,
        int $attempt,
        int $maxRetries
    ): array {
        $willRetry = $attempt < $maxRetries;
        
        // Log the failure
        Log::error("Job failed: {$jobType}/{$entityId}", [
            'attempt' => $attempt,
            'max_retries' => $maxRetries,
            'will_retry' => $willRetry,
            'error' => $e->getMessage(),
        ]);

        // Mark entity as failed
        $this->markEntityFailed($jobType, $entityId, $e->getMessage());

        return [
            'retry' => $willRetry,
            'attempt' => $attempt,
            'max_retries' => $maxRetries,
            'next_retry_after' => $willRetry ? $this->getBackoffSeconds($attempt) : null,
        ];
    }

    /**
     * Mark entity as failed.
     */
    protected function markEntityFailed(string $jobType, int $entityId, string $error): void
    {
        match($jobType) {
            'text', 'image', 'storyboard', 'output' => $this->markOutputFailed($entityId, $error),
            'upload', 'reference' => $this->markReferenceFailed($entityId, $error),
            default => null,
        };
    }

    protected function markOutputFailed(int $outputId, string $error): void
    {
        $output = AIOutput::find($outputId);
        if ($output) {
            $output->update([
                'status' => 'failed',
                'error_message' => $error,
            ]);
            $output->setMetadata('failure_count', ($output->getMetadata('failure_count', 0) ?? 0) + 1);
        }
    }

    protected function markReferenceFailed(int $refId, string $error): void
    {
        $ref = ReferenceImage::find($refId);
        if ($ref) {
            $ref->setMetadata('processing_error', $error);
            $ref->setMetadata('processing_status', 'failed');
        }
    }

    /**
     * Get backoff seconds for retry.
     */
    protected function getBackoffSeconds(int $attempt): int
    {
        // Exponential backoff: 30s, 60s, 120s...
        return 30 * pow(2, $attempt - 1);
    }

    /**
     * Get status for UI.
     */
    public function getJobStatus(string $jobType, int $entityId): array
    {
        return match($jobType) {
            'text', 'image', 'storyboard', 'output' => $this->getOutputStatus($entityId),
            'upload', 'reference' => $this->getReferenceStatus($entityId),
            default => ['status' => 'unknown'],
        };
    }

    protected function getOutputStatus(int $outputId): array
    {
        $output = AIOutput::find($outputId);
        
        if (!$output) {
            return ['status' => 'not_found'];
        }

        return [
            'status' => $output->status,
            'error' => $output->error_message,
            'failure_count' => $output->getMetadata('failure_count', 0),
            'created_at' => $output->created_at->toISOString(),
            'completed_at' => $output->updated_at->toISOString(),
        ];
    }

    protected function getReferenceStatus(int $refId): array
    {
        $ref = ReferenceImage::find($refId);
        
        if (!$ref) {
            return ['status' => 'not_found'];
        }

        return [
            'status' => $ref->getMetadata('processing_status', 'pending'),
            'error' => $ref->getMetadata('processing_error'),
            'created_at' => $ref->created_at->toISOString(),
        ];
    }

    /**
     * Retry a failed job.
     */
    public function retryJob(string $jobType, int $entityId): bool
    {
        $service = app(\App\Services\DWJobs::class);

        return match($jobType) {
            'text' => $this->retryOutputJob($entityId, 'text'),
            'image' => $this->retryOutputJob($entityId, 'image'),
            'upload' => $this->retryUpload($entityId),
            default => false,
        };
    }

    protected function retryOutputJob(int $outputId, string $type): bool
    {
        $output = AIOutput::find($outputId);
        
        if (!$output) {
            return false;
        }

        $output->update(['status' => 'pending', 'error_message' => null]);

        if ($type === 'text') {
            DWJobs::generateText($output->session_id, $output->prompt);
        } else {
            DWJobs::generateImage($output->session_id, $output->prompt);
        }

        return true;
    }

    protected function retryUpload(int $refId): bool
    {
        $ref = ReferenceImage::find($refId);
        
        if (!$ref) {
            return false;
        }

        $ref->setMetadata('processing_status', 'pending');
        $ref->unsetMetadata('processing_error');

        \App\Jobs\ProcessUploadJob::dispatch($refId);

        return true;
    }
}
