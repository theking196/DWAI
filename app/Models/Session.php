<?php

use App\Models\CanonCandidate;

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Session extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'project_id', 'name', 'description', 'notes',
        'temp_notes', 'ai_reasoning', 'draft_text', 'session_references',
        'context_updated_at', 'type', 'status', 'output_count',
        'archived_at', 'archive_reason', 'activity_history',
    ];

    protected $casts = [
        'output_count' => 'integer',
        'session_references' => 'array',
        'assistant_structure' => 'array',
        'assistant_image_prompts' => 'array',
        'assistant_video_prompts' => 'array',
        'build_steps' => 'array',
        'build_outputs' => 'array',
        'current_step_index' => 'integer',
        'context_updated_at' => 'datetime',
        'archived_at' => 'datetime',
        'activity_history' => 'array',
    ];

    protected $attributes = [
        'session_type' => 'normal',
    ];

    // Relationships
    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function aiOutputs(): HasMany { return $this->hasMany(AIOutput::class); }
    public function timelineEvents(): HasMany { return $this->hasMany(TimelineEvent::class); }

    // Scopes
    public function scopeActive($query) { return $query->where('status', 'active'); }
    public function scopeCompleted($query) { return $query->where('status', 'completed'); }
    public function scopeArchived($query) { return $query->where('status', 'archived'); }
    public function scopeSearchable($query) { return $query->whereIn('status', ['active', 'completed', 'archived']); }

    // Accessors
    public function getIsActiveAttribute(): bool { return $this->status === 'active'; }
    public function getStatusLabelAttribute(): string { return match($this->status) { 'active'=>'In Progress', 'completed'=>'Completed', 'archived'=>'Archived', default=>ucfirst($this->status) }; }
    public function getHasContextAttribute(): bool { return !empty($this->temp_notes) || !empty($this->ai_reasoning) || !empty($this->draft_text); }

    // ============================================================
    // Archive Management
    // ============================================================

    public function archive(string $reason = null): void
    {
        $this->recordActivity('archived', 'Session archived', ['reason' => $reason]);
        $this->update(['status' => 'archived', 'archived_at' => now(), 'archive_reason' => $reason]);
    }

    public function unarchive(): void
    {
        $this->recordActivity('unarchived', 'Session restored from archive');
        $this->update(['status' => 'active', 'archived_at' => null, 'archive_reason' => null]);
    }

    public function isArchived(): bool { return $this->status === 'archived'; }

    // ============================================================
    // Activity History
    // ============================================================

    public function recordActivity(string $action, string $description, array $metadata = []): void
    {
        $history = $this->activity_history ?? [];
        $history[] = [
            'action' => $action,
            'description' => $description,
            'metadata' => $metadata,
            'timestamp' => now()->toISOString(),
            'user_id' => auth()->id() ?? null,
        ];
        if (count($history) > 100) { $history = array_slice($history, -100); }
        $this->update(['activity_history' => $history]);
    }

    public function recordAIOutput(AIOutput $output): void
    {
        $this->recordActivity('ai_output', 'AI output generated', [
            'output_id' => $output->id, 'type' => $output->type, 'model' => $output->model, 'status' => $output->status,
        ]);
    }

    public function getActivityHistory(int $limit = 20): array
    {
        $history = $this->activity_history ?? [];
        return array_slice($history, -$limit);
    }

    public function getTimeline(): array
    {
        $timeline = [];
        foreach ($this->activity_history ?? [] as $event) {
            $timeline[] = ['type' => 'activity', 'action' => $event['action'], 'description' => $event['description'], 'timestamp' => $event['timestamp']];
        }
        foreach ($this->aiOutputs()->orderBy('created_at', 'desc')->limit(20)->get() as $output) {
            $timeline[] = ['type' => 'ai_output', 'action' => $output->type, 'description' => substr($output->prompt, 0, 50), 'timestamp' => $output->created_at->toISOString()];
        }
        usort($timeline, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);
        return array_slice($timeline, 0, 30);
    }

    // ============================================================
    // Search
    // ============================================================

    public static function search(string $query): \Illuminate\Database\Eloquent\Builder
    {
        return static::searchable()->where(function ($q) use ($query) {
            $q->where('name', 'like', "%{$query}%")->orWhere('description', 'like', "%{$query}%")
              ->orWhere('notes', 'like', "%{$query}%")->orWhere('temp_notes', 'like', "%{$query}%")->orWhere('draft_text', 'like', "%{$query}%");
        });
    }

    // ============================================================
    // Full Summary
    // ============================================================

    public function getFullSummary(): array
    {
        return [
            'id' => $this->id, 'name' => $this->name, 'description' => $this->description,
            'type' => $this->type, 'status' => $this->status, 'status_label' => $this->status_label,
            'is_active' => $this->is_active, 'is_archived' => $this->isArchived(),
            'archived_at' => $this->archived_at?->toISOString(), 'archive_reason' => $this->archive_reason,
            'project' => ['id' => $this->project->id ?? null, 'name' => $this->project->name ?? null],
            'active_goal' => $this->notes, 'recent_outputs' => $this->getRecentOutputs(),
            'short_term_memory' => $this->getShortTermMemory(), 'current_errors' => $this->getCurrentErrors(),
            'current_references' => $this->getCurrentReferences(), 'activity_history' => $this->getActivityHistory(10),
            'created_at' => $this->created_at->toISOString(), 'updated_at' => $this->updated_at->toISOString(),
            'last_activity' => $this->getLastActivityTime(), 'context_updated_at' => $this->context_updated_at?->toISOString(),
            'output_count' => $this->output_count, 'timeline_count' => $this->timelineEvents()->count(),
        ];
    }

    public function getRecentOutputs(int $limit = 5): array
    {
        return $this->aiOutputs()->orderBy('created_at', 'desc')->limit($limit)->get()->map(fn($o) => [
            'id' => $o->id, 'type' => $o->type, 'prompt' => substr($o->prompt, 0, 100), 'status' => $o->status, 'model' => $o->model, 'created_at' => $o->created_at->toISOString(),
        ])->toArray();
    }

    public function getShortTermMemory(): array
    {
        return ['temp_notes' => $this->temp_notes ? substr($this->temp_notes, 0, 200) : null, 'has_ai_reasoning' => !empty($this->ai_reasoning),
            'ai_reasoning' => $this->ai_reasoning ? substr($this->ai_reasoning, 0, 200) : null, 'has_draft' => !empty($this->draft_text),
            'draft_text' => $this->draft_text ? substr($this->draft_text, 0, 200) : null, 'reference_count' => count($this->session_references ?? [])];
    }

    public function getCurrentErrors(): array
    {
        return $this->aiOutputs()->where('status', 'failed')->orderBy('created_at', 'desc')->limit(5)->get()->map(fn($o) => [
            'id' => $o->id, 'prompt' => substr($o->prompt, 0, 100), 'error' => $o->error_message, 'created_at' => $o->created_at->toISOString()])->toArray();
    }

    public function getCurrentReferences(): array
    {
        return array_map(fn($r) => ['id' => $r['id'] ?? null, 'url' => $r['url'] ?? null, 'title' => $r['title'] ?? null, 'added_at' => $r['added_at'] ?? null], $this->session_references ?? []);
    }

    public function getLastActivityTime(): ?string
    {
        $latestOutput = $this->aiOutputs()->orderBy('created_at', 'desc')->first();
        $latestUpdate = $this->updated_at;
        if (!$latestOutput && !$latestUpdate) return null;
        if (!$latestOutput) return $latestUpdate->toISOString();
        if (!$latestUpdate) return $latestOutput->created_at->toISOString();
        return $latestOutput->created_at->gte($latestUpdate) ? $latestOutput->created_at->toISOString() : $latestUpdate->toISOString();
    }

    // Short-Term Memory
    public function updateTempNotes(string $notes): void { $this->update(['temp_notes' => $notes, 'context_updated_at' => now()]); $this->recordActivity('note_updated', 'Temporary notes updated'); }
    public function appendTempNotes(string $notes): void { $this->update(['temp_notes' => ($this->temp_notes ?? '')."\n".$notes, 'context_updated_at' => now()]); }
    public function updateAIReasoning(string $r): void { $this->update(['ai_reasoning' => $r, 'context_updated_at' => now()]); }
    public function updateDraftText(string $t): void { $this->update(['draft_text' => $t, 'context_updated_at' => now()]); $this->recordActivity('draft_updated', 'Draft text updated'); }
    public function addSessionReference(array $ref): void { $refs = $this->session_references ?? []; $refs[] = array_merge($ref, ['added_at' => now()->toISOString()]); $this->update(['session_references' => $refs, 'context_updated_at' => now()]); $this->recordActivity('reference_added', 'Reference added', $ref); }
    public function removeSessionReference(string $id): void { $refs = array_filter($this->session_references ?? [], fn($r) => ($r['id'] ?? null) !== $id); $this->update(['session_references' => array_values($refs), 'context_updated_at' => now()]); }
    public function clearShortTermMemory(): void { $this->update(['temp_notes' => null, 'ai_reasoning' => null, 'draft_text' => null, 'session_references' => null, 'context_updated_at' => now()]); $this->recordActivity('memory_cleared', 'Short-term memory cleared'); }

    // Status
    public function close(): void { $this->update(['status' => 'completed']); $this->recordActivity('closed', 'Session marked as completed'); }
    public function resume(): void { $this->update(['status' => 'active']); $this->recordActivity('resumed', 'Session resumed'); }

    public function getSummary(): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'type' => $this->type, 'status' => $this->status, 'output_count' => $this->output_count,
            'project_name' => $this->project->name ?? null, 'short_term_memory' => $this->getShortTermMemory(), 'created_at' => $this->created_at, 'updated_at' => $this->updated_at];
    }
}



    // Promote session data to canon candidate
    public function promoteToCanon(array $data): CanonCandidate
    {
        return CanonCandidate::createFromSession($this, $data);
    }
}

    // ============================================================
    // Session End - Handle Memory
    // ============================================================

    /**
     * Close session with memory action.
     * Promotes useful data to canon or discards.
     */
    public function closeWithMemoryAction(string $action = 'discard', ?array $promoteData = null): void
    {
        match($action) {
            'promote' => $this->promoteMemoryToCanon($promoteData),
            'discard' => $this->clearShortTermMemory(),
            default => null,
        };

        $this->close();
    }

    /**
     * Archive session with memory action.
     */
    public function archiveWithMemoryAction(string $reason = null, string $action = 'discard', ?array $promoteData = null): void
    {
        match($action) {
            'promote' => $this->promoteMemoryToCanon($promoteData),
            'discard' => $this->clearShortTermMemory(),
            default => null,
        };

        $this->archive($reason);
    }

    /**
     * Promote short-term memory to canon.
     */
    public function promoteMemoryToCanon(?array $options = null): ?CanonEntry
    {
        if (!$this->hasContextAttribute) {
            return null;
        }

        $title = $options['title'] ?? ($this->name . ' - Notes');
        $type = $options['type'] ?? 'note';
        $importance = $options['importance'] ?? 'minor';

        // Combine memory into content
        $content = [];
        if ($this->temp_notes) $content[] = "Notes: " . $this->temp_notes;
        if ($this->ai_reasoning) $content[] = "AI Reasoning: " . $this->ai_reasoning;
        if ($this->draft_text) $content[] = "Draft: " . $this->draft_text;

        $canon = CanonEntry::create([
            'user_id' => $this->user_id,
            'project_id' => $this->project_id,
            'title' => $title,
            'type' => $type,
            'content' => implode("\n\n---\n\n", $content),
            'tags' => $options['tags'] ?? ['from-session', 'temp-memory'],
            'importance' => $importance,
        ]);

        $this->recordActivity('memory_promoted', 'Short-term memory promoted to canon', ['canon_id' => $canon->id]);

        // Optionally clear memory after promoting
        if ($options['clear_after'] ?? true) {
            $this->clearShortTermMemory();
        }

        return $canon;
    }

    /**
     * Get memory summary for decision.
     */
    public function getMemorySummary(): array
    {
        return [
            'has_content' => $this->hasContextAttribute,
            'temp_notes_length' => strlen($this->temp_notes ?? ''),
            'draft_length' => strlen($this->draft_text ?? ''),
            'has_reasoning' => !empty($this->ai_reasoning),
            'reference_count' => count($this->session_references ?? []),
            'suggestion' => $this->hasContextAttribute ? 'Consider promoting to canon before closing' : 'No memory to preserve',
        ];
    }

    // ============================================================
    // Visual Style Override
    // ============================================================

    /**
     * Override visual style for this session.
     */
    public function setStyleOverride(?string $imagePath, ?string $description = null): void
    {
        $this->update([
            'style_override' => [
                'image_path' => $imagePath,
                'description' => $description,
            ],
        ]);
    }

    /**
     * Clear style override.
     */
    public function clearStyleOverride(): void
    {
        $this->update(['style_override' => null]);
    }

    /**
     * Check if session has style override.
     */
    public function hasStyleOverride(): bool
    {
        $override = $this->style_override;
        return !empty($override['image_path']) || !empty($override['description']);
    }

    /**
     * Get effective style (override or project default).
     */
    public function getEffectiveStyle(): array
    {
        // Return override if exists
        if ($this->hasStyleOverride()) {
            return [
                'image_url' => $this->getStyleOverrideUrl(),
                'description' => $this->getStyleOverrideDescription(),
                'source' => 'session_override',
            ];
        }

        // Fall back to project style
        $project = $this->project;
        if ($project && $project->hasVisualStyle()) {
            return [
                'image_url' => $project->getVisualStyleUrl(),
                'description' => $project->getVisualStyleDescription(),
                'source' => 'project',
            ];
        }

        return [
            'image_url' => null,
            'description' => null,
            'source' => 'none',
        ];
    }

    /**
     * Get style override image URL.
     */
    public function getStyleOverrideUrl(): ?string
    {
        $override = $this->style_override;
        if (empty($override['image_path'])) {
            return null;
        }
        return asset('storage/' . $override['image_path']);
    }

    /**
     * Get style override description.
     */
    public function getStyleOverrideDescription(): ?string
    {
        $override = $this->style_override;
        return $override['description'] ?? null;
    }

    // ============================================================
    // Assistant Agent Mode
    // ============================================================

    /**
     * Assistant phases.
     */
    const ASSISTANT_PHASES = [
        'idea_input' => 'Idea Input',
        'idea_refinement' => 'Idea Refinement',
        'structure_planning' => 'Structure Planning',
        'image_prompts' => 'Image Prompts',
        'video_prompts' => 'Video Prompts',
        'music_prompt' => 'Music Prompt',
        'complete' => 'Complete',
    ];

    /**
     * Get current assistant phase.
     */
    public function getAssistantPhase(): string
    {
        return $this->assistant_phase ?? 'idea_input';
    }

    /**
     * Set assistant phase.
     */
    public function setAssistantPhase(string $phase): void
    {
        if (in_array($phase, array_keys(self::ASSISTANT_PHASES))) {
            $this->update(['assistant_phase' => $phase]);
        }
    }

    /**
     * Get assistant idea.
     */
    public function getAssistantIdea(): ?string
    {
        return $this->assistant_idea;
    }

    /**
     * Set assistant idea.
     */
    public function setAssistantIdea(string $idea): void
    {
        $this->update(['assistant_idea' => $idea]);
    }

    /**
     * Get refined idea.
     */
    public function getAssistantRefinedIdea(): ?string
    {
        return $this->assistant_refined_idea;
    }

    /**
     * Set refined idea.
     */
    public function setAssistantRefinedIdea(string $idea): void
    {
        $this->update(['assistant_refined_idea' => $idea]);
    }

    /**
     * Get assistant structure.
     */
    public function getAssistantStructure(): array
    {
        return $this->assistant_structure ?? [];
    }

    /**
     * Set assistant structure.
     */
    public function setAssistantStructure(array $structure): void
    {
        $this->update(['assistant_structure' => $structure]);
    }

    /**
     * Update assistant structure incrementally.
     */
    public function updateAssistantStructure(array $updates): void
    {
        $current = $this->getAssistantStructure();
        $this->update(['assistant_structure' => array_merge($current, $updates)]);
    }

    /**
     * Get image prompts.
     */
    public function getAssistantImagePrompts(): array
    {
        return $this->assistant_image_prompts ?? [];
    }

    /**
     * Set image prompts.
     */
    public function setAssistantImagePrompts(array $prompts): void
    {
        $this->update(['assistant_image_prompts' => $prompts]);
    }

    /**
     * Add image prompt.
     */
    public function addAssistantImagePrompt(string $prompt): void
    {
        $prompts = $this->getAssistantImagePrompts();
        $prompts[] = $prompt;
        $this->update(['assistant_image_prompts' => $prompts]);
    }

    /**
     * Get video prompts.
     */
    public function getAssistantVideoPrompts(): array
    {
        return $this->assistant_video_prompts ?? [];
    }

    /**
     * Set video prompts.
     */
    public function setAssistantVideoPrompts(array $prompts): void
    {
        $this->update(['assistant_video_prompts' => $prompts]);
    }

    /**
     * Add video prompt.
     */
    public function addAssistantVideoPrompt(string $prompt): void
    {
        $prompts = $this->getAssistantVideoPrompts();
        $prompts[] = $prompt;
        $this->update(['assistant_video_prompts' => $prompts]);
    }

    /**
     * Get music prompt.
     */
    public function getAssistantMusicPrompt(): ?string
    {
        return $this->assistant_music_prompt;
    }

    /**
     * Set music prompt.
     */
    public function setAssistantMusicPrompt(string $prompt): void
    {
        $this->update(['assistant_music_prompt' => $prompt]);
    }

    /**
     * Get complete assistant state.
     */
    public function getAssistantState(): array
    {
        return [
            'phase' => $this->getAssistantPhase(),
            'phase_label' => self::ASSISTANT_PHASES[$this->getAssistantPhase()] ?? 'Unknown',
            'idea' => $this->getAssistantIdea(),
            'refined_idea' => $this->getAssistantRefinedIdea(),
            'structure' => $this->getAssistantStructure(),
            'image_prompts' => $this->getAssistantImagePrompts(),
            'video_prompts' => $this->getAssistantVideoPrompts(),
            'music_prompt' => $this->getAssistantMusicPrompt(),
        ];
    }

    /**
     * Advance to next phase.
     */
    public function advanceAssistantPhase(): void
    {
        $phases = array_keys(self::ASSISTANT_PHASES);
        $current = array_search($this->getAssistantPhase(), $phases);
        
        if ($current !== false && $current < count($phases) - 1) {
            $this->update(['assistant_phase' => $phases[$current + 1]]);
        }
    }

    /**
     * Check if assistant is complete.
     */
    public function isAssistantComplete(): bool
    {
        return $this->getAssistantPhase() === 'complete';
    }

    /**
     * Reset assistant state.
     */
    public function resetAssistantState(): void
    {
        $this->update([
            'assistant_phase' => 'idea_input',
            'assistant_idea' => null,
            'assistant_refined_idea' => null,
            'assistant_structure' => null,
            'assistant_image_prompts' => null,
            'assistant_video_prompts' => null,
            'assistant_music_prompt' => null,
        ]);
    }
