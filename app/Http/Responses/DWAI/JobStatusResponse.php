<?php

namespace App\Http\Responses\DWAI;

use Illuminate\Http\JsonResponse;

class JobStatusResponse
{
    public static function pending(string $jobType, int $entityId): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'job' => [
                'type' => $jobType,
                'entity_id' => $entityId,
                'state' => 'pending',
                'message' => 'Job queued for processing',
            ],
        ]);
    }

    public static function processing(string $jobType, int $entityId, int $progress = 0): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'job' => [
                'type' => $jobType,
                'entity_id' => $entityId,
                'state' => 'processing',
                'progress' => $progress,
                'message' => 'Job in progress',
            ],
        ]);
    }

    public static function completed(string $jobType, $result): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'job' => [
                'type' => $jobType,
                'state' => 'completed',
                'result' => $result,
            ],
        ]);
    }

    public static function failed(string $jobType, string $error): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'job' => [
                'type' => $jobType,
                'state' => 'failed',
                'error' => $error,
            ],
        ], 500);
    }

    public static function fromOutput($output): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'job' => [
                'id' => $output->id,
                'type' => $output->type,
                'state' => $output->status,
                'progress' => $output->status === 'processing' ? 50 : ($output->status === 'completed' ? 100 : 0),
                'error' => $output->error_message,
                'result' => $output->status === 'completed' ? $output->getResultPreview() : null,
                'created_at' => $output->created_at->toISOString(),
            ],
        ]);
    }
}
