<?php

namespace App\Services\AI\Tools;

class ValidateTimelineTool implements AIToolInterface
{
    public function getName(): string { return 'validate_timeline'; }
    public function getDescription(): string { return 'Validate timeline events for consistency'; }
    public function getInputSchema(): array {
        return ['type' => 'object', 'properties' => ['events' => ['type' => 'array']]];
    }
    public function execute(array $input, array $context = []): array {
        $project = $context['project'] ?? null;
        if (!$project) return ['success' => false, 'error' => 'No project'];
        $events = $project->canonEntries()->where('type', 'timeline_event')->orderBy('created_at')->get();
        $issues = [];
        foreach ($events as $i => $e) {
            if (!$e->content || strlen($e->content) < 5) $issues[] = ['id' => $e->id, 'issue' => 'Empty or too short'];
        }
        return ['success' => true, 'valid' => empty($issues), 'issues' => $issues, 'event_count' => $events->count()];
    }
}
