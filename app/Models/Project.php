<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'type',
        'thumbnail',
        'visual_style_image',
        'visual_style_description',
        'progress',
        'status',
        'is_archived',
        'archived_at',
        'metadata',
        'tags',
    ];

    protected $casts = [
        'progress' => 'integer',
        'is_archived' => 'boolean',
        'archived_at' => 'datetime',
        'metadata' => 'array',
        'tags' => 'array',
    ];

    // ============================================================
    // Relationships
    // ============================================================

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class);
    }

    public function canonEntries(): HasMany
    {
        return $this->hasMany(CanonEntry::class);
    }

    public function referenceImages(): HasMany
    {
        return $this->hasMany(ReferenceImage::class);
    }

    public function timelineEvents(): HasMany
    {
        return $this->hasMany(TimelineEvent::class);
    }

    public function conflicts(): HasMany
    {
        return $this->hasMany(Conflict::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    // ============================================================
    // Scopes
    // ============================================================

    public function scopeActive($query)
    {
        return $query->where('is_archived', false)->where('status', 'active');
    }

    public function scopeArchived($query)
    {
        return $query->where('is_archived', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // ============================================================
    // Accessors & Mutators
    // ============================================================

    public function getVisualStyleUrlAttribute(): ?string
    {
        return $this->visual_style_image 
            ? asset('storage/' . $this->visual_style_image)
            : null;
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->thumbnail 
            ? asset('storage/' . $this->thumbnail)
            : null;
    }

    public function getProgressPercentAttribute(): string
    {
        return $this->progress . '%';
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active' && !$this->is_archived;
    }

    // ============================================================
    // Helper Methods
    // ============================================================

    public function getPrimaryReference(): ?ReferenceImage
    {
        return $this->referenceImages()->where('is_primary', true)->first();
    }

    public function getUnresolvedConflicts()
    {
        return $this->conflicts()->unresolved()->get();
    }

    public function archive(): void
    {
        $this->update([
            'is_archived' => true,
            'archived_at' => now(),
            'status' => 'archived',
        ]);
    }

    public function unarchive(): void
    {
        $this->update([
            'is_archived' => false,
            'archived_at' => null,
            'status' => 'active',
        ]);
    }

    public function setVisualStyle(string $path, ?string $description = null): void
    {
        $this->update([
            'visual_style_image' => $path,
            'visual_style_description' => $description,
        ]);
    }

    public function updateProgress(int $progress): void
    {
        $this->update(['progress' => min(100, max(0, $progress))]);
    }

    public function setMetadata(array $data): void
    {
        $this->update(['metadata' => array_merge($this->metadata ?? [], $data)]);
    }

    public function addTag(string $tag): void
    {
        $tags = $this->tags ?? [];
        if (!in_array($tag, $tags)) {
            $tags[] = $tag;
            $this->update(['tags' => $tags]);
        }
    }

    public function removeTag(string $tag): void
    {
        $tags = $this->tags ?? [];
        $this->update(['tags' => array_filter($tags, fn($t) => $t !== $tag)]);
    }
}
