<?php

namespace App\Services\DWAI;

use App\Models\Conflict;
use App\Models\ActivityLog;
use App\Services\AI\ConflictDetectionService;

class ConflictService
{
    protected ConflictDetectionService $detector;

    public function __construct()
    {
        $this->detector = app(ConflictDetectionService::class);
    }

    public function getActiveConflicts(int $projectId): array
    {
        return Conflict::active($projectId)->toArray();
    }

    public function getAllConflicts(int $projectId): array
    {
        return Conflict::forProject($projectId)->toArray();
    }

    public function resolve(int $conflictId, ?string $notes = null): void
    {
        $conflict = Conflict::findOrFail($conflictId);
        $conflict->resolve($notes);
        ActivityLog::conflictResolved(auth()->id(), $conflict);
    }

    public function ignore(int $conflictId): void
    {
        $conflict = Conflict::findOrFail($conflictId);
        $conflict->ignore();
    }

    public function acknowledge(int $conflictId): void
    {
        $conflict = Conflict::findOrFail($conflictId);
        $conflict->acknowledge();
    }

    public function scanAndSync(int $projectId): int
    {
        return Conflict::syncFromDetection($projectId);
    }

    public function getSuggestion(int $conflictId): array
    {
        $conflict = Conflict::findOrFail($conflictId);
        $assistant = app(\App\Services\AI\ConflictResolutionAssistant::class);
        
        return [
            'suggestions' => $assistant->suggestResolution($conflict),
            'explanation' => $assistant->explainConflict($conflict),
        ];
    }
}
