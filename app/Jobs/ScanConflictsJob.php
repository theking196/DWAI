<?php

namespace App\Jobs;

use App\Models\Project;
use App\Models\Conflict;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ScanConflictsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(
        public int $projectId,
        public bool $createRecords = true
    ) {}

    public function handle(): void
    {
        $project = Project::findOrFail($this->projectId);
        
        $service = app(\App\Services\AI\ConflictDetectionService::class);
        $conflicts = $service->detectAllConflicts($project->id);

        if ($this->createRecords) {
            Conflict::syncFromDetection($project->id);
        }

        // Store scan results
        $project->updateMetadata('last_conflict_scan', [
            'conflicts' => $conflicts,
            'scanned_at' => now()->toISOString(),
        ]);
    }
}
