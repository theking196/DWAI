<?php

namespace App\Services\AI\Tools;

class CanonTool implements AIToolInterface
{
    public function getName(): string { return 'canon'; }
    public function getDescription(): string { return 'Query or create canon entries'; }
    public function getInputSchema(): array {
        return ['type' => 'object', 'properties' => ['type' => ['type' => 'string'], 'query' => ['type' => 'string']]];
    }
    public function execute(array $input, array $context = []): array {
        $project = $context['project'] ?? null;
        if (!$project) return ['success' => false, 'error' => 'No project context'];
        $type = $input['type'] ?? 'lookup';
        if ($type === 'lookup' && !empty($input['query'])) {
            $results = \App\Models\CanonEntry::search(['project_id' => $project->id, 'keyword' => $input['query']])->limit(5)->get();
            return ['success' => true, 'canon' => $results->toArray()];
        }
        return ['success' => true, 'canon' => []];
    }
}
