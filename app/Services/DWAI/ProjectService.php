<?php

namespace App\Services\DWAI;

use App\Models\Project;
use App\Models\ActivityLog;
use App\Models\ChangeHistory;

class ProjectService
{
    public function create(array $data): Project
    {
        $project = Project::create([
            'user_id' => auth()->id(),
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'type' => $data['type'] ?? 'story',
            'visual_style_description' => $data['visual_style_description'] ?? null,
        ]);

        ActivityLog::projectCreated(auth()->id(), $project);
        return $project;
    }

    public function update(Project $project, array $data): Project
    {
        $oldData = $project->getOriginal();
        $project->update($data);
        
        foreach ($data as $field => $newValue) {
            $oldValue = $oldData[$field] ?? null;
            if ($oldValue != $newValue) {
                ChangeHistory::recordProjectUpdate(auth()->id(), $project, [$field => $newValue]);
            }
        }

        return $project;
    }

    public function archive(Project $project): void
    {
        $project->update(['status' => 'archived', 'archived_at' => now()]);
    }

    public function getStats(Project $project): array
    {
        return [
            'sessions_count' => $project->sessions()->count(),
            'canon_count' => $project->canonEntries()->count(),
            'references_count' => $project->referenceImages()->count(),
            'active_sessions' => $project->sessions()->where('status', 'active')->count(),
        ];
    }
}
