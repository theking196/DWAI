<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CanonEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'project_id', 'title', 'type', 'content', 'image', 'tags', 'importance',
    ];

    protected $casts = [
        'tags' => 'array',
    ];

    // Relationships
    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    // Scopes - Group by type
    public function scopeCharacters($query) { return $query->where('type', 'character'); }
    public function scopeLocations($query) { return $query->where('type', 'location'); }
    public function scopeEvents($query) { return $query->where('type', 'event'); }
    public function scopeRules($query) { return $query->where('type', 'rule'); }
    public function scopeArtifacts($query) { return $query->where('type', 'artifact'); }

    // Scopes - By importance
    public function scopeCritical($query) { return $query->where('importance', 'critical'); }
    public function scopeImportant($query) { return $query->where('importance', 'important'); }
    public function scopeMinor($query) { return $query->where('importance', 'minor'); }

    // Scope - By project
    public function scopeForProject($query, int $projectId) { return $query->where('project_id', $projectId); }

    // Scope - By tag
    public function scopeWithTag($query, string $tag) { return $query->whereJsonContains('tags', $tag); }

    // Scope - Search
    public function scopeSearch($query, string $search) {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")->orWhere('content', 'like', "%{$search}%");
        });
    }

    // Organize/Group Methods
    public function groupByType(): \Illuminate\Support\Collection {
        return self::where('project_id', $this->project_id)
            ->get()
            ->groupBy('type');
    }

    public function groupByTag(): \Illuminate\Support\Collection {
        return self::where('project_id', $this->project_id)
            ->get()
            ->groupBy(fn($entry) => $entry->tags ? implode(', ', $entry->tags) : 'untagged');
    }

    public function groupByImportance(): \Illuminate\Support\Collection {
        return self::where('project_id', $this->project_id)
            ->orderByRaw("FIELD(importance, 'critical', 'important', 'minor', 'none')")
            ->get()
            ->groupBy('importance');
    }

    // Get organized canon for a project
    public static function getOrganized(int $projectId): array {
        $entries = self::where('project_id', $projectId)->get();
        
        return [
            'by_type' => $entries->groupBy('type'),
            'by_tag' => $entries->groupBy(fn($e) => $e->tags ? implode(', ', $e->tags) : 'untagged'),
            'by_importance' => $entries->groupBy('importance'),
            'by_date' => $entries->sortBy('created_at')->groupBy(fn($e) => $e->created_at->format('Y-m')),
            'count' => $entries->count(),
        ];
    }

    // Accessors
    public function getImportanceLabelAttribute(): string {
        return match($this->importance) {
            'critical' => '🔴 Critical',
            'important' => '🟡 Important',
            'minor' => '🟢 Minor',
            default => '⚪ None',
        };
    }

    // Helper to set importance
    public function setImportance(string $level): void {
        if (in_array($level, ['critical', 'important', 'minor', 'none'])) {
            $this->update(['importance' => $level]);
        }
    }

    // Add tag
    public function addTag(string $tag): void {
        $tags = $this->tags ?? [];
        if (!in_array($tag, $tags)) {
            $tags[] = $tag;
            $this->update(['tags' => $tags]);
        }
    }

    // Remove tag
    public function removeTag(string $tag): void {
        $tags = $this->tags ?? [];
        $this->update(['tags' => array_filter($tags, fn($t) => $t !== $tag)]);
    }

    public function getSummary(): array {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'type' => $this->type,
            'importance' => $this->importance,
            'importance_label' => $this->importance_label,
            'tags' => $this->tags,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
