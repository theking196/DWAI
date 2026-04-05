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
        $history = [];
        Log::info('AI Agent: Starting', ['prompt_length' => strlen($prompt)]);

        while ($this->currentIteration < $this->maxIterations) {
            $this->currentIteration++;
            $inspection = $this->inspectPrompt($prompt, $context, $history);
            $decision = $this->decideTool($inspection, $history);

            if ($decision['action'] === 'finish') {
                return ['success' => true, 'final_output' => $decision['output'], 'iterations' => $this->currentIteration, 'history' => $history];
            }

            $tool = $this->getTool($decision['tool']);
            if (!$tool) return ['success' => false, 'error' => "Tool not found: {$decision['tool']}"];

            $result = $tool->execute($decision['input'], $context);
            $resultInspection = $this->inspectResult($result, $history);
            $continuation = $this->shouldContinue($resultInspection, $history);

            $history[] = ['iteration' => $this->currentIteration, 'inspection' => $inspection, 'decision' => $decision, 'result' => $result, 'continuation' => $continuation];

            if (!$continuation['should_continue']) {
                return ['success' => $result['success'] ?? false, 'final_output' => $result, 'iterations' => $this->currentIteration, 'history' => $history];
            }

            $context['last_result'] = $result;
            $context['history'] = $history;
        }

        return ['success' => false, 'error' => 'Max iterations reached', 'iterations' => $this->currentIteration, 'history' => $history];
    }

    protected function inspectPrompt(string $prompt, array $context, array $history): array
    {
        $pl = strtolower($prompt);
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
                    'text', 'improve' => ['action' => 'use_tool', 'tool' => 'text', 'input' => []],
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
}
