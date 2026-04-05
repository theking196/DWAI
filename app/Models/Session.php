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
        'type',
        'status',
        'output_count',
    ];

    protected $casts = [
        'output_count' => 'integer',
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

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
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

    // ============================================================
    // Helper Methods
    // ============================================================

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isArchived(): bool
    {
        return $this->status === 'archived';
    }

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

    public function updateNotes(string $notes): void
    {
        $this->update(['notes' => $notes]);
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
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
