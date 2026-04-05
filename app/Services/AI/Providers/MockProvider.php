<?php

namespace App\Services\AI\Providers;

use Illuminate\Support\Str;

class MockProvider implements AIProviderInterface
{
    protected string $name = 'Mock Provider';
    protected bool $enabled = true;

    public function generateText(string $prompt, array $context = []): array
    {
        return [
            'success' => true,
            'content' => "Generated text for: " . substr($prompt, 0, 50) . "...\n\nThis is mock output. Configure a real AI provider to enable text generation.",
            'model' => 'mock-text-v1',
            'tokens' => Str::random(20),
            'metadata' => [
                'provider' => $this->name,
                'prompt_length' => strlen($prompt),
                'context_keys' => array_keys($context),
            ],
        ];
    }

    public function generateImage(string $prompt, array $references = []): array
    {
        return [
            'success' => true,
            'images' => [
                [
                    'url' => 'https://via.placeholder.com/512x512/333/fff?text=AI+Image',
                    'width' => 512,
                    'height' => 512,
                    'seed' => Str::random(8),
                ]
            ],
            'model' => 'mock-image-v1',
            'metadata' => [
                'provider' => $this->name,
                'prompt_length' => strlen($prompt),
                'reference_count' => count($references),
            ],
        ];
    }

    public function generateStoryboard(string $prompt, array $context = []): array
    {
        $frames = [];
        $frameCount = $context['frame_count'] ?? 4;

        for ($i = 0; $i < $frameCount; $i++) {
            $frames[] = [
                'frame_number' => $i + 1,
                'image_url' => 'https://via.placeholder.com/512x288/333/fff?text=Frame+' . ($i + 1),
                'description' => "Frame " . ($i + 1) . " of storyboard",
                'prompt' => $prompt . " - Frame " . ($i + 1),
            ];
        }

        return [
            'success' => true,
            'frames' => $frames,
            'total_frames' => count($frames),
            'model' => 'mock-storyboard-v1',
            'metadata' => [
                'provider' => $this->name,
                'prompt_length' => strlen($prompt),
            ],
        ];
    }

    public function isAvailable(): bool
    {
        return $this->enabled;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSupportedFeatures(): array
    {
        return [
            'text' => true,
            'image' => true,
            'storyboard' => true,
            'streaming' => false,
            'custom_models' => false,
        ];
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }
}
