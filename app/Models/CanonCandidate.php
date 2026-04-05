<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CanonCandidate extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'project_id', 'session_id', 'source_output_id',
        'title', 'type', 'content', 'ai_reasoning', 'tags', 'importance',
        'status', 'review_notes', 'reviewer_id', 'reviewed_at',
    ];

    protected $casts = [
        'tags' => 'array',
        'reviewed_at' => 'datetime',
    ];

    // Relationships
    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function session(): BelongsTo { return $this->belongsTo(Session::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewer_id'); }
    public function sourceOutput(): BelongsTo { return $this->belongsTo(AIOutput::class, 'source_output_id'); }

    // Scopes
    public function scopePending($query) { return $query->where('status', 'pending'); }
    public function scopeApproved($query) { return $query->where('status', 'approved'); }
    public function scopeRejected($query) { return $query->where('status', 'rejected'); }
    public function scopeForProject($query, int $projectId) { return $query->where('project_id', $projectId); }

    // Create from session data
    public static function createFromSession(Session $session, array $data): self
    {
        return self::create([
            'user_id' => auth()->id() ?? $session->user_id,
            'project_id' => $session->project_id,
            'session_id' => $session->id,
            'title' => $data['title'],
            'type' => $data['type'],
            'content' => $data['content'] ?? $session->draft_text,
            'ai_reasoning' => $data['ai_reasoning'] ?? $session->ai_reasoning,
            'tags' => $data['tags'] ?? [],
            'importance' => $data['importance'] ?? 'none',
            'status' => 'pending',
        ]);
    }

    // Create from AI output
    public static function createFromOutput(AIOutput $output, array $data): self
    {
        return self::create([
            'user_id' => $output->session->user_id ?? auth()->id(),
            'project_id' => $output->session->project_id ?? null,
            'session_id' => $output->session->id ?? null,
            'source_output_id' => $output->id,
            'title' => $data['title'],
            'type' => $data['type'],
            'content' => $data['content'] ?? $output->content,
            'ai_reasoning' => $data['ai_reasoning'] ?? null,
            'tags' => $data['tags'] ?? [],
            'importance' => $data['importance'] ?? 'none',
            'status' => 'pending',
        ]);
    }

    // Approve - promotes to canon
    public function approve(?string $notes = null): CanonEntry
    {
        $this->update([
            'status' => 'approved',
            'reviewer_id' => auth()->id(),
            'reviewed_at' => now(),
            'review_notes' => $notes,
        ]);

        // Create canon entry
        return CanonEntry::create([
            'user_id' => $this->user_id,
            'project_id' => $this->project_id,
            'title' => $this->title,
            'type' => $this->type,
            'content' => $this->content,
            'tags' => $this->tags,
            'importance' => $this->importance,
        ]);
    }

    // Reject
    public function reject(string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'reviewer_id' => auth()->id(),
            'reviewed_at' => now(),
            'review_notes' => $reason,
        ]);
    }

    // Edit and approve in one
    public function approveWithEdit(array $updates, ?string $notes = null): CanonEntry
    {
        $this->update(array_merge($updates, [
            'reviewer_id' => auth()->id(),
            'reviewed_at' => now(),
            'review_notes' => $notes,
            'status' => 'approved',
        ]));

        return CanonEntry::create([
            'user_id' => $this->user_id,
            'project_id' => $this->project_id,
            'title' => $this->title,
            'type' => $this->type,
            'content' => $this->content,
            'tags' => $this->tags,
            'importance' => $this->importance,
        ]);
    }

    // Get related AI output for context
    public function getContext(): ?array
    {
        if (!$this->session && !$this->sourceOutput) {
            return null;
        }

        return [
            'session_name' => $this->session->name ?? null,
            'ai_reasoning' => $this->ai_reasoning,
            'source_output_type' => $this->sourceOutput?->type,
            'source_output_prompt' => $this->sourceOutput?->prompt,
        ];
    }

    public function getSummary(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'type' => $this->type,
            'content_preview' => $this->content ? substr($this->content, 0, 100) : null,
            'status' => $this->status,
            'importance' => $this->importance,
            'tags' => $this->tags,
            'session_name' => $this->session->name ?? null,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
