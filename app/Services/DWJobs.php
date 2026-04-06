<?php

namespace App\Services;

use App\Jobs\GenerateTextJob;
use App\Jobs\GenerateImageJob;
use App\Jobs\UpdateEmbeddingsJob;
use App\Jobs\ValidateTimelineJob;
use App\Jobs\ScanConflictsJob;

class DWJobs
{
    /**
     * Queue text generation.
     */
    public static function generateText(int $sessionId, string $prompt, array $options = []): void
    {
        GenerateTextJob::dispatch($sessionId, $prompt, $options);
    }

    /**
     * Queue image generation.
     */
    public static function generateImage(int $sessionId, string $prompt, array $options = []): void
    {
        GenerateImageJob::dispatch($sessionId, $prompt, $options);
    }

    /**
     * Queue embedding updates.
     */
    public static function updateEmbeddings(int $userId, string $type, ?int $entityId = null, ?int $projectId = null): void
    {
        UpdateEmbeddingsJob::dispatch($userId, $type, $entityId, $projectId);
    }

    /**
     * Queue timeline validation.
     */
    public static function validateTimeline(int $projectId, bool $autoFix = false): void
    {
        ValidateTimelineJob::dispatch($projectId, $autoFix);
    }

    /**
     * Queue conflict scanning.
     */
    public static function scanConflicts(int $projectId, bool $createRecords = true): void
    {
        ScanConflictsJob::dispatch($projectId, $createRecords);
    }
}
