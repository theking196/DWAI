<?php

namespace App\Http\Controllers\DWAI;

use App\Http\Controllers\Controller;
use App\Models\Session;
use App\Models\Project;
use App\Services\AI\AIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProgressiveSessionController extends Controller
{
    protected AIService $aiService;

    public function __construct()
    {
        $this->aiService = app(AIService::class);
    }

    /**
     * Main handler for progressive sessions.
     */
    public function handle(Request $request, int $sessionId)
    {
        $session = Session::findOrFail($sessionId);
        
        // Check session type
        if ($session->session_type !== 'progressive') {
            return response()->json([
                'error' => 'This session is not a progressive session',
                'session_type' => $session->session_type,
            ], 400);
        }

        $userInput = $request->input('input', '');
        $action = $request->input('action', 'next'); // next, refine, reset
        $feedback = $request->input('feedback', '');

        // Initialize build_steps if needed
        if (empty($session->build_steps)) {
            $this->initializeSteps($session, $userInput);
        }

        $currentIndex = $session->current_step_index ?? 0;
        $steps = $session->build_steps ?? [];
        $outputs = $session->build_outputs ?? [];
        
        // Get current step name from structured array
        $currentStep = is_array($steps[$currentIndex] ?? null) ? ($steps[$currentIndex]['name'] ?? null) : ($steps[$currentIndex] ?? null);
    }

    /**
     * Initialize build_steps based on input analysis.
     */
    protected function initializeSteps(Session $session, string $input): void
    {
        $wordCount = str_word_count($input);
        $steps = $this->generateStepsForComplexity($wordCount);
        
        // Convert to structured format with status
        $structuredSteps = array_map(fn($name) => [
            'name' => $name,
            'status' => 'pending',
        ], $steps);

        $session->update([
            'build_steps' => $structuredSteps,
            'current_step_index' => 0,
            'build_outputs' => [],
        ]);

        Log::info('Progressive steps initialized', ['session_id' => $session->id, 'steps' => count($structuredSteps), 'complexity' => $this->getComplexityLabel($wordCount)]);
    }

    /**
     * Generate steps based on input complexity.
     */
    protected function generateStepsForComplexity(int $wordCount): array
    {
        // Short input: < 30 words
        if ($wordCount < 30) {
            return [
                'Concept Development',
                'Main Visual',
                'Final Output',
            ];
        }

        // Medium input: 30-100 words
        if ($wordCount < 100) {
            return [
                'Concept Development',
                'Character Design',
                'Environment Building',
                'Key Scene',
                'Action Sequence',
                'Final Output',
            ];
        }

        // Long input: > 100 words
        return [
            'Concept Breakdown',
            'Multiple Characters',
            'Multiple Environments',
            'Scene Progression',
            'Cinematic Shots',
            'Emotional Tone',
            'Final Output',
        ];
    }

    /**
     * Get complexity label for logging.
     */
    protected function getComplexityLabel(int $wordCount): string
    {
        return match(true) {
            $wordCount < 30 => 'short',
            $wordCount < 100 => 'medium',
            default => 'long',
        };
    }

    /**
     * Handle "next" action - generate current step output.
     */
    protected function handleNext(Session $session, int $index, string $input, array $steps, array $outputs): void
    {
        $stepData = $steps[$index] ?? null;
        $currentStep = is_array($stepData) ? ($stepData['name'] ?? null) : $stepData;
        
        if (!$currentStep) {
            return;
        }

        // Build prompt for this step
        $prompt = $this->buildStepPrompt($currentStep, $index, $steps, $input, $session);
        
        try {
            $output = $this->aiService->generateText($session, $prompt);
            $result = $output->result ?? '';
            
            // Save output
            $outputs[$currentStep] = $result;
            
            // Update step status
            $steps[$index]['status'] = 'completed';
            
            $session->update([
                'build_steps' => $steps,
                'build_outputs' => $outputs,
            ]);
        } catch (\Exception $e) {
            Log::error('Step generation failed', ['step' => $currentStep, 'error' => $e->getMessage()]);
            $outputs[$currentStep] = "Error: " . $e->getMessage();
            $session->update(['build_outputs' => $outputs]);
        }
    }

    /**
     * Handle "refine" action - regenerate current step with feedback.
     */
    protected function handleRefine(Session $session, int $index, string $feedback, array $steps, array $outputs): void
    {
        $stepData = $steps[$index] ?? null;
        $currentStep = is_array($stepData) ? ($stepData['name'] ?? null) : $stepData;
        
        if (!$currentStep || empty($feedback)) {
            return;
        }

        // Build refinement prompt
        $previousOutput = $outputs[$currentStep] ?? '';
        $prompt = "Refine this {$currentStep} output based on feedback: {$feedback}\n\nPrevious output:\n{$previousOutput}";
        
        try {
            $output = $this->aiService->generateText($session, $prompt);
            $result = $output->result ?? $previousOutput;
            
            $outputs[$currentStep] = $result;
            $session->update(['build_outputs' => $outputs]);
        } catch (\Exception $e) {
            Log::error('Step refinement failed', ['step' => $currentStep, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Handle "reset" action.
     */
    protected function handleReset(Session $session): void
    {
        $session->update([
            'build_steps' => [],
            'current_step_index' => 0,
            'build_outputs' => [],
        ]);
    }

    /**
     * Build prompt for a specific step with full context.
     */
    protected function buildStepPrompt(string $step, int $index, array $allSteps, string $userInput, Session $session): string
    {
        $project = $session->project;
        $style = $project?->getVisualStyleDescription() ?? 'cinematic';
        
        // Get previous step outputs for continuity
        $previousOutputs = $this->getPreviousOutputs($allSteps, $index);
        
        // Get relevant canon entries
        $canon = $this->getRelevantCanon($project);
        
        // Get reference images
        $references = $this->getReferences($project);
        
        // Build contextual prompt
        $prompt = $this->buildContextualPrompt($step, $index, $userInput, $previousOutputs, $style, $canon, $references);
        
        return $prompt;
    }
    
    /**
     * Get outputs from all previous completed steps.
     */
    protected function getPreviousOutputs(array $steps, int $currentIndex): string
    {
        $outputs = [];
        
        for ($i = 0; $i < $currentIndex; $i++) {
            $stepData = $steps[$i] ?? null;
            $stepName = is_array($stepData) ? ($stepData['name'] ?? $i) : $stepData;
            if ($stepData['status'] ?? '' === 'completed') {
                $outputs[] = "Step {$i}: {$stepName}";
            }
        }
        
        return implode("\n", $outputs);
    }
    
    /**
     * Get relevant canon entries.
     */
    protected function getRelevantCanon(?Project $project): string
    {
        if (!$project) return '';
        
        $entries = $project->canonEntries()
            ->whereIn('importance', ['important', 'critical'])
            ->limit(5)
            ->get(['title', 'content']);
        
        if ($entries->isEmpty()) return '';
        
        return "EXISTING CANON:\n" . $entries->map(fn($e) => "- {$e->title}: " . substr($e->content, 0, 100))->implode("\n");
    }
    
    /**
     * Get reference images.
     */
    protected function getReferences(?Project $project): string
    {
        if (!$project) return '';
        
        $refs = $project->referenceImages()->limit(5)->get(['title', 'tags']);
        
        if ($refs->isEmpty()) return '';
        
        return "REFERENCE IMAGES:\n" . $refs->map(fn($r) => "- {$r->title} (" . implode(', ', $r->tags ?? []) . ")")->implode("\n");
    }
    
    /**
     * Build fully contextual prompt.
     */
    protected function buildContextualPrompt(string $step, int $index, string $userInput, string $previousOutputs, string $style, string $canon, string $references): string
    {
        $prompt = "CONTINUING BUILD STEP {$index}: {$step}\n\n";
        $prompt .= "ORIGINAL CONCEPT: {$userInput}\n\n";
        
        if ($previousOutputs) {
            $prompt .= "PREVIOUS STEPS COMPLETED:\n{$previousOutputs}\n\n";
        }
        
        if ($canon) {
            $prompt .= "{$canon}\n\n";
        }
        
        if ($references) {
            $prompt .= "{$references}\n\n";
        }
        
        $prompt .= "VISUAL STYLE: {$style}\n\n";
        
        // Add step-specific instructions that BUILD ON previous content
        $prompt .= match($step) {
            'Concept Development' => "EXPAND the concept: Build on any previous steps. Create a clear premise statement.",
            'Character Design' => "DESIGN characters: Consider the concept and previous steps. Include names, descriptions, motivations, visual traits.",
            'Environment Building' => "BUILD environment: Match the concept and characters. Describe locations with atmosphere and visual details.",
            'Key Scene' => "CREATE key scene: Use the characters and setting. Describe with camera angles, lighting, mood.",
            'Action Sequence' => "PLAN action: Build on previous character abilities and setting. Include movement, stakes.",
            'Final Output' => "COMPILE final production: Integrate ALL previous steps into a complete summary with image/video/music prompts.",
            'Concept Breakdown' => "BREAKDOWN concept: Expand the idea thoroughly. Build on any earlier steps.",
            'Multiple Characters' => "DEFINE multiple characters: Ensure consistency with concept. Names, roles, relationships.",
            'Multiple Environments' => "DESCRIBE environments: Match the story. Visual details, lighting, mood per location.",
            'Scene Progression' => "MAP scene progression: Build on characters/environments. Order events logically.",
            'Cinematic Shots' => "DESCRIBE cinematic shots: Use characters and scenes. Camera angles, movement, lighting.",
            'Emotional Tone' => "SET emotional tone: Match the story arc. Describe music mood, pacing, atmosphere.",
            default => "DEVELOP {$step}: Build on previous steps. Be consistent with established content.",
        };
        
        return $prompt;
    }

    /**
     * Build response for UI.
     */
    protected function buildResponse(Session $session, array $steps, array $outputs): array
    {
        $currentIndex = $session->current_step_index ?? 0;
        $currentStep = $steps[$currentIndex] ?? null;
        $isComplete = $currentIndex >= count($steps);

        // If complete, trigger final generations
        if ($isComplete && !$session->assistant_phase) {
            $this->triggerFinalGenerations($session, $outputs);
        }

        return [
            'session_type' => 'progressive',
            'current_step' => $currentStep,
            'current_step_index' => $currentIndex,
            'total_steps' => count($steps),
            'is_complete' => $isComplete,
            'steps' => $steps,
            'outputs' => $outputs,
            'current_output' => $currentStep ? ($outputs[$currentStep] ?? null) : null,
            'message' => $this->getStepMessage($currentStep, $isComplete),
            'available_actions' => $isComplete ? [] : ['next', 'refine'],
        ];
    }

    /**
     * Get user-friendly step message.
     */
    protected function getStepMessage(?string $step, bool $isComplete): string
    {
        if ($isComplete) {
            return "All steps complete! Generating final production prompts...";
        }

        return match($step) {
            'concept' => "Let's define your core concept first.",
            'characters' => "Now let's develop the characters.",
            'setting' => "Building the world...",
            'plot' => "Mapping out the story...",
            'conflict' => "What's at stake?",
            'resolution' => "How does it all end?",
            'emotion' => "What's the emotional journey?",
            'visuals' => "Creating visual descriptions...",
            'audio' => "Setting the mood with music...",
            'final' => "Compiling your production package...",
            default => "Working on {$step}...",
        };
    }

    /**
     * Trigger final generations when complete.
     */
    protected function triggerFinalGenerations(Session $session, array $outputs): void
    {
        // Set assistant phase to generate final outputs
        $session->update([
            'assistant_phase' => 'image_prompts',
            'assistant_structure' => $outputs,
        ]);
    }

    /**
     * Get current session state.
     */
    public function state(int $sessionId)
    {
        $session = Session::findOrFail($sessionId);
        
        if ($session->session_type !== 'progressive') {
            return response()->json(['error' => 'Not a progressive session'], 400);
        }

        return response()->json($this->buildResponse($session, $session->build_steps ?? [], $session->build_outputs ?? []));
    }

    /**
     * Move to next step.
     */
    public function next(Request $request, int $sessionId)
    {
        $request->merge(['action' => 'next']);
        return $this->handle($request, $sessionId);
    }

    /**
     * Refine current step.
     */
    public function refine(Request $request, int $sessionId)
    {
        $request->merge(['action' => 'refine']);
        return $this->handle($request, $sessionId);
    }
}
