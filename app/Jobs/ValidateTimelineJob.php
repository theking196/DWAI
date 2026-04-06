<?php

namespace App\Jobs;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ValidateTimelineJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(
        public int $projectId,
        public bool $autoFix = false
    ) {}

    public function handle(): void
    {
        $project = Project::findOrFail($this->projectId);
        
        $service = app(\App\Services\AI\TimelineService::class);
        $issues = $service->validateTimeline($project->id);

        if (!empty($issues) && $this->autoFix) {
            $service->autoFixTimeline($project->id);
        }

        // Store validation results
        $project->updateMetadata('last_timeline_validation', [
            'issues' => $issues,
            'fixed' => $this->autoFix,
            'validated_at' => now()->toISOString(),
        ]);
    }
}
