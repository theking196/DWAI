<?php

namespace App\Services\AI\Tools;

class GetProjectContextTool implements AIToolInterface
{
    public function getName(): string { return 'get_project_context'; }
    public function getDescription(): string { return 'Get project details, settings, style, and recent sessions'; }
    public function getInputSchema(): array {
        return ['type' => 'object', 'properties' => ['include_sessions' => ['type' => 'boolean'], 'include_canon' => ['type' => 'boolean']]];
    }
    public function execute(array $input, array $context = []): array {
        $project = $context['project'] ?? null;
        if (!$project) return ['success' => false, 'error' => 'No project context'];
        $data = ['id' => $project->id, 'name' => $project->name, 'description' => $project->description, 'type' => $project->type];
        if ($project->style_notes) $data['style_notes'] = $project->style_notes;
        if ($project->style_image_path) $data['style_image'] = asset('storage/' . $project->style_image_path);
        if (!empty($input['include_sessions'])) {
            $data['recent_sessions'] = $project->sessions()->orderBy('updated_at', 'desc')->limit(5)->get(['id', 'name', 'status', 'updated_at'])->toArray();
        }
        if (!empty($input['include_canon'])) {
            $data['canon_count'] = $project->canonEntries()->count();
        }
        return ['success' => true, 'context' => $data];
    }
}
