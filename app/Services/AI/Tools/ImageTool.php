<?php

namespace App\Services\AI\Tools;

class ImageTool implements AIToolInterface
{
    public function getName(): string { return 'image'; }
    public function getDescription(): string { return 'Generate image'; }
    public function getInputSchema(): array {
        return ['type' => 'object', 'properties' => ['prompt' => ['type' => 'string']]];
    }
    public function execute(array $input, array $context = []): array {
        $ai = app(\App\Services\AI\AIService::class);
        return $ai->generateImage($input['prompt'] ?? 'Generate image', $context['references'] ?? []);
    }
}
