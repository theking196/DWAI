<?php

namespace App\Services\AI\Tools;

class GenerateImageTool implements AIToolInterface
{
    public function getName(): string { return 'generate_image'; }
    public function getDescription(): string { return 'Generate image from text prompt with style references'; }
    public function getInputSchema(): array {
        return ['type' => 'object', 'properties' => ['prompt' => ['type' => 'string'], 'style' => ['type' => 'string'], 'variations' => ['type' => 'integer']]];
    }
    public function execute(array $input, array $context = []): array {
        $ai = app(\App\Services\AI\AIService::class);
        $refs = $context['references'] ?? [];
        return $ai->generateImage($input['prompt'] ?? 'Generate image', $refs);
    }
}
