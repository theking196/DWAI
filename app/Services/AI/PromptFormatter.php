<?php

namespace App\Services\AI;

use App\Models\Project;
use App\Models\Session;
use App\Models\CanonEntry;
use App\Models\ReferenceImage;

class PromptFormatter
{
    /**
     * Format full context for text generation.
     */
    public static function forText(string $basePrompt, ?Project $project = null, ?Session $session = null): string
    {
        $context = [];

        // Project context
        if ($project) {
            $context[] = self::formatProjectContext($project);
        }

        // Session context
        if ($session) {
            $context[] = self::formatSessionContext($session);
        }

        // Canon context
        if ($project) {
            $canon = self::formatCanonContext($project);
            if ($canon) $context[] = $canon;
        }

        // Combine
        $formatted = implode("\n\n", array_filter($context));
        
        return $formatted ? $formatted . "\n\n---\n\n" . $basePrompt : $basePrompt;
    }

    /**
     * Format context for image generation.
     */
    public static function forImage(string $basePrompt, ?Project $project = null, ?Session $session = null): array
    {
        $context = [
            'prompt' => $basePrompt,
            'style' => [],
            'references' => [],
            'canon' => [],
        ];

        // Visual style from project
        if ($project) {
            $context['style'] = self::getVisualStyle($project);
        }

        // Reference images
        if ($project) {
            $context['references'] = self::getReferenceImages($project, $session);
        }

        // Character/visual canon for image prompts
        if ($project) {
            $context['canon'] = self::getVisualCanon($project);
        }

        // Build combined prompt
        $context['combined_prompt'] = self::buildImagePrompt($basePrompt, $context);

        return $context;
    }

    /**
     * Format context for storyboard generation.
     */
    public static function forStoryboard(string $basePrompt, ?Project $project = null, ?Session $session = null, int $frameCount = 4): array
    {
        $imageContext = self::forImage($basePrompt, $project, $session);
        
        return [
            'prompt' => $basePrompt,
            'frame_count' => $frameCount,
            'style' => $imageContext['style'],
            'references' => $imageContext['references'],
            'canon' => $imageContext['canon'],
            'frame_prompts' => self::generateFramePrompts($basePrompt, $frameCount, $imageContext),
        ];
    }

    // ============================================================
    // Private Formatters
    // ============================================================

    protected static function formatProjectContext(Project $project): string
    {
        $lines = ["=== PROJECT: {$project->name} ==="];
        
        if ($project->description) {
            $lines[] = "Description: {$project->description}";
        }
        
        if ($project->type) {
            $lines[] = "Type: {$project->type}";
        }

        return implode("\n", $lines);
    }

    protected static function formatSessionContext(Session $session): string
    {
        $lines = ["=== SESSION: {$session->name} ==="];
        
        if ($session->notes) {
            $lines[] = "Goal: {$session->notes}";
        }
        
        if ($session->temp_notes) {
            $lines[] = "Notes: {$session->temp_notes}";
        }
        
        if ($session->draft_text) {
            $lines[] = "Current Draft: " . substr($session->draft_text, 0, 200) . "...";
        }

        return implode("\n", $lines);
    }

    protected static function formatCanonContext(Project $project): ?string
    {
        $canon = $project->canonEntries()
            ->orderByRaw("FIELD(importance, 'critical', 'important', 'minor', 'none')")
            ->limit(20)
            ->get();

        if ($canon->isEmpty()) return null;

        $sections = ["=== CANON (World Knowledge) ==="];
        
        // Group by type
        $byType = $canon->groupBy('type');
        
        foreach ($byType as $type => $entries) {
            $sections[] = "\n--- {$type}s ---";
            foreach ($entries as $entry) {
                $importance = $entry->importance !== 'none' ? " [{$entry->importance}]" : "";
                $sections[] = "- {$entry->title}{$importance}";
                if ($entry->content) {
                    $sections[] = "  " . substr($entry->content, 0, 100);
                }
            }
        }

        return implode("\n", $sections);
    }

    protected static function getVisualStyle(Project $project): array
    {
        $style = [];

        // Main style image
        if ($project->style_image_path) {
            $style['main'] = [
                'url' => asset('storage/' . $project->style_image_path),
                'title' => $project->style_image_title,
            ];
        }

        // Supporting style images
        if ($project->style_images) {
            $style['supporting'] = array_map(fn($img) => [
                'url' => asset('storage/' . $img['path']),
                'title' => $img['title'] ?? null,
            ], $project->style_images);
        }

        // Style notes
        if ($project->style_notes) {
            $style['notes'] = $project->style_notes;
        }

        return $style;
    }

    protected static function getReferenceImages(Project $project, ?Session $session): array
    {
        $refs = [];

        // Project references
        $projectRefs = ReferenceImage::forProject($project->id)->get();
        foreach ($projectRefs as $ref) {
            $refs[] = ['url' => $ref->url, 'title' => $ref->title, 'type' => 'project'];
        }

        // Session references
        if ($session) {
            $sessionRefs = ReferenceImage::forSession($session->id)->get();
            foreach ($sessionRefs as $ref) {
                $refs[] = ['url' => $ref->url, 'title' => $ref->title, 'type' => 'session'];
            }
        }

        return $refs;
    }

    protected static function getVisualCanon(Project $project): array
    {
        // Get canon entries that have visual relevance
        $visualCanon = $project->canonEntries()
            ->whereIn('type', ['character', 'location', 'artifact'])
            ->whereNotNull('image')
            ->get();

        return $visualCanon->map(fn($entry) => [
            'title' => $entry->title,
            'type' => $entry->type,
            'image' => $entry->image,
            'description' => $entry->content ? substr($entry->content, 0, 100) : null,
        ])->toArray();
    }

    protected static function buildImagePrompt(string $base, array $context): string
    {
        $parts = [$base];

        // Add style notes
        if (!empty($context['style']['notes'])) {
            $parts[] = "Style notes: {$context['style']['notes']}";
        }

        // Add character descriptions from canon
        if (!empty($context['canon'])) {
            foreach ($context['canon'] as $canon) {
                if ($canon['description']) {
                    $parts[] = "{$canon['title']}: {$canon['description']}";
                }
            }
        }

        return implode(". ", $parts);
    }

    protected static function generateFramePrompts(string $base, int $count, array $context): array
    {
        $prompts = [];
        
        $frameDescriptions = [
            'Establishing shot - wide view',
            'Medium shot - main subject focus',
            'Detail shot - important element',
            'Action moment - dynamic pose',
            'Atmospheric shot - mood setting',
            'Character interaction - dialogue moment',
            'POV shot - perspective view',
            'Dramatic angle - impactful view',
        ];

        for ($i = 0; $i < $count; $i++) {
            $desc = $frameDescriptions[$i % count($frameDescriptions)];
            $prompts[] = "{$base}, {$desc}";
        }

        return $prompts;
    }

    /**
     * Quick format - just project and prompt.
     */
    public static function quick(string $prompt, ?Project $project = null): string
    {
        if (!$project) return $prompt;
        
        $context = "Project: {$project->name}";
        if ($project->type) $context .= " | Type: {$project->type}";
        
        return "{$context}\n\n{$prompt}";
    }
}
