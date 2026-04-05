<?php

namespace App\Services\AI;

use App\Models\Project;
use App\Models\Session;
use App\Models\AIOutput;
use App\Models\ReferenceImage;
use Illuminate\Support\Facades\Log;

/**
 * AI Service Manager
 * 
 * Main service for AI operations. Manages providers and handles
 * integration with database models.
 */
class AIService
{
    protected ?AIProvider $provider = null;
    protected string $defaultProvider = 'mock';
    
    /**
     * Get the active AI provider.
     */
    public function getProvider(string $provider = null): AIProvider
    {
        $provider = $provider ?? $this->defaultProvider;
        
        return match ($provider) {
            'mock' => new MockAIProvider(),
            default => new MockAIProvider(),
        };
    }
    
    /**
     * Set the default provider.
     */
    public function setDefaultProvider(string $provider): self
    {
        $this->defaultProvider = $provider;
        return $this;
    }
    
    /**
     * Generate text for a session.
     */
    public function generateText(
        Session $session,
        string $prompt,
        array $options = []
    ): AIOutput {
        $provider = $this->getProvider($options['provider'] ?? null);
        
        Log::info('AI Text Generation', [
            'session_id' => $session->id,
            'provider' => $provider->getName(),
            'prompt_length' => strlen($prompt),
        ]);
        
        $result = $provider->generateText($prompt, $options);
        
        // Save to database
        $output = AIOutput::create([
            'session_id' => $session->id,
            'prompt' => $prompt,
            'result' => $result['text'],
            'type' => 'text',
            'model' => $result['model'],
            'metadata' => $result['usage'] ?? null,
        ]);
        
        // Update session output count
        $session->increment('output_count');
        
        return $output;
    }
    
    /**
     * Generate image for a session.
     */
    public function generateImage(
        Session $session,
        string $prompt,
        array $options = []
    ): AIOutput {
        $provider = $this->getProvider($options['provider'] ?? null);
        
        // Get reference images from project
        $references = $session->project->referenceImages()
            ->pluck('path')
            ->toArray();
        
        Log::info('AI Image Generation', [
            'session_id' => $session->id,
            'provider' => $provider->getName(),
            'references_count' => count($references),
        ]);
        
        $result = $provider->generateImage($prompt, $references, $options);
        
        // Save to database
        $output = AIOutput::create([
            'session_id' => $session->id,
            'prompt' => $prompt,
            'result' => $result['url'],
            'type' => 'image',
            'model' => $result['model'],
            'metadata' => [
                'size' => $result['size'] ?? null,
                'references' => $references,
            ],
        ]);
        
        $session->increment('output_count');
        
        return $output;
    }
    
    /**
     * Get available providers.
     */
    public function getAvailableProviders(): array
    {
        return [
            'mock' => [
                'name' => 'Mock AI',
                'available' => true,
                'description' => 'Development provider for testing',
            ],
        ];
    }
}
