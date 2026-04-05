<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Conflict extends Model
{
    protected $fillable = [
        'project_id', 'session_id', 'user_id', 'type', 'description',
        'severity', 'status', 'source_type', 'source_id', 'suggested_fix',
    ];

    protected $casts = [
        'severity' => 'string',
        'status' => 'string',
    ];

    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function session(): BelongsTo { return $this->belongsTo(Session::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public static function forProject(int $projectId): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('project_id', $projectId)->orderBy('severity')->get();
    }

    public static function active(int $projectId): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('project_id', $projectId)
            ->whereIn('status', ['detected', 'acknowledged'])
            ->orderBy('severity')
            ->get();
    }

    public function acknowledge(): void
    {
        $this->update(['status' => 'acknowledged']);
    }

    public function resolve(?string $fix = null): void
    {
        $this->update([
            'status' => 'resolved',
            'suggested_fix' => $fix ?? $this->suggested_fix,
        ]);
    }

    public function ignore(): void
    {
        $this->update(['status' => 'ignored']);
    }

    public static function createFromDetection(int $projectId, array $conflict): self
    {
        return static::create([
            'project_id' => $projectId,
            'session_id' => $conflict['session_id'] ?? null,
            'user_id' => auth()->id(),
            'type' => $conflict['type'] ?? 'unknown',
            'description' => $conflict['message'] ?? '',
            'severity' => $conflict['severity'] ?? 'warning',
            'status' => 'detected',
            'source_type' => $conflict['source_type'] ?? null,
            'source_id' => $conflict['source_id'] ?? null,
            'suggested_fix' => $conflict['suggested_fix'] ?? null,
        ]);
    }

    public static function syncFromDetection(int $projectId): int
    {
        $service = app(\App\Services\AI\ConflictDetectionService::class);
        $conflicts = $service->detectAllConflicts($projectId);
        
        // Clear old detected conflicts
        static::where('project_id', $projectId)->where('status', 'detected')->delete();
        
        $count = 0;
        foreach ($conflicts as $category) {
            foreach ($category as $conflict) {
                static::createFromDetection($projectId, $conflict);
                $count++;
            }
        }
        
        return $count;
    }
}

    // ============================================================
    // Resolution Flow
    // ============================================================

    /**
     * Review conflict in detail.
     */
    public function getReviewDetails(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'description' => $this->description,
            'severity' => $this->severity,
            'status' => $this->status,
            'source_type' => $this->source_type,
            'source_id' => $this->source_id,
            'suggested_fix' => $this->suggested_fix,
            'related' => $this->getRelatedEntities(),
            'created_at' => $this->created_at->toISOString(),
        ];
    }

    /**
     * Get related entities for context.
     */
    protected function getRelatedEntities(): ?array
    {
        if (!$this->source_type || !$this->source_id) return null;

        return match($this->source_type) {
            'canon' => CanonEntry::find($this->source_id)?->getSummary(),
            'timeline' => TimelineEvent::find($this->source_id)?->getSummary(),
            'reference' => ReferenceImage::find($this->source_id)?->getSummary(),
            default => null,
        };
    }

    /**
     * Accept suggested fix and apply it.
     */
    public function acceptSuggestion(): array
    {
        if (!$this->suggested_fix) {
            return ['success' => false, 'message' => 'No suggestion to accept'];
        }

        // Apply fix based on type
        $applied = match($this->type) {
            'duplicate_character' => $this->fixDuplicateCharacter(),
            'timestamp_order' => $this->fixTimestampOrder(),
            'missing_reference' => $this->fixMissingReference(),
            default => false,
        };

        if ($applied) {
            $this->resolve('Accepted suggestion: ' . $this->suggested_fix);
            return ['success' => true, 'message' => 'Suggestion applied and resolved'];
        }

        return ['success' => false, 'message' => 'Could not apply suggestion'];
    }

    /**
     * Reject suggestion with reason.
     */
    public function rejectSuggestion(string $reason): void
    {
        $this->update([
            'status' => 'acknowledged',
            'suggested_fix' => null,
        ]);
        // Log rejection reason - could add a rejection_notes field
    }

    /**
     * Edit manually and resolve.
     */
    public function resolveManually(array $fix): void
    {
        $this->resolve($fix['notes'] ?? 'Manual resolution');
        
        // If there's a source, update it
        if ($this->source_type && $this->source_id) {
            match($this->source_type) {
                'canon' => $this->updateCanon($fix),
                'timeline' => $this->updateTimeline($fix),
                default => null,
            };
        }
    }

    /**
     * Fix duplicate character - keep latest, remove others.
     */
    protected function fixDuplicateCharacter(): bool
    {
        if ($this->source_type !== 'canon' || !$this->source_id) return false;
        
        $entry = CanonEntry::find($this->source_id);
        if (!$entry) return false;
        
        // Find and delete duplicates
        $duplicates = CanonEntry::where('project_id', $entry->project_id)
            ->where('type', 'character')
            ->where('title', $entry->title)
            ->where('id', '!=', $entry->id)
            ->get();
        
        foreach ($duplicates as $dup) {
            $dup->delete();
        }
        
        return true;
    }

    /**
     * Fix timestamp order - reorder events.
     */
    protected function fixTimestampOrder(): bool
    {
        if ($this->source_type !== 'timeline' || !$this->source_id) return false;
        
        $event = TimelineEvent::find($this->source_id);
        if (!$event) return false;
        
        TimelineEvent::orderByTimestamp($event->project_id);
        return true;
    }

    /**
     * Fix missing reference.
     */
    protected function fixMissingReference(): bool
    {
        // Could re-upload or link differently
        return true;
    }

    /**
     * Update canon entry with manual fix.
     */
    protected function updateCanon(array $fix): void
    {
        $entry = CanonEntry::find($this->source_id);
        if (!$entry) return;
        
        if (isset($fix['title'])) $entry->update(['title' => $fix['title']]);
        if (isset($fix['content'])) $entry->update(['content' => $fix['content']]);
    }

    /**
     * Update timeline event with manual fix.
     */
    protected function updateTimeline(array $fix): void
    {
        $event = TimelineEvent::find($this->source_id);
        if (!$event) return;
        
        if (isset($fix['title'])) $event->update(['title' => $fix['title']]);
        if (isset($fix['description'])) $event->update(['description' => $fix['description']]);
        if (isset($fix['order_index'])) $event->update(['order_index' => $fix['order_index']]);
    }
