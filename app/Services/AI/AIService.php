<?php

namespace App\Services\AI;

use App\Services\AI\Providers\AIProviderInterface;
use App\Services\AI\Providers\MockProvider;
use Illuminate\Support\Facades\Log;

class AIService
{
    protected array $providers = [];
    protected array $featureMap = [];
    protected bool $localOnly;

    public function __construct()
    {
        $this->localOnly = config('ai.local_only', true);
        $this->featureMap = config('ai.features', []);
        $this->initializeProviders();
    }

    /**
     * Initialize all configured providers.
     */
    protected function initializeProviders(): void
    {
        $providers = config('ai.providers', []);
        
        foreach ($providers as $name => $config) {
            if ($config['enabled'] ?? false) {
                $this->providers[$name] = $this->createProvider($name);
            }
        }
    }

    /**
     * Create provider instance.
     */
    protected function createProvider(string $name): AIProviderInterface
    {
        // Local-only mode blocks external providers
        if ($this->localOnly && $name !== 'mock') {
            Log::warning("AI: Provider {$name} blocked by local_only mode");
            return new MockProvider();
        }

        return match(strtolower($name)) {
            'mock' => new MockProvider(),
            // Future:
            // 'openai' => new OpenAIProvider(),
            // 'anthropic' => new AnthropicProvider(),
            // 'replicate' => new ReplicateProvider(),
            // 'stability' => new StabilityProvider(),
            default => new MockProvider(),
        };
    }

    /**
     * Get provider for a specific feature.
     */
    protected function getProviderForFeature(string $feature): AIProviderInterface
    {
        $featureConfig = $this->featureMap[$feature] ?? [];
        $providerName = $featureConfig['provider'] ?? config('ai.provider', 'mock');
        $fallback = $featureConfig['fallback'] ?? 'mock';

        // Check if provider is available
        if (isset($this->providers[$providerName])) {
            return $this->providers[$providerName];
        }

        // Try fallback
        if ($providerName !== $fallback && isset($this->providers[$fallback])) {
            return $this->providers[$fallback];
        }

        // Default to mock
        return new MockProvider();
    }

    /**
     * Generate text using the configured text provider.
     */
    public function generateText(string $prompt, array $context = []): array
    {
        $context = $this->prepareContext($context);
        $provider = $this->getProviderForFeature('text');

        Log::info('AI: Text generation', [
            'provider' => $provider->getName(),
            'prompt_length' => strlen($prompt),
        ]);

        $result = $provider->generateText($prompt, $context);
        
        if ($result['success']) {
            Log::info('AI: Text generated', ['model' => $result['model'] ?? 'unknown']);
        } else {
            Log::error('AI: Text generation failed', ['error' => $result['error'] ?? 'Unknown']);
        }

        return $result;
    }

    /**
     * Generate image using the configured image provider.
     */
    public function generateImage(string $prompt, array $references = []): array
    {
        $provider = $this->getProviderForFeature('image');

        Log::info('AI: Image generation', [
            'provider' => $provider->getName(),
            'prompt_length' => strlen($prompt),
            'reference_count' => count($references),
        ]);

        $result = $provider->generateImage($prompt, $references);

        if ($result['success']) {
            Log::info('AI: Image generated', ['image_count' => count($result['images'] ?? [])]);
        } else {
            Log::error('AI: Image generation failed', ['error' => $result['error'] ?? 'Unknown']);
        }

        return $result;
    }

    /**
     * Generate storyboard using the configured storyboard provider.
     */
    public function generateStoryboard(string $prompt, array $context = []): array
    {
        $context = $this->prepareContext($context);
        $provider = $this->getProviderForFeature('storyboard');

        Log::info('AI: Storyboard generation', [
            'provider' => $provider->getName(),
            'frame_count' => $context['frame_count'] ?? 4,
        ]);

        $result = $provider->generateStoryboard($prompt, $context);

        if ($result['success']) {
            Log::info('AI: Storyboard generated', ['frame_count' => $result['total_frames'] ?? 0]);
        } else {
            Log::error('AI: Storyboard generation failed', ['error' => $result['error'] ?? 'Unknown']);
        }

        return $result;
    }

    /**
     * Prepare context with project/session data.
     */
    protected function prepareContext(array $context): array
    {
        if (!isset($context['frame_count'])) {
            $context['frame_count'] = config('ai.defaults.storyboard.frame_count', 4);
        }

        // Add style images from project
        if (isset($context['project']) && $context['project']) {
            $project = $context['project'];
            
            $styleImages = [];
            if (!empty($project->style_image_path)) {
                $styleImages[] = ['url' => asset('storage/' . $project->style_image_path)];
            }
            if (!empty($project->style_images)) {
                foreach ($project->style_images as $img) {
                    if (!empty($img['path'])) {
                        $styleImages[] = ['url' => asset('storage/' . $img['path'])];
                    }
                }
            }
            if (!empty($styleImages)) {
                $context['style_images'] = $styleImages;
            }
        }

        // Add canon entries from session
        if (isset($context['session']) && $context['session']) {
            $session = $context['session'];
            
            if ($session->project) {
                $context['canon'] = $session->project->canonEntries()
                    ->select('id', 'title', 'type', 'content')
                    ->limit(10)
                    ->get()
                    ->toArray();
            }
        }

        return $context;
    }

    /**
     * Check if AI is available.
     */
    public function isAvailable(): bool
    {
        return !empty($this->providers);
    }

    /**
     * Get system info.
     */
    public function getInfo(): array
    {
        $textProvider = $this->getProviderForFeature('text');
        $imageProvider = $this->getProviderForFeature('image');
        $storyboardProvider = $this->getProviderForFeature('storyboard');

        return [
            'local_only' => $this->localOnly,
            'providers' => [
                'text' => $textProvider->getName(),
                'image' => $imageProvider->getName(),
                'storyboard' => $storyboardProvider->getName(),
            ],
            'features' => [
                'text' => $textProvider->getSupportedFeatures(),
                'image' => $imageProvider->getSupportedFeatures(),
                'storyboard' => $storyboardProvider->getSupportedFeatures(),
            ],
        ];
    }

    /**
     * Switch to a different provider.
     */
    public function setProvider(string $name): void
    {
        config(['ai.provider' => $name]);
    }

    /**
     * Set provider for specific feature.
     */
    public function setFeatureProvider(string $feature, string $provider): void
    {
        $features = config('ai.features', []);
        $features[$feature]['provider'] = $provider;
        config(['ai.features' => $features]);
    }
}
