<?php

namespace App\Http\Responses\DWAI;

use Illuminate\Http\JsonResponse;

class ConflictResponse
{
    public static function list($conflicts, int $count = 0): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'conflicts' => $conflicts->map(fn($c) => [
                'id' => $c->id,
                'type' => $c->type,
                'description' => $c->description,
                'severity' => $c->severity,
                'status' => $c->status,
                'suggested_fix' => $c->suggested_fix,
                'created_at' => $c->created_at->toISOString(),
            ]),
            'summary' => [
                'total' => $count,
                'by_severity' => [
                    'error' => $conflicts->where('severity', 'error')->count(),
                    'warning' => $conflicts->where('severity', 'warning')->count(),
                    'info' => $conflicts->where('severity', 'info')->count(),
                ],
                'by_status' => [
                    'detected' => $conflicts->where('status', 'detected')->count(),
                    'acknowledged' => $conflicts->where('status', 'acknowledged')->count(),
                    'resolved' => $conflicts->where('status', 'resolved')->count(),
                    'ignored' => $conflicts->where('status', 'ignored')->count(),
                ],
            ],
        ]);
    }

    public static function resolved($conflict): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Conflict resolved',
            'conflict' => [
                'id' => $conflict->id,
                'status' => 'resolved',
                'resolution_notes' => $conflict->suggested_fix,
            ],
        ]);
    }

    public static function ignored($conflict): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Conflict ignored',
            'conflict' => ['id' => $conflict->id, 'status' => 'ignored'],
        ]);
    }

    public static function suggestions($conflict, $assistant): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'conflict' => [
                'id' => $conflict->id,
                'type' => $conflict->type,
                'description' => $conflict->description,
            ],
            'suggestions' => $assistant->suggestResolution($conflict),
            'explanation' => $assistant->explainConflict($conflict),
        ]);
    }
}
