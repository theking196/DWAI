<?php

namespace App\Services\AI\Tools;

class GetReferenceImagesTool implements AIToolInterface
{
    public function getName(): string { return 'get_reference_images'; }
    public function getDescription(): string { return 'Get reference images for project or session'; }
    public function getInputSchema(): array {
        return ['type' => 'object', 'properties' => ['type' => ['type' => 'string'], 'limit' => ['type' => 'integer']]];
    }
    public function execute(array $input, array $context = []): array {
        $project = $context['project'] ?? null;
        if (!$project) return ['success' => false, 'error' => 'No project'];
        $query = \App\Models\ReferenceImage::forProject($project->id);
        $session = $context['session'] ?? null;
        if ($session) $query->orWhere('session_id', $session->id);
        $refs = $query->limit($input['limit'] ?? 20)->get();
        return ['success' => true, 'references' => $refs->map(fn($r) => ['url' => $r->url, 'title' => $r->title, 'is_primary' => $r->is_primary])->toArray()];
    }
}
