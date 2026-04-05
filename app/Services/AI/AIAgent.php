<?php

namespace App\Services\AI;

use App\Services\AI\Tools\AIToolInterface;
use App\Services\AI\Tools\TextTool;
use App\Services\AI\Tools\ImageTool;
use App\Services\AI\Tools\StoryboardTool;
use App\Services\AI\Tools\CanonTool;
use App\Services\AI\Tools\ReferenceTool;
use Illuminate\Support\Facades\Log;

class AIAgent
{
    protected array $tools = [];
    protected int $maxIterations = 10;
    protected int $currentIteration = 0;
    protected int $maxRetries = 2;
    protected array $errorLog = [];

    public function __construct() { $this->registerDefaultTools(); }

    protected function registerDefaultTools(): void
    {
        $this->register(new TextTool());
        $this->register(new ImageTool());
        $this->register(new StoryboardTool());
        $this->register(new CanonTool());
        $this->register(new ReferenceTool());
    }

    public function register(AIToolInterface $tool): void { $this->tools[$tool->getName()] = $tool; }
    public function getTools(): array { return $this->tools; }
    public function getTool(string $name): ?AIToolInterface { return $this->tools[$name] ?? null; }

    public function run(string $prompt, array $context = []): array
    {
        $this->currentIteration = 0;
        $this->errorLog = [];
        $history = [];
        
        Log::info('AI Agent: Starting', ['prompt_length' => strlen($prompt)]);

        while ($this->currentIteration < $this->maxIterations) {
            $this->currentIteration++;
            $inspection = $this->inspectPrompt($prompt, $context, $history);
            $decision = $this->decideTool($inspection, $history);

            if ($decision['action'] === 'finish') {
                return ['success' => true, 'final_output' => $decision['output'], 'iterations' => $this->currentIteration, 'history' => $history, 'errors' => $this->errorLog];
            }

            // Execute with retry logic
            $result = $this->executeWithRetry($decision['tool'], $decision['input'], $context);
            $resultInspection = $this->inspectResult($result, $history);

            // If failed, handle failure
            if (!$resultInspection['success']) {
                $strategy = $this->handleFailure($result, $decision, $history);
                
                if ($strategy['action'] === 'retry') {
                    $context['retry_count'] = ($context['retry_count'] ?? 0) + 1;
                    continue;
                }
                if ($strategy['action'] === 'fallback') {
                    $decision = $strategy['decision'];
                    $result = $this->executeWithRetry($decision['tool'], $decision['input'], $context);
                    $resultInspection = $this->inspectResult($result, $history);
                }
            }

            $continuation = $this->shouldContinue($resultInspection, $history);
            $history[] = ['iteration' => $this->currentIteration, 'inspection' => $inspection, 'decision' => $decision, 'result' => $result, 'continuation' => $continuation];

            if (!$continuation['should_continue']) {
                return ['success' => $result['success'] ?? false, 'final_output' => $result, 'iterations' => $this->currentIteration, 'history' => $history, 'errors' => $this->errorLog];
            }

            $context['last_result'] = $result;
            $context['history'] = $history;
        }

        return ['success' => false, 'error' => 'Max iterations reached', 'iterations' => $this->currentIteration, 'history' => $history, 'errors' => $this->errorLog];
    }

    /**
     * Execute tool with retry logic.
     */
    protected function executeWithRetry(string $toolName, array $input, array $context): array
    {
        $attempts = 0;
        
        while ($attempts <= $this->maxRetries) {
            $tool = $this->getTool($toolName);
            
            if (!$tool) {
                $this->logError('tool_not_found', "Tool not found: {$toolName}", []);
                return ['success' => false, 'error' => "Tool not found: {$toolName}"];
            }

            try {
                $result = $tool->execute($input, $context);
                
                if ($result['success'] ?? false) {
                    return $result;
                }
                
                if ($attempts < $this->maxRetries) {
                    $this->logError('tool_failed', "Attempt " . ($attempts + 1) . " failed", ['tool' => $toolName, 'error' => $result['error'] ?? 'Unknown']);
                    $attempts++;
                    continue;
                }
                
                return $result;
                
            } catch (\Exception $e) {
                $this->logError('exception', $e->getMessage(), ['tool' => $toolName]);
                
                if ($attempts < $this->maxRetries) {
                    $attempts++;
                    continue;
                }
                
                return ['success' => false, 'error' => $e->getMessage()];
            }
        }
        
        return ['success' => false, 'error' => 'Max retries exceeded'];
    }

