<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferenceImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'project_id', 'session_id', 'canon_entry_id', 'style_group_id',
        'title', 'description', 'file_path', 'file_name', 'file_size', 'mime_type',
        'is_primary', 'sort_order',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'sort_order' => 'integer',
    ];

    // Relationships
    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function session(): BelongsTo { return $this->belongsTo(Session::class); }
    public function canonEntry(): BelongsTo { return $this->belongsTo(CanonEntry::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    // Scopes
    public function scopeForProject($query, int $projectId) { return $query->where('project_id', $projectId); }
    public function scopeForSession($query, int $sessionId) { return $query->where('session_id', $sessionId); }
    public function scopeForCanon($query, int $canonId) { return $query->where('canon_entry_id', $canonId); }
    public function scopeForStyleGroup($query, int $groupId) { return $query->where('style_group_id', $groupId); }
    public function scopePrimary($query) { return $query->where('is_primary', true); }

    // Get by entity type
    public static function getForEntity(string $entityType, int $entityId): \Illuminate\Database\Eloquent\Collection
    {
        return match($entityType) {
            'project' => self::forProject($entityId)->get(),
            'session' => self::forSession($entityId)->get(),
            'canon' => self::forCanon($entityId)->get(),
            'style_group' => self::forStyleGroup($entityId)->get(),
            default => collect(),
        };
    }

    // Set as primary for entity
    public function setAsPrimary(): void
    {
        // Clear other primaries for same entity
        if ($this->session_id) {
            self::where('session_id', $this->session_id)->where('id', '!=', $this->id)->update(['is_primary' => false]);
        } elseif ($this->canon_entry_id) {
            self::where('canon_entry_id', $this->canon_entry_id)->where('id', '!=', $this->id)->update(['is_primary' => false]);
        } elseif ($this->style_group_id) {
            self::where('style_group_id', $this->style_group_id)->where('id', '!=', $this->id)->update(['is_primary' => false]);
        } elseif ($this->project_id) {
            self::where('project_id', $this->project_id)->where('id', '!=', $this->id)->update(['is_primary' => false]);
        }

        $this->update(['is_primary' => true]);
    }

    // Get URL (assuming storage/app/public references)
    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->file_path);
    }

    public function getSummary(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'url' => $this->url,
            'is_primary' => $this->is_primary,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
