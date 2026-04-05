<?php

namespace App\Services\AI;

use App\Models\Project;
use App\Models\Session;

/**
 * AI generation with automatic context injection.
 */
class AIWithContext
{
    protected AIService $ai;

    public function __construct(AIService $ai)
    {
        $this->ai = $ai;
    }

    /**
     * Generate text with full context.
     */
    public function generateText(string $prompt, ?Project $project = null, ?Session $session = null): array
    {
        $formattedPrompt = PromptFormatter::forText($prompt, $project, $session);
        
        return $this->ai->generateText($formattedPrompt, [
            'project' => $project,
            'session' => $session,
        ]);
    }

    /**
     * Generate image with style context.
     */
    public function generateImage(string $prompt, ?Project $project = null, ?Session $session = null): array
    {
        $context = PromptFormatter::forImage($prompt, $project, $session);
        
        return $this->ai->generateImage($context['combined_prompt'], $context['references']);
    }

    /**
     * Generate storyboard with style context.
     */
    public function generateStoryboard(string $prompt, ?Project $project = null, ?Session $session = null, int $frameCount = 4): array
    {
        $context = PromptFormatter::forStoryboard($prompt, $project, $session, $frameCount);
        
        return $this->ai->generateStoryboard($context['frame_prompts'][0] ?? $prompt, [
            'project' => $project,
            'session' => $session,
            'frame_count' => $frameCount,
            'style' => $context['style'],
            'references' => $context['references'],
        ]);
    }
}
