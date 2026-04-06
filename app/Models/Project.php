<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

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
    // Summary Logic
    // ============================================================

    /**
     * Get complete project summary.
     */
    public function getSummary(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'status' => $this->status,
            'progress' => $this->progress,
            'is_archived' => $this->is_archived,
            'counts' => $this->getCounts(),
            'latest_activity' => $this->getLatestActivity(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Get count of all related entities.
     */
    public function getCounts(): array
    {
        return [
            'sessions' => $this->sessions()->count(),
            'canon_entries' => $this->canonEntries()->count(),
            'reference_images' => $this->referenceImages()->count(),
            'timeline_events' => $this->timelineEvents()->count(),
            'conflicts' => $this->conflicts()->count(),
            'unresolved_conflicts' => $this->conflicts()->unresolved()->count(),
            'ai_outputs' => $this->sessions()->withCount('aiOutputs')->get()->sum('ai_outputs_count'),
        ];
    }

    /**
     * Get latest activity across all related entities.
     */
    public function getLatestActivity(): ?array
    {
        // Get latest session
        $latestSession = $this->sessions()
            ->orderBy('updated_at', 'desc')
            ->first();

        // Get latest canon entry
        $latestCanon = $this->canonEntries()
            ->orderBy('updated_at', 'desc')
            ->first();

        // Get latest reference
        $latestRef = $this->referenceImages()
            ->orderBy('updated_at', 'desc')
            ->first();

        // Get latest AI output
        $latestOutput = AIOutput::whereHas('session', function ($q) {
            $q->where('project_id', $this->id);
        })->orderBy('created_at', 'desc')->first();

        // Get latest activity log
        $latestLog = $this->activityLogs()
            ->orderBy('created_at', 'desc')
            ->first();

        // Find the most recent
        $activities = array_filter([
            $latestSession ? ['type' => 'session', 'name' => $latestSession->name, 'date' => $latestSession->updated_at] : null,
            $latestCanon ? ['type' => 'canon', 'name' => $latestCanon->title, 'date' => $latestCanon->updated_at] : null,
            $latestRef ? ['type' => 'reference', 'name' => $latestRef->title, 'date' => $latestRef->updated_at] : null,
            $latestOutput ? ['type' => 'ai_output', 'name' => substr($latestOutput->prompt, 0, 30), 'date' => $latestOutput->created_at] : null,
            $latestLog ? ['type' => 'log', 'name' => $latestLog->action, 'date' => $latestLog->created_at] : null,
        ]);

        if (empty($activities)) {
            return null;
        }

        // Sort by date descending
        usort($activities, function ($a, $b) {
            return $b['date'] <=> $a['date'];
        });

        return $activities[0];
    }

    /**
     * Get brief summary for lists.
     */
    public function getBriefSummary(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'status' => $this->status,
            'progress' => $this->progress,
            'session_count' => $this->sessions()->count(),
            'canon_count' => $this->canonEntries()->count(),
            'reference_count' => $this->referenceImages()->count(),
            'updated_at' => $this->updated_at,
        ];
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
        $this->update(['is_archived' => true, 'archived_at' => now(), 'status' => 'archived']);
    }

    public function unarchive(): void
    {
        $this->update(['is_archived' => false, 'archived_at' => null, 'status' => 'active']);
    }

    public function setVisualStyle(string $path, ?string $description = null): void
    {
        $this->update(['visual_style_image' => $path, 'visual_style_description' => $description]);
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
