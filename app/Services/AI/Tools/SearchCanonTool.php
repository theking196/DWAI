<?php

namespace App\Services\AI\Tools;

class SearchCanonTool implements AIToolInterface
{
    public function getName(): string { return 'search_canon'; }
    public function getDescription(): string { return 'Search canon entries by keyword, type, or tag'; }
    public function getInputSchema(): array {
        return ['type' => 'object', 'properties' => ['keyword' => ['type' => 'string'], 'type' => ['type' => 'string'], 'tag' => ['type' => 'string'], 'limit' => ['type' => 'integer']]];
    }
    public function execute(array $input, array $context = []): array {
        $project = $context['project'] ?? null;
        if (!$project) return ['success' => false, 'error' => 'No project context'];
        $params = ['project_id' => $project->id, 'keyword' => $input['keyword'] ?? '', 'type' => $input['type'] ?? null, 'tag' => $input['tag'] ?? null];
        $results = \App\Models\CanonEntry::search($params)->limit($input['limit'] ?? 10)->get();
        return ['success' => true, 'results' => $results->toArray(), 'count' => $results->count()];
    }
}
