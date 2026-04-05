<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Asset extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'project_id', 'session_id', 'canon_entry_id',
        'file_name', 'original_name', 'file_path', 'mime_type', 'file_size', 'extension',
        'title', 'description', 'type', 'tags', 'metadata',
        'is_primary', 'sort_order',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'tags' => 'array',
        'metadata' => 'array',
        'is_primary' => 'boolean',
        'sort_order' => 'integer',
    ];

    // Relationships
    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function session(): BelongsTo { return $this->belongsTo(Session::class); }
    public function canonEntry(): BelongsTo { return $this->belongsTo(CanonEntry::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    // Scopes - By type
    public function scopeImages($query) { return $query->where('type', 'image'); }
    public function scopeAudio($query) { return $query->where('type', 'audio'); }
    public function scopeVideo($query) { return $query->where('type', 'video'); }
    public function scopeDocuments($query) { return $query->where('type', 'document'); }
    public function scopeModels($query) { return $query->where('type', 'model'); }

    // Scopes - By entity
    public function scopeForProject($query, int $projectId) { return $query->where('project_id', $projectId); }
    public function scopeForSession($query, int $sessionId) { return $query->where('session_id', $sessionId); }
    public function scopeForCanon($query, int $canonId) { return $query->where('canon_entry_id', $canonId); }
    public function scopePrimary($query) { return $query->where('is_primary', true); }

    // ============================================================
    // Search
    // ============================================================

    public static function search(array $params): \Illuminate\Database\Eloquent\Builder
    {
        $query = self::query();

        if (!empty($params['project_id'])) {
            $query->where('project_id', $params['project_id']);
        }

        // Keyword search
        if (!empty($params['keyword'])) {
            $keyword = $params['keyword'];
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                  ->orWhere('description', 'like', "%{$keyword}%")
                  ->orWhere('file_name', 'like', "%{$keyword}%")
                  ->orWhere('original_name', 'like', "%{$keyword}%");
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

        // Tag filter
        if (!empty($params['tag'])) {
            $query->whereJsonContains('tags', $params['tag']);
        }

        // Session link
        if (isset($params['session_id'])) {
            if ($params['session_id'] === null) {
                $query->whereNull('session_id');
            } else {
                $query->where('session_id', $params['session_id']);
            }
        }

        // Canon link
        if (isset($params['canon_entry_id'])) {
            if ($params['canon_entry_id'] === null) {
                $query->whereNull('canon_entry_id');
            } else {
                $query->where('canon_entry_id', $params['canon_entry_id']);
            }
        }

        // Size range
        if (!empty($params['min_size'])) {
            $query->where('file_size', '>=', $params['min_size']);
        }
        if (!empty($params['max_size'])) {
            $query->where('file_size', '<=', $params['max_size']);
        }

        // Extension
        if (!empty($params['extension'])) {
            $query->where('extension', $params['extension']);
        }

        // Sort
        $query->orderBy($params['sort_by'] ?? 'created_at', $params['sort_dir'] ?? 'desc');

        return $query;
    }

    // ============================================================
    // Organize / Group
    // ============================================================

    public static function getOrganized(int $projectId): array
    {
        $assets = self::where('project_id', $projectId)->get();

        return [
            'by_type' => $assets->groupBy('type'),
            'by_extension' => $assets->groupBy('extension'),
            'by_session' => $assets->groupBy('session_id'),
            'by_tag' => $assets->groupBy(fn($a) => $a->tags ? implode(', ', $a->tags) : 'untagged'),
            'total_size' => $assets->sum('file_size'),
            'count' => $assets->count(),
        ];
    }

    public static function getProjectStats(int $projectId): array
    {
        $assets = self::where('project_id', $projectId);
        
        return [
            'total' => $assets->count(),
            'by_type' => $assets->clone()->groupBy('type')->map->count(),
            'total_size' => $assets->sum('file_size'),
            'total_size_formatted' => self::formatSize($assets->sum('file_size')),
        ];
    }

    // ============================================================
    // Helpers
    // ============================================================

    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->file_path);
    }

    public function getSizeFormattedAttribute(): string
    {
        return self::formatSize($this->file_size);
    }

    public static function formatSize(?int $bytes): string
    {
        if (!$bytes) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = floor(log($bytes, 1024));
        return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
    }

    public static function detectType(string $mimeType): string
    {
        if (str_starts_with($mimeType, 'image/')) return 'image';
        if (str_starts_with($mimeType, 'audio/')) return 'audio';
        if (str_starts_with($mimeType, 'video/')) return 'video';
        if (str_contains($mimeType, 'pdf') || str_contains($mimeType, 'document')) return 'document';
        if (str_contains($mimeType, 'model') || str_contains($mimeType, 'obj') || str_contains($mimeType, 'gltf')) return 'model';
        return 'other';
    }

    public function setAsPrimary(): void
    {
        // Clear other primaries for same entity
        self::where('project_id', $this->project_id)
            ->where('id', '!=', $this->id)
            ->update(['is_primary' => false]);

        $this->update(['is_primary' => true]);
    }

    public function addTag(string $tag): void
    {
        $tags = $this->tags ?? [];
        if (!in_array($tag, $tags)) {
            $tags[] = $tag;
            $this->update(['tags' => $tags]);
        }
    }

    public function getSummary(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'file_name' => $this->file_name,
            'type' => $this->type,
            'size' => $this->file_size,
            'size_formatted' => $this->size_formatted,
            'url' => $this->url,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
