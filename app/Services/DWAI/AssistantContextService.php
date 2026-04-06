<?php

namespace App\Services\DWAI;

use App\Models\Project;
use App\Models\CanonEntry;
use App\Models\ReferenceImage;
use App\Models\Session;

class AssistantContextService
{
    /**
     * Build complete context from session/project.
     */
    public function buildContext(Session $session): array
    {
        $project = $session->project;
        
        return [
            'session' => [
                'name' => $session->name,
                'idea' => $session->getAssistantIdea(),
                'refined_idea' => $session->getAssistantRefinedIdea(),
                'notes' => $session->temp_notes,
                'draft' => $session->draft_text ? substr($session->draft_text, 0, 500) : null,
                'references' => $session->session_references ?? [],
            ],
            'project' => $project ? [
                'name' => $project->name,
                'type' => $project->type,
                'description' => $project->description,
                'visual_style' => [
                    'description' => $project->getVisualStyleDescription(),
                    'images' => array_slice($project->getAllStyleReferences(), 0, 5),
                ],
            ] : null,
            'canon' => $this->getRelevantCanon($project),
            'references' => $this->getReferenceMetadata($project),
        ];
    }

    /**
     * Get relevant canon entries (important only).
     */
    public function getRelevantCanon(?Project $project): array
    {
        if (!$project) return [];
        
        return $project->canonEntries()
            ->whereIn('importance', ['important', 'critical'])
            ->limit(10)
            ->get()
            ->map(fn($c) => [
                'title' => $c->title,
                'type' => $c->type,
                'content' => substr($c->content, 0, 200),
            ])
            ->toArray();
    }

    /**
     * Get reference image metadata.
     */
    public function getReferenceMetadata(?Project $project): array
    {
        if (!$project) return [];
        
        return $project->referenceImages()
            ->limit(10)
            ->get()
            ->map(fn($r) => [
                'title' => $r->title,
                'url' => $r->url,
                'tags' => $r->tags,
            ])
            ->toArray();
    }

    /**
     * Build prompt with full context.
     */
    public function buildPrompt(string $type, array $context, string $userInput = ''): string
    {
        return match($type) {
            'refine' => $this->buildRefinePrompt($context, $userInput),
            'structure' => $this->buildStructurePrompt($context, $userInput),
            'image' => $this->buildImagePrompt($context, $userInput),
            'video' => $this->buildVideoPrompt($context, $userInput),
            'music' => $this->buildMusicPrompt($context, $userInput),
            default => $userInput,
        };
    }

    /**
     * Build refinement prompt.
     */
    protected function buildRefinePrompt(array $context, string $input): string
    {
        $project = $context['project'] ?? null;
        $canon = $context['canon'] ?? [];
        
        $prompt = "Refine this story concept: {$input}\n\n";
        
        if ($project) {
            $prompt .= "Project visual style: {$project['visual_style']['description']}\n";
        }
        
        if (!empty($canon)) {
            $prompt .= "Existing canon:\n";
            foreach (array_slice($canon, 0, 5) as $c) {
                $prompt .= "- {$c['title']} ({$c['type']}): {$c['content']}\n";
            }
        }
        
        $prompt .= "\nAdd detail, ask questions, or confirm ready.";
        
        return $prompt;
    }

    /**
     * Build structure prompt with cinematic language.
     */
    protected function buildStructurePrompt(array $context, string $input): string
    {
        $project = $context['project'] ?? null;
        $visualStyle = $project['visual_style']['description'] ?? 'cinematic, dramatic lighting';
        
        $idea = $context['session']['refined_idea'] ?? $context['session']['idea'] ?? $input;
        
        return "Create JSON story structure for: {$idea}

VISUAL STYLE: {$visualStyle}

REQUIREMENTS:
- Characters: name, description, visual_notes
- Environment: location, description, lighting, mood
- Mood: atmosphere, color_palette, overall_feel
- Scenes (3-5): title, description, camera_angle, lighting, key_moment

Camera angles: close-up, wide shot, tracking, pan, crane, handheld
Lighting: dramatic shadows, neon, golden hour, moody, backlight
Mood: dark, heroic, romantic, tense, epic";
    }

    /**
     * Build image prompt.
     */
    protected function buildImagePrompt(array $context, string $input): string
    {
        $structure = $context['session']['structure'] ?? [];
        $project = $context['project'] ?? null;
        $visualStyle = $project['visual_style']['description'] ?? 'cinematic, high quality, 4k';
        
        $scenes = $structure['scenes'] ?? [];
        $count = $scenes ? min(count($scenes), 6) : 4;
        
        $prompt = "Generate {$count} cinematic image prompts.\n\nSTYLE: {$visualStyle}\n\n";
        
        if (!empty($scenes)) {
            foreach ($scenes as $scene) {
                $camera = $scene['camera_angle'] ?? 'cinematic';
                $lighting = $scene['lighting'] ?? 'dramatic';
                $mood = $structure['mood']['atmosphere'] ?? 'dramatic';
                $prompt .= "- {$scene['description']} | Camera: {$camera}, Lighting: {$lighting}, Mood: {$mood}\n";
            }
        }
        
        $prompt .= "\nEach prompt: subject + setting + camera angle + lighting + mood + style.";
        
        return $prompt;
    }

    /**
     * Build video prompt.
     */
    protected function buildVideoPrompt(array $context, string $input): string
    {
        $imagePrompts = $context['session']['image_prompts'] ?? [];
        
        $prompt = "Convert " . count($imagePrompts) . " image prompts to video/motion prompts.\n\n";
        
        foreach ($imagePrompts as $p) {
            $prompt .= "- {$p}\n";
        }
        
        $prompt .= "\nAdd motion keywords: tracking shot, crane shot, handheld, smooth glide, slow motion, dramatic zoom, dolly shot.";
        
        return $prompt;
    }

    /**
     * Build music prompt.
     */
    protected function buildMusicPrompt(array $context, string $input): string
    {
        $structure = $context['session']['structure'] ?? [];
        $mood = is_array($structure) ? ($structure['mood']['atmosphere'] ?? 'cinematic') : 'cinematic';
        
        return "Music prompt for {$mood} story.

Include: genre, tempo, instrumentation, emotional arc.
Format as descriptive text for music AI.";
    }
}
