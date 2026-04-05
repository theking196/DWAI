<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Session extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'project_id',
        'name',
        'description',
        'notes',
        'temp_notes',
        'ai_reasoning',
        'draft_text',
        'session_references',
        'context_updated_at',
        'type',
        'status',
        'output_count',
    ];

    protected $casts = [
        'output_count' => 'integer',
        'session_references' => 'array',
        'context_updated_at' => 'datetime',
    ];

    // Relationships
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function aiOutputs(): HasMany
    {
        return $this->hasMany(AIOutput::class);
    }

    public function timelineEvents(): HasMany
    {
        return $this->hasMany(TimelineEvent::class);
    }

    // Scopes
    public function scopeActive($query) { return $query->where('status', 'active'); }
    public function scopeCompleted($query) { return $query->where('status', 'completed'); }
    public function scopeArchived($query) { return $query->where('status', 'archived'); }

    // Accessors
    public function getIsActiveAttribute(): bool { return $this->status === 'active'; }
    public function getStatusLabelAttribute(): string { return match($this->status) { 'active'=>'In Progress', 'completed'=>'Completed', 'archived'=>'Archived', default=>ucfirst($this->status) }; }
    public function getHasContextAttribute(): bool { return !empty($this->temp_notes) || !empty($this->ai_reasoning) || !empty($this->draft_text) || !empty($this->session_references); }

    // ============================================================
    // Full Session Summary
    // ============================================================

    /**
     * Get complete production summary for session.
     */
    public function getFullSummary(): array
    {
        return [
            // Basic info
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'is_active' => $this->is_active,
            
            // Project link
            'project' => [
                'id' => $this->project->id ?? null,
                'name' => $this->project->name ?? null,
            ],
            
            // Goals
            'active_goal' => $this->notes,
            
            // Recent AI outputs (last 5)
            'recent_outputs' => $this->getRecentOutputs(),
            
            // Temporary context
            'short_term_memory' => $this->getShortTermMemory(),
            
            // Current errors (failed outputs)
            'current_errors' => $this->getCurrentErrors(),
            
            // Current references
            'current_references' => $this->getCurrentReferences(),
            
            // Timing
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            'last_activity' => $this->getLastActivityTime(),
            'context_updated_at' => $this->context_updated_at?->toISOString(),
            
            // Counts
            'output_count' => $this->output_count,
            'timeline_count' => $this->timelineEvents()->count(),
        ];
    }

    /**
     * Get recent AI outputs.
     */
    public function getRecentOutputs(int $limit = 5): array
    {
        return $this->aiOutputs()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(fn($o) => [
                'id' => $o->id,
                'type' => $o->type,
                'prompt' => substr($o->prompt, 0, 100),
                'status' => $o->status,
                'model' => $o->model,
                'created_at' => $o->created_at->toISOString(),
            ])
            ->toArray();
    }

    /**
     * Get short-term memory summary.
     */
    public function getShortTermMemory(): array
    {
        return [
            'temp_notes' => $this->temp_notes ? substr($this->temp_notes, 0, 200) : null,
            'has_ai_reasoning' => !empty($this->ai_reasoning),
            'ai_reasoning' => $this->ai_reasoning ? substr($this->ai_reasoning, 0, 200) : null,
            'has_draft' => !empty($this->draft_text),
            'draft_text' => $this->draft_text ? substr($this->draft_text, 0, 200) : null,
            'reference_count' => count($this->session_references ?? []),
        ];
    }

    /**
     * Get current errors (failed outputs).
     */
    public function getCurrentErrors(): array
    {
        return $this->aiOutputs()
            ->where('status', 'failed')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(fn($o) => [
                'id' => $o->id,
                'prompt' => substr($o->prompt, 0, 100),
                'error' => $o->error_message,
                'created_at' => $o->created_at->toISOString(),
            ])
            ->toArray();
    }

    /**
     * Get current references for session.
     */
    public function getCurrentReferences(): array
    {
        $refs = $this->session_references ?? [];
        return array_map(fn($r) => [
            'id' => $r['id'] ?? null,
            'url' => $r['url'] ?? null,
            'title' => $r['title'] ?? null,
            'added_at' => $r['added_at'] ?? null,
        ], $refs);
    }

    /**
     * Get last activity time (most recent of outputs or updates).
     */
    public function getLastActivityTime(): ?string
    {
        $latestOutput = $this->aiOutputs()->orderBy('created_at', 'desc')->first();
        $latestUpdate = $this->updated_at;
        
        if (!$latestOutput && !$latestUpdate) return null;
        if (!$latestOutput) return $latestUpdate->toISOString();
        if (!$latestUpdate) return $latestOutput->created_at->toISOString();
        
        return $latestOutput->created_at->gte($latestUpdate) 
            ? $latestOutput->created_at->toISOString() 
            : $latestUpdate->toISOString();
    }

    // Short-Term Memory Methods
    public function updateTempNotes(string $notes): void { $this->update(['temp_notes' => $notes, 'context_updated_at' => now()]); }
    public function appendTempNotes(string $notes): void { $this->update(['temp_notes' => ($this->temp_notes ?? '')."\n".$notes, 'context_updated_at' => now()]); }
    public function updateAIReasoning(string $r): void { $this->update(['ai_reasoning' => $r, 'context_updated_at' => now()]); }
    public function updateDraftText(string $t): void { $this->update(['draft_text' => $t, 'context_updated_at' => now()]); }
    public function addSessionReference(array $ref): void { $refs = $this->session_references ?? []; $refs[] = array_merge($ref, ['added_at' => now()->toISOString()]); $this->update(['session_references' => $refs, 'context_updated_at' => now()]); }
    public function removeSessionReference(string $id): void { $refs = array_filter($this->session_references ?? [], fn($r) => ($r['id'] ?? null) !== $id); $this->update(['session_references' => array_values($refs), 'context_updated_at' => now()]); }
    public function clearShortTermMemory(): void { $this->update(['temp_notes' => null, 'ai_reasoning' => null, 'draft_text' => null, 'session_references' => null, 'context_updated_at' => now()]); }

    // Status Methods
    public function close(): void { $this->update(['status' => 'completed']); }
    public function archive(): void { $this->update(['status' => 'archived']); }
    public function resume(): void { $this->update(['status' => 'active']); }

    public function getSummary(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'status' => $this->status,
            'output_count' => $this->output_count,
            'project_name' => $this->project->name ?? null,
            'short_term_memory' => $this->getShortTermMemory(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
