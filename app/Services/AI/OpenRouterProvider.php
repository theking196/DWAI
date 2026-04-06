<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenRouterProvider implements AIProvider
{
    protected string $baseUrl = 'https://openrouter.ai/api/v1';
    protected ?string $apiKey;
    protected string $model;

    public function __construct()
    {
        $this->apiKey = config('services.openrouter.key');
        $this->model = config('services.openrouter.model', 'google/gemini-2.0-flash-001');
    }

    public function generateText(string $prompt, array $options = []): string
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post("{$this->baseUrl}/chat/completions", [
            'model' => $options['model'] ?? $this->model,
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
            'max_tokens' => $options['max_tokens'] ?? 1000,
            'temperature' => $options['temperature'] ?? 0.7,
        ]);

        if ($response->failed()) {
            Log::error('OpenRouter API error', ['response' => $response->body()]);
            throw new \Exception('OpenRouter API failed: ' . $response->body());
        }

        return $response->json('choices.0.message.content');
    }

    public function generateImage(string $prompt, array $options = []): array
    {
        // OpenRouter doesn't have image generation, use fallback
        return [
            ['url' => 'https://picsum.photos/512/512?random=' . rand(1, 1000)]
        ];
    }

    public function generateStoryboard(string $prompt, int $frameCount = 4, array $options = []): array
    {
        $text = $this->generateText($prompt . " Create {$frameCount} distinct scene descriptions.", $options);
        
        $frames = [];
        for ($i = 1; $i <= $frameCount; $i++) {
            $frames[] = [
                'frame_number' => $i,
                'description' => "Frame {$i}: Generated from prompt",
                'image_url' => 'https://picsum.photos/512/288?random=' . rand(1, 1000) . $i,
            ];
        }
        
        return ['frames' => $frames];
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    public function getName(): string
    {
        return 'openrouter';
    }
}
