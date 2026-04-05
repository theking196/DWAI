<?php

namespace App\Services\AI;

use App\Services\AI\Providers\AIProviderInterface;
use App\Services\AI\Providers\MockProvider;
use Illuminate\Support\Facades\Log;

class AIService
{
    protected ?AIProviderInterface $provider = null;
    protected string $defaultProvider = 'mock';

    public function __construct()
    {
        $this->provider = $this->resolveProvider();
    }

    /**
     * Resolve the active provider.
     */
    protected function resolveProvider(): AIProviderInterface
    {
        // Load from config or env
        $providerName = config('ai.provider', $this->defaultProvider);

        return $this->createProvider($providerName);
    }

    /**
     * Create a provider instance.
     */
    protected function createProvider(string $name): AIProviderInterface
    {
        return match(strtolower($name)) {
            'mock' => new MockProvider(),
            // Future providers:
            // 'openai' => new OpenAIProvider(),
            // 'anthropic' => new AnthropicProvider(),
            // 'replicate' => new ReplicateProvider(),
            // 'stability' => new StabilityProvider(),
            default => new MockProvider(),
        };
    }

    /**
     * Get the current provider.
     */
    public function getProvider(): AIProviderInterface
    {
        return $this->provider;
    }

    /**
     * Switch provider at runtime.
     */
    public function setProvider(string $name): void
    {
        $this->provider = $this->createProvider($name);
    }

    /**
     * Generate text content.
     * 
     * @param string $prompt The main prompt
     * @param array $context Context data (project, session, canon, references)
     * @return array Result with content, model, metadata
     */
    public function generateText(string $prompt, array $context = []): array
    {
        $context = $this->prepareContext($context);
        
        Log::info('AI: Generating text', [
            'prompt_length' => strlen($prompt),
            'context_keys' => array_keys($context),
        ]);

        $result = $this->provider->generateText($prompt, $context);

        if ($result['success']) {
            Log::info('AI: Text generated', ['model' => $result['model']]);
        } else {
            Log::error('AI: Text generation failed', ['error' => $result['error'] ?? 'Unknown']);
        }

        return $result;
    }

    /**
     * Generate image(s) from prompt.
     * 
     * @param string $prompt Image description
     * @param array $references Reference images/URLs
     * @return array Result with images array
     */
    public function generateImage(string $prompt, array $references = []): array
    {
        Log::info('AI: Generating image', [
            'prompt_length' => strlen($prompt),
            'reference_count' => count($references),
        ]);

        $result = $this->provider->generateImage($prompt, $references);

        if ($result['success']) {
            Log::info('AI: Image generated', [
                'image_count' => count($result['images'] ?? []),
                'model' => $result['model'],
            ]);
        } else {
            Log::error('AI: Image generation failed', ['error' => $result['error'] ?? 'Unknown']);
        }

        return $result;
    }

    /**
     * Generate storyboard with multiple frames.
     * 
     * @param string $prompt Storyboard description
     * @param array $context Context (frame_count, style, etc.)
     * @return array Result with frames array
     */
    public function generateStoryboard(string $prompt, array $context = []): array
    {
        $context = $this->prepareContext($context);

        Log::info('AI: Generating storyboard', [
            'prompt_length' => strlen($prompt),
            'frame_count' => $context['frame_count'] ?? 4,
        ]);

        $result = $this->provider->generateStoryboard($prompt, $context);

        if ($result['success']) {
            Log::info('AI: Storyboard generated', [
                'frame_count' => $result['total_frames'],
                'model' => $result['model'],
            ]);
        } else {
            Log::error('AI: Storyboard generation failed', ['error' => $result['error'] ?? 'Unknown']);
        }

        return $result;
    }

    /**
     * Prepare context by enriching with project/session data.
     */
    protected function prepareContext(array $context): array
    {
        // Add default frame count if not set
        if (!isset($context['frame_count'])) {
            $context['frame_count'] = 4;
        }

        // Add style references if project provided
        if (isset($context['project']) && $context['project']) {
            $project = $context['project'];
            
            if ($project->style_image_path) {
                $context['style_images'] = [
                    ['url' => asset('storage/' . $project->style_image_path)]
                ];
            }

            if ($project->style_images) {
                $context['style_images'] = array_merge(
                    $context['style_images'] ?? [],
                    array_map(fn($img) => ['url' => asset('storage/' . $img['path'])], $project->style_images)
                );
            }
        }

        // Add canon entries if session provided
        if (isset($context['session']) && $context['session']) {
            $session = $context['session'];
            
            if ($session->project && $session->project->canonEntries) {
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
        return $this->provider->isAvailable();
    }

    /**
     * Get provider info.
     */
    public function getInfo(): array
    {
        return [
            'provider' => $this->provider->getName(),
            'available' => $this->provider->isAvailable(),
            'features' => $this->provider->getSupportedFeatures(),
        ];
    }
}
