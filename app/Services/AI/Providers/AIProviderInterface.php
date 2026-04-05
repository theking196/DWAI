<?php

namespace App\Services\AI\Providers;

interface AIProviderInterface
{
    /**
     * Generate text content.
     */
    public function generateText(string $prompt, array $context = []): array;

    /**
     * Generate image(s) from prompt.
     */
    public function generateImage(string $prompt, array $references = []): array;

    /**
     * Generate storyboard (sequence of images/frames).
     */
    public function generateStoryboard(string $prompt, array $context = []): array;

    /**
     * Check if provider is available.
     */
    public function isAvailable(): bool;

    /**
     * Get provider name.
     */
    public function getName(): string;

    /**
     * Get supported features.
     */
    public function getSupportedFeatures(): array;
}
