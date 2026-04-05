<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

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

    // Scopes - Type
    public function scopeCharacters($query) { return $query->where('type', 'character'); }
    public function scopeLocations($query) { return $query->where('type', 'location'); }
    public function scopeLore($query) { return $query->where('type', 'lore'); }
    public function scopeRules($query) { return $query->where('type', 'rule'); }
    public function scopeTimelineEvents($query) { return $query->where('type', 'timeline_event'); }
    public function scopeNotes($query) { return $query->where('type', 'note'); }

    // Scopes - Importance
    public function scopeCritical($query) { return $query->where('importance', 'critical'); }
    public function scopeImportant($query) { return $query->where('importance', 'important'); }
    public function scopeMinor($query) { return $query->where('importance', 'minor'); }

    // Scopes - By Project/Tag
    public function scopeForProject($query, int $projectId) { return $query->where('project_id', $projectId); }
    public function scopeWithTag($query, string $tag) { return $query->whereJsonContains('tags', $tag); }

    // ============================================================
    // Search - Keyword & Metadata
    // ============================================================

    /**
     * Full-text search across canon entries.
     * Ready for vector search integration.
     */
    public static function search(array $params): \Illuminate\Database\Eloquent\Builder
    {
        $query = self::query();
        
        // Project filter (required for ownership)
        if (!empty($params['project_id'])) {
            $query->where('project_id', $params['project_id']);
        }

        // Keyword search in title and content
        if (!empty($params['keyword'])) {
            $keyword = $params['keyword'];
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                  ->orWhere('content', 'like', "%{$keyword}%");
            });
        }

        // Type filter
        if (!empty($params['type'])) {
            $query->where('type', $params['type']);
        }

        // Multiple types
        if (!empty($params['types']) && is_array($params['types'])) {
            $query->whereIn('type', $params['types']);
        }

        // Importance filter
        if (!empty($params['importance'])) {
            $query->where('importance', $params['importance']);
        }

        // Tag filter
        if (!empty($params['tag'])) {
            $query->whereJsonContains('tags', $params['tag']);
        }

        // Multiple tags
        if (!empty($params['tags']) && is_array($params['tags'])) {
            foreach ($params['tags'] as $tag) {
                $query->whereJsonContains('tags', $tag);
            }
        }

        // Date range
        if (!empty($params['from_date'])) {
            $query->where('created_at', '>=', $params['from_date']);
        }
        if (!empty($params['to_date'])) {
            $query->where('created_at', '<=', $params['to_date']);
        }

        // Sort
        $sortBy = $params['sort_by'] ?? 'created_at';
        $sortDir = $params['sort_dir'] ?? 'desc';
        $query->orderBy($sortBy, $sortDir);

        return $query;
    }

    /**
     * Simple keyword search alias.
     */
    public static function searchByKeyword(string $keyword, ?int $projectId = null): \Illuminate\Database\Eloquent\Builder
    {
        return self::search([
            'keyword' => $keyword,
            'project_id' => $projectId,
        ]);
    }

    /**
     * Get search results with highlights.
     */
    public static function searchWithHighlights(array $params, int $perPage = 20): array
    {
        $results = self::search($params)->paginate($perPage);
        
        // Add highlight excerpts
        $results->getCollection()->transform(function ($entry) use ($params) {
            $keyword = $params['keyword'] ?? '';
            
            $entry->title_highlight = $keyword 
                ? preg_replace("/({$keyword})/i", '<mark>$1</mark>', $entry->title)
                : $entry->title;
                
            if ($entry->content && $keyword) {
                $excerpt = substr($entry->content, 0, 200);
                $entry->content_excerpt = preg_replace("/({$keyword})/i", '<mark>$1</mark>', $excerpt);
            } else {
                $entry->content_excerpt = $entry->content ? substr($entry->content, 0, 200) : null;
            }
            
            return $entry;
        });

        return [
            'data' => $results->items(),
            'meta' => [
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
            ],
        ];
    }

    /**
     * Get available tags for a project.
     */
    public static function getProjectTags(int $projectId): array
    {
        $entries = self::where('project_id', $projectId)->whereNotNull('tags')->get();
        $allTags = [];
        
        foreach ($entries as $entry) {
            if ($entry->tags) {
                $allTags = array_merge($allTags, $entry->tags);
            }
        }
        
        return array_unique($allTags);
    }

    /**
     * Group organization
     */
    public static function getOrganized(int $projectId): array
    {
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

    // Helpers
    public function setImportance(string $level): void {
        if (in_array($level, ['critical', 'important', 'minor', 'none'])) {
            $this->update(['importance' => $level]);
        }
    }

    public function addTag(string $tag): void {
        $tags = $this->tags ?? [];
        if (!in_array($tag, $tags)) {
            $tags[] = $tag;
            $this->update(['tags' => $tags]);
        }
    }

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
            'tags' => $this->tags,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}

    // ============================================================
    // Careful Merge - Preserve History
    // ============================================================

    /**
     * Merge new data into existing canon carefully.
     * Creates version history and doesn't overwrite.
     */
    public function mergeWithHistory(array $newData, ?string $reason = null): array
    {
        $changes = [];
        
        // Track what changed
        foreach (['title', 'content', 'image', 'tags', 'importance'] as $field) {
            if (isset($newData[$field]) && $newData[$field] !== ($this->$field ?? null)) {
                $changes[$field] = [
                    'old' => $this->$field,
                    'new' => $newData[$field],
                ];
            }
        }

        if (empty($changes)) {
            return ['merged' => false, 'reason' => 'No changes'];
        }

        // Create history entry before updating
        $historyEntry = [
            'timestamp' => now()->toISOString(),
            'reason' => $reason ?? 'Manual merge',
            'changes' => $changes,
            'user_id' => auth()->id(),
        ];

        // Get existing history
        $history = $this->metadata['merge_history'] ?? [];
        $history[] = $historyEntry;
        
        // Keep last 20 merge history entries
        if (count($history) > 20) {
            $history = array_slice($history, -20);
        }

        // Update with new data + history
        $this->update(array_merge($newData, [
            'metadata' => array_merge($this->metadata ?? [], ['merge_history' => $history]),
        ]));

        return ['merged' => true, 'changes' => $changes, 'history_count' => count($history)];
    }

    /**
     * Check if entry can be merged (validate before merge).
     */
    public function canMergeWith(array $newData): array
    {
        $issues = [];
        $warnings = [];

        // Check importance changes
        if (isset($newData['importance'])) {
            $currentImportance = $this->importance ?? 'none';
            $newImportance = $newData['importance'];
            
            // Warn if downgrading critical
            if ($currentImportance === 'critical' && in_array($newImportance, ['important', 'minor', 'none'])) {
                $warnings[] = "Downgrading from critical to {$newImportance}";
            }
        }

        // Check content length
        if (isset($newData['content']) && strlen($newData['content']) < 50 && strlen($this->content ?? '') > 200) {
            $warnings[] = "New content is significantly shorter than existing";
        }

        // Check for potential conflicts
        if (isset($newData['content']) && $this->content) {
            $similarity = similar_text($this->content, $newData['content']);
            if ($similarity < 30) {
                $warnings[] = "New content is very different - may conflict";
            }
        }

        return ['valid' => empty($issues), 'issues' => $issues, 'warnings' => $warnings];
    }

    /**
     * Create a new version instead of overwriting.
     */
    public function createVersion(array $newData, ?string $notes = null): CanonEntry
    {
        // Create new entry as a "version" of this one
        $version = self::create([
            'user_id' => $this->user_id,
            'project_id' => $this->project_id,
            'title' => $newData['title'] ?? $this->title . ' (v2)',
            'type' => $newData['type'] ?? $this->type,
            'content' => $newData['content'] ?? $this->content,
            'image' => $newData['image'] ?? $this->image,
            'tags' => array_merge($this->tags ?? [], $newData['tags'] ?? [], ['version_of_' . $this->id]),
            'importance' => $newData['importance'] ?? $this->importance,
            'metadata' => ['original_id' => $this->id, 'version_notes' => $notes],
        ]);

        return $version;
    }

    /**
     * Get merge history for this entry.
     */
    public function getMergeHistory(int $limit = 10): array
    {
        $history = $this->metadata['merge_history'] ?? [];
        return array_slice($history, -$limit);
    }

    /**
     * Compare with another entry (for conflict detection).
     */
    public function compareWith(CanonEntry $other): array
    {
        return [
            'same_type' => $this->type === $other->type,
            'title_similarity' => similar_text($this->title, $other->title),
            'content_similarity' => similar_text($this->content ?? '', $other->content ?? ''),
            'conflicts' => $this->detectConflicts($other),
        ];
    }

    /**
     * Detect potential conflicts.
     */
    protected function detectConflicts(CanonEntry $other): array
    {
        $conflicts = [];

        // Same type + high similarity = potential duplicate
        if ($this->type === $other->type && similar_text($this->title, $other->title) > 70) {
            $conflicts[] = ['type' => 'duplicate', 'message' => 'Similar entry exists'];
        }

        // Timeline conflict
        if ($this->type === 'timeline_event' && $other->type === 'timeline_event') {
            // Could add timeline-specific conflict detection
        }

        return $conflicts;
    }

    // ============================================================
    // Version History
    // ============================================================

    /**
     * Create a version snapshot before making changes.
     */
    public function createVersion(?string $summary = null, ?array $changes = null): CanonVersion
    {
        return CanonVersion::create([
            'canon_entry_id' => $this->id,
            'user_id' => auth()->id() ?? $this->user_id,
            'title' => $this->title,
            'content' => $this->content,
            'type' => $this->type,
            'image' => $this->image,
            'tags' => $this->tags,
            'importance' => $this->importance,
            'change_summary' => $summary,
            'changes' => $changes,
            'created_at' => now(),
        ]);
    }

    /**
     * Update with automatic version creation.
     */
    public function updateWithVersion(array $data, ?string $summary = null): array
    {
        // Create version of current state first
        $this->createVersion($summary, $data);

        // Now update
        $this->update($data);

        return ['updated' => true, 'version_created' => true];
    }

    /**
     * Get version history.
     */
    public function getVersionHistory(int $limit = 20): array
    {
        return CanonVersion::where('canon_entry_id', $this->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(fn($v) => $v->getSummary())
            ->toArray();
    }

    /**
     * Restore to a specific version.
     */
    public function restoreToVersion(int $versionId): ?CanonEntry
    {
        $version = CanonVersion::find($versionId);
        
        if (!$version || $version->canon_entry_id !== $this->id) {
            return null;
        }

        // Create version of current state before restoring
        $this->createVersion('Before restore to v' . $versionId);

        // Restore
        $this->update([
            'title' => $version->title,
            'content' => $version->content,
            'type' => $version->type,
            'image' => $version->image,
            'tags' => $version->tags,
            'importance' => $version->importance,
        ]);

        return $this->fresh();
    }

    /**
     * Compare current state with a version.
     */
    public function diffFromVersion(int $versionId): ?array
    {
        $version = CanonVersion::find($versionId);
        
        if (!$version || $version->canon_entry_id !== $this->id) {
            return null;
        }

        return [
            'title' => ['old' => $version->title, 'new' => $this->title],
            'content' => ['old' => $version->content, 'new' => $this->content],
            'type' => ['old' => $version->type, 'new' => $this->type],
            'importance' => ['old' => $version->importance, 'new' => $this->importance],
            'tags' => ['old' => $version->tags, 'new' => $this->tags],
        ];
    }