    /**
     * Log error for debugging.
     */
    protected function logError(string $type, string $message, array $data): void
    {
        $error = ['type' => $type, 'message' => $message, 'data' => $data, 'iteration' => $this->currentIteration, 'timestamp' => now()->toISOString()];
        $this->errorLog[] = $error;
        Log::error("AI Agent Error: {$type}", $error);
    }

    /**
     * Handle failure - retry or fallback.
     */
    protected function handleFailure(array $result, array $decision, array $history): array
    {
        $this->logError('execution_failed', 'Tool execution failed', ['decision' => $decision, 'error' => $result['error'] ?? 'Unknown']);

        $retryCount = $history[count($history) - 1]['result']['retry_count'] ?? 0;
        
        if ($retryCount >= $this->maxRetries) {
            return $this->getFallbackStrategy($decision, $history);
        }

        return ['action' => 'retry'];
    }

    /**
     * Get fallback strategy when primary fails.
     */
    protected function getFallbackStrategy(array $originalDecision, array $history): array
    {
        $tool = $originalDecision['tool'];
        
        // If image failed, try text
        if ($tool === 'image') {
            return ['action' => 'fallback', 'decision' => ['action' => 'use_tool', 'tool' => 'text', 'input' => ['fallback' => true]]];
        }
        
        // If text failed, try simpler prompt
        if ($tool === 'text') {
            return ['action' => 'fallback', 'decision' => ['action' => 'use_tool', 'tool' => 'text', 'input' => ['simplified' => true]]];
        }
        
        return ['action' => 'finish', 'output' => ['success' => false, 'error' => 'All strategies failed', 'logged_errors' => $this->errorLog]];
    }

    protected function inspectPrompt(string $prompt, array $context, array $history): array
    {
        $intents = [];
        if (preg_match('/(write|create|generate|compose|story|text)/i', $prompt)) $intents[] = 'text';
        if (preg_match('/(image|picture|photo|draw)/i', $prompt)) $intents[] = 'image';
        if (preg_match('/(storyboard|frames?|sequence)/i', $prompt)) $intents[] = 'storyboard';
        if (preg_match('/(canon|character|lore|world)/i', $prompt)) $intents[] = 'canon';
        if (preg_match('/(reference|style|inspiration)/i', $prompt)) $intents[] = 'reference';
        if (preg_match('/(improve|edit|revise)/i', $prompt)) $intents[] = 'improve';

        return ['intents' => $intents, 'is_continuation' => !empty($history), 'complexity' => strlen($prompt) > 200 ? 'complex' : 'simple'];
    }

    protected function decideTool(array $inspection, array $history): array
    {
        foreach (['canon', 'reference', 'storyboard', 'image', 'text', 'improve'] as $intent) {
            if (in_array($intent, $inspection['intents'])) {
                return match($intent) {
                    'canon' => ['action' => 'use_tool', 'tool' => 'canon', 'input' => ['type' => 'lookup']],
                    'reference' => ['action' => 'use_tool', 'tool' => 'reference', 'input' => []],
                    'storyboard' => ['action' => 'use_tool', 'tool' => 'storyboard', 'input' => []],
                    'image' => ['action' => 'use_tool', 'tool' => 'image', 'input' => []],
                    'text', 'improve' => ['action' => => 'use_tool', 'tool' => 'text', 'input' => []],
                    default => ['action' => 'finish', 'output' => 'Unknown'],
                };
            }
        }
        return ['action' => 'use_tool', 'tool' => 'text', 'input' => []];
    }

    protected function inspectResult(array $result, array $history): array
    {
        return ['success' => $result['success'] ?? false, 'has_content' => !empty($result['content'] ?? $result['images'] ?? $result['frames'])];
    }

    protected function shouldContinue(array $resultInspection, array $history): array
    {
        if (!$resultInspection['success']) return ['should_continue' => false, 'reason' => 'Failed'];
        if (!$resultInspection['has_content']) return ['should_continue' => true, 'reason' => 'No content'];
        if (count($history) >= 3) return ['should_continue' => false, 'reason' => 'Max iterations'];
        return ['should_continue' => false, 'reason' => 'Complete'];
    }

    public function getErrorLog(): array { return $this->errorLog; }
}