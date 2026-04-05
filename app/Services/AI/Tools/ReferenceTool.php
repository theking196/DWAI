<?php

namespace App\Services\AI\Tools;

class ReferenceTool implements AIToolInterface
{
    public function getName(): string { return 'reference'; }
    public function getDescription(): string { return 'Get reference images'; }
    public function getInputSchema(): array {
        return ['type' => 'object', 'properties' => ['type' => ['type' => 'string']]];
    }
    public function execute(array $input, array $context = []): array {
        $project = $context['project'] ?? null;
        if (!$project) return ['success' => false, 'error' => 'No project'];
        $refs = \App\Models\ReferenceImage::forProject($project->id)->get();
        return ['success' => true, 'references' => $refs->map(fn($r) => ['url' => $r->url, 'title' => $r->title])->toArray()];
    }
}
