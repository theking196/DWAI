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

    // ============================================================
    // Relationships
    // ============================================================

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

    // ============================================================
    // Scopes
    // ============================================================

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeArchived($query)
    {
        return $query->where('status', 'archived');
    }

    // ============================================================
    // Accessors
    // ============================================================

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'active' => 'In Progress',
            'completed' => 'Completed',
            'archived' => 'Archived',
            default => ucfirst($this->status),
        };
    }

    public function getHasContextAttribute(): bool
    {
        return !empty($this->temp_notes) || 
               !empty($this->ai_reasoning) || 
               !empty($this->draft_text) ||
               !empty($this->session_references);
    }

    // ============================================================
    // Short-Term Memory Methods
    // ============================================================

    /**
     * Update temporary notes.
     */
    public function updateTempNotes(string $notes): void
    {
        $this->update([
            'temp_notes' => $notes,
            'context_updated_at' => now(),
        ]);
    }

    /**
     * Append to temporary notes.
     */
    public function appendTempNotes(string $notes): void
    {
        $current = $this->temp_notes ?? '';
        $this->update([
            'temp_notes' => $current . "\n" . $notes,
            'context_updated_at' => now(),
        ]);
    }

    /**
     * Store AI reasoning for this session.
     */
    public function updateAIReasoning(string $reasoning): void
    {
        $this->update([
            'ai_reasoning' => $reasoning,
            'context_updated_at' => now(),
        ]);
    }

    /**
     * Store draft scene text.
     */
    public function updateDraftText(string $text): void
    {
        $this->update([
            'draft_text' => $text,
            'context_updated_at' => now(),
        ]);
    }

    /**
     * Add a session-specific reference.
     */
    public function addSessionReference(array $reference): void
    {
        $refs = $this->session_references ?? [];
        $refs[] = array_merge($reference, ['added_at' => now()->toISOString()]);
        
        $this->update([
            'session_references' => $refs,
            'context_updated_at' => now(),
        ]);
    }

    /**
     * Remove a session reference.
     */
    public function removeSessionReference(string $referenceId): void
    {
        $refs = $this->session_references ?? [];
        $refs = array_filter($refs, fn($r) => ($r['id'] ?? null) !== $referenceId);
        
        $this->update([
            'session_references' => array_values($refs),
            'context_updated_at' => now(),
        ]);
    }

    /**
     * Clear all short-term memory.
     */
    public function clearShortTermMemory(): void
    {
        $this->update([
            'temp_notes' => null,
            'ai_reasoning' => null,
            'draft_text' => null,
            'session_references' => null,
            'context_updated_at' => now(),
        ]);
    }

    /**
     * Get short-term memory summary.
     */
    public function getShortTermMemory(): array
    {
        return [
            'temp_notes' => $this->temp_notes,
            'has_ai_reasoning' => !empty($this->ai_reasoning),
            'has_draft' => !empty($this->draft_text),
            'reference_count' => count($this->session_references ?? []),
            'last_updated' => $this->context_updated_at,
        ];
    }

    // ============================================================
    // Status Methods
    // ============================================================

    public function close(): void
    {
        $this->update(['status' => 'completed']);
    }

    public function archive(): void
    {
        $this->update(['status' => 'archived']);
    }

    public function resume(): void
    {
        $this->update(['status' => 'active']);
    }

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
