<?php

namespace App\Services\AI\Tools;

class UpdateCanonTool implements AIToolInterface
{
    public function getName(): string { return 'update_canon'; }
    public function getDescription(): string { return 'Update an existing canon entry'; }
    public function getInputSchema(): array {
        return ['type' => 'object', 'properties' => ['id' => ['type' => 'integer'], 'title' => ['type' => 'string'], 'content' => ['type' => 'string'], 'tags' => ['type' => 'array']]];
    }
    public function execute(array $input, array $context = []): array {
        $entry = \App\Models\CanonEntry::find($input['id'] ?? 0);
        if (!$entry) return ['success' => false, 'error' => 'Canon entry not found'];
        if ($entry->user_id !== auth()->id()) return ['success' => false, 'error' => 'Unauthorized'];
        $entry->update(array_filter(['title' => $input['title'] ?? null, 'content' => $input['content'] ?? null, 'tags' => $input['tags'] ?? null]));
        return ['success' => true, 'entry' => $entry->fresh()->toArray()];
    }
}
