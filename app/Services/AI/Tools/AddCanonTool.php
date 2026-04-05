<?php

namespace App\Services\AI\Tools;

class AddCanonTool implements AIToolInterface
{
    public function getName(): string { return 'add_canon'; }
    public function getDescription(): string { return 'Create a new canon entry'; }
    public function getInputSchema(): array {
        return ['type' => 'object', 'properties' => ['title' => ['type' => 'string'], 'type' => ['type' => 'string'], 'content' => ['type' => 'string'], 'tags' => ['type' => 'array'], 'importance' => ['type' => 'string']]];
    }
    public function execute(array $input, array $context = []): array {
        $project = $context['project'] ?? null;
        if (!$project) return ['success' => false, 'error' => 'No project context'];
        $entry = \App\Models\CanonEntry::create(['user_id' => auth()->id(), 'project_id' => $project->id, 'title' => $input['title'], 'type' => $input['type'] ?? 'note', 'content' => $input['content'] ?? null, 'tags' => $input['tags'] ?? [], 'importance' => $input['importance'] ?? 'none']);
        return ['success' => true, 'entry' => $entry->toArray()];
    }
}
