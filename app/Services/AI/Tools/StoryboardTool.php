<?php

namespace App\Services\AI\Tools;

class StoryboardTool implements AIToolInterface
{
    public function getName(): string { return 'storyboard'; }
    public function getDescription(): string { return 'Generate storyboard frames'; }
    public function getInputSchema(): array {
        return ['type' => 'object', 'properties' => ['prompt' => ['type' => 'string'], 'frames' => ['type' => 'integer']]];
    }
    public function execute(array $input, array $context = []): array {
        $ai = app(\App\Services\AI\AIService::class);
        return $ai->generateStoryboard($input['prompt'] ?? 'Create storyboard', $context);
    }
}
