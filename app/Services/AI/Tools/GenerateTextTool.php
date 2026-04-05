<?php

namespace App\Services\AI\Tools;

class GenerateTextTool implements AIToolInterface
{
    public function getName(): string { return 'generate_text'; }
    public function getDescription(): string { return 'Generate text content with project context'; }
    public function getInputSchema(): array {
        return ['type' => 'object', 'properties' => ['prompt' => ['type' => 'string'], 'type' => ['type' => 'string'], 'length' => ['type' => 'string']]];
    }
    public function execute(array $input, array $context = []): array {
        $ai = app(\App\Services\AI\AIService::class);
        return $ai->generateText($input['prompt'] ?? 'Write something', $context);
    }
}
