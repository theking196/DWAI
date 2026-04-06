<?php

namespace App\Http\Controllers\DWAI;

use App\Http\Controllers\Controller;
use App\Models\Session;
use App\Models\Project;
use App\Models\AIOutput;
use App\Services\AI\AIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AssistantController extends Controller
{
    protected AIService $aiService;

    public function __construct()
    {
        $this->aiService = app(AIService::class);
    }

    /**
     * Main handler for Assistant Agent workflow.
     */
    public function handle(Request $request, int $sessionId)
    {
        $session = Session::findOrFail($sessionId);
        
        if ($session->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $input = $request->input('input', '');
        $phase = $session->getAssistantPhase();
        
        $result = match($phase) {
            'idea_input' => $this->handleIdeaInput($session, $input),
            'idea_refinement' => $this->handleRefining($session, $input),
            'structure_planning' => $this->handleStructuring($session, $input),
            'image_prompts' => $this->handleGeneratingImages($session, $input),
            'video_prompts' => $this->handleGeneratingVideo($session, $input),
            'music_prompt' => $this->handleGeneratingMusic($session, $input),
            'complete' => $this->handleComplete($session, $input),
            default => $this->handleUnknownPhase($session),
        };

        return response()->json($result);
    }

    /**
     * Phase: idea_input - Save initial idea and move to refining.
     */
    protected function handleIdeaInput(Session $session, string $input): array
    {
        if (empty($input)) {
            return [
                'message' => "Welcome to DWAI Assistant! I'm here to help you create your visual story. Tell me about your idea - what story do you want to tell?",
                'current_phase' => 'idea_input',
                'phase_label' => 'Idea Input',
                'generated_outputs' => null,
            ];
        }

        $session->setAssistantIdea($input);
        $session->setAssistantPhase('idea_refinement');
        
        return [
            'message' => "That's a great starting point! Tell me more about the characters, setting, and mood you want.",
            'current_phase' => 'idea_refinement',
            'phase_label' => 'Idea Refinement',
            'generated_outputs' => null,
        ];
    }

    /**
     * Phase: refining - Refine idea with follow-up.
     */
    protected function handleRefining(Session $session, string $input): array
    {
        if (empty($input)) {
            return [
                'message' => "Keep building! What's the emotional arc? Any visual references? Say 'done' when ready.",
                'current_phase' => 'idea_refinement',
                'phase_label' => 'Idea Refinement',
                'generated_outputs' => null,
            ];
        }

        $existing = $session->getAssistantIdea();
        $refined = $existing ? $existing . "\n" . $input : $input;
        $session->setAssistantRefinedIdea($refined);
        
        if (stripos($input, 'done') !== false || stripos($input, 'ready') !== false) {
            $session->setAssistantPhase('structure_planning');
            return [
                'message' => "Creating your story structure now...",
                'current_phase' => 'structure_planning',
                'phase_label' => 'Structure Planning',
                'generated_outputs' => null,
            ];
        }
        
        return [
            'message' => "Got it! Add more details, or say 'done' when ready to proceed.",
            'current_phase' => 'idea_refinement',
            'phase_label' => 'Idea Refinement',
            'generated_outputs' => null,
        ];
    }

    /**
     * Phase: structuring - Generate structured breakdown.
     */
    protected function handleStructuring(Session $session, string $input): array
    {
        $idea = $session->getAssistantRefinedIdea() ?? $session->getAssistantIdea();
        $project = $session->project;
        $style = $project ? $project->getVisualStyleDescription() : 'cinematic';
        
        $prompt = "Create a JSON structure for this story: {$idea}. Include: characters (name, desc), environment (locations), mood, scenes (3-5 key moments).";
        
        try {
            $output = $this->aiService->generateText($session, $prompt);
            $result = $output->result ?? '';
            
            $structure = json_decode($result, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $structure = ['raw' => $result, 'characters' => [], 'environment' => [], 'mood' => 'cinematic', 'scenes' => []];
            }
            
            $session->setAssistantStructure($structure);
            $session->setAssistantPhase('image_prompts');
            
            return [
                'message' => "Structure created! Now generating image prompts...",
                'current_phase' => 'image_prompts',
                'phase_label' => 'Image Prompts',
                'generated_outputs' => ['structure' => $structure],
            ];
        } catch (\Exception $e) {
            return ['message' => "Structure generation failed: " . $e->getMessage(), 'current_phase' => 'structure_planning', 'error' => true];
        }
    }

    /**
     * Phase: generating_images - Generate image prompts.
     */
    protected function handleGeneratingImages(Session $session, string $input): array
    {
        $project = $session->project;
        $style = $project ? $project->getVisualStyleDescription() : 'cinematic, high quality';
        
        $prompts = [
            "Cinematic hero shot, {$style}, dramatic lighting",
            "Epic environment, {$style}, wide angle",
            "Emotional scene, {$style}, close-up",
            "Action moment, {$style}, dynamic",
        ];
        
        $session->setAssistantImagePrompts($prompts);
        $session->setAssistantPhase('video_prompts');
        
        return [
            'message' => count($prompts) . " image prompts created! Converting to video prompts...",
            'current_phase' => 'video_prompts',
            'phase_label' => 'Video Prompts',
            'generated_outputs' => ['image_prompts' => $prompts],
        ];
    }

    /**
     * Phase: generating_video - Convert to video prompts.
     */
    protected function handleGeneratingVideo(Session $session, string $input): array
    {
        $imagePrompts = $session->getAssistantImagePrompts();
        
        $videoPrompts = array_map(fn($p) => str_replace('image', 'video, smooth motion', $p) . ", cinematic movement", $imagePrompts);
        
        $session->setAssistantVideoPrompts($videoPrompts);
        $session->setAssistantPhase('music_prompt');
        
        return [
            'message' => count($videoPrompts) . " video prompts ready! Creating music prompt...",
            'current_phase' => 'music_prompt',
            'phase_label' => 'Music Prompt',
            'generated_outputs' => ['video_prompts' => $videoPrompts],
        ];
    }

    /**
     * Phase: generating_music - Generate music prompt.
     */
    protected function handleGeneratingMusic(Session $session, string $input): array
    {
        $structure = $session->getAssistantStructure();
        $mood = is_array($structure) ? ($structure['mood'] ?? 'cinematic') : 'cinematic';
        
        $musicPrompt = "Cinematic {$mood} orchestral music, moderate tempo, emotional arc, drums and strings";
        
        $session->setAssistantMusicPrompt($musicPrompt);
        $session->setAssistantPhase('complete');
        
        return [
            'message' => "Complete! Your workflow is done:\n- Story structure\n- " . count($session->getAssistantImagePrompts()) . " image prompts\n- " . count($session->getAssistantVideoPrompts()) . " video prompts\n- Music prompt",
            'current_phase' => 'complete',
            'phase_label' => 'Complete',
            'generated_outputs' => [
                'music_prompt' => $musicPrompt,
                'full_state' => $session->getAssistantState(),
            ],
        ];
    }

    /**
     * Phase: complete.
     */
    protected function handleComplete(Session $session, string $input): array
    {
        return [
            'message' => "Workflow complete! Start over or use the prompts.",
            'current_phase' => 'complete',
            'phase_label' => 'Complete',
            'generated_outputs' => ['full_state' => $session->getAssistantState()],
        ];
    }

    protected function handleUnknownPhase(Session $session): array
    {
        $session->setAssistantPhase('idea_input');
        return ['message' => "Let's start fresh!", 'current_phase' => 'idea_input', 'phase_label' => 'Idea Input'];
    }

    /**
     * Get state.
     */
    public function state(int $sessionId)
    {
        $session = Session::findOrFail($sessionId);
        if ($session->user_id !== auth()->id()) return response()->json(['error' => 'Unauthorized'], 403);
        return response()->json($session->getAssistantState());
    }

    /**
     * Reset.
     */
    public function reset(int $sessionId)
    {
        $session = Session::findOrFail($sessionId);
        if ($session->user_id !== auth()->id()) return response()->json(['error' => 'Unauthorized'], 403);
        $session->resetAssistantState();
        return response()->json(['reset' => true]);
    }
}
