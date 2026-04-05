<?php

namespace App\Services\AI;

/**
 * AI Provider Interface
 * 
 * Defines the contract for AI generation services.
 * Implement this to add new AI providers (OpenAI, Anthropic, etc.)
 */
interface AIProvider
{
    /**
     * Generate text from a prompt.
     * 
     * @param string $prompt The input prompt
     * @param array $options Additional options (model, temperature, etc.)
     * @return array ['text' => string, 'model' => string, 'usage' => array]
     */
    public function generateText(string $prompt, array $options = []): array;
    
    /**
     * Generate an image from a prompt.
     * 
     * @param string $prompt The input prompt
     * @param array $references Reference image paths for style guidance
     * @param array $options Additional options (size, model, etc.)
     * @return array ['url' => string, 'prompt' => string, 'model' => string]
     */
    public function generateImage(string $prompt, array $references = [], array $options = []): array;
    
    /**
     * Get provider name.
     * 
     * @return string
     */
    public function getName(): string;
    
    /**
     * Check if provider is available/configured.
     * 
     * @return bool
     */
    public function isAvailable(): bool;
}
