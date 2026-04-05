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
