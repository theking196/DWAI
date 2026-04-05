<?php

namespace App\Services\AI;

use Illuminate\Support\Str;

/**
 * Mock AI Provider
 * 
 * Development provider that simulates AI responses.
 * Replace with real provider implementations for production.
 */
class MockAIProvider implements AIProvider
{
    protected string $name = 'Mock AI';
    protected bool $available = true;
    
    /**
     * Simulated text generation.
     */
    public function generateText(string $prompt, array $options = []): array
    {
        $model = $options['model'] ?? 'gpt-4';
        
        // Simulate processing delay
        $responses = [
            'character' => "Character created: A young hero named " . Str::random(8) . " with extraordinary abilities...",
            'script' => "INT. LAGOS APARTMENT - DAY\n\nOur hero wakes from a vivid dream...",
            'storyboard' => "Panel 1: Wide shot of Lagos skyline at sunset.\nPanel 2: Close-up on hero's face...",
            'description' => "A dramatic scene set in modern Lagos, with vibrant colors and dynamic lighting...",
        ];
        
        // Find matching response type
        $response = "AI Response to: {$prompt}\n\n" . collect($responses)->random();
        
        return [
            'text' => $response,
            'model' => $model,
            'usage' => [
                'prompt_tokens' => str_word_count($prompt),
                'completion_tokens' => str_word_count($response),
                'total_tokens' => str_word_count($prompt) + str_word_count($response),
            ],
            'created_at' => now()->toISOString(),
        ];
    }
    
    /**
     * Simulated image generation.
     */
    public function generateImage(string $prompt, array $references = [], array $options = []): array
    {
        $size = $options['size'] ?? '1024x1024';
        $model = $options['model'] ?? 'dall-e-3';
        
        // Generate placeholder image URL
        // In production, this would call real AI image API
        $seed = Str::random(8);
        $placeholderUrl = "https://picsum.photos/seed/{$seed}/512/512";
        
        return [
            'url' => $placeholderUrl,
            'prompt' => $prompt,
            'model' => $model,
            'size' => $size,
            'references' => $references,
            'created_at' => now()->toISOString(),
        ];
    }
    
    public function getName(): string
    {
        return $this->name;
    }
    
    public function isAvailable(): bool
    {
        return $this->available;
    }
}
