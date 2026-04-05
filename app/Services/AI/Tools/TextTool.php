<?php

namespace App\Services\AI\Tools;

class TextTool implements AIToolInterface
{
    public function getName(): string { return 'text'; }
    public function getDescription(): string { return 'Generate text content'; }
    public function getInputSchema(): array {
        return ['type' => 'object', 'properties' => ['prompt' => ['type' => 'string']]];
    }
    public function execute(array $input, array $context = []): array {
        $ai = app(\App\Services\AI\AIService::class);
        $prompt = $input['prompt'] ?? 'Write something';
        return $ai->generateText($prompt, $context);
    }
}
