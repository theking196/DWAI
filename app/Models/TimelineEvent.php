<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimelineEvent extends Model
{
    protected $fillable = [
        'project_id', 'session_id', 'user_id', 'title', 'description',
        'order_index', 'event_timestamp', 'related_canon',
    ];

    protected $casts = [
        'order_index' => 'integer',
        'event_timestamp' => 'datetime',
        'related_canon' => 'array',
    ];

    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function session(): BelongsTo { return $this->belongsTo(Session::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public static function forProject(int $projectId): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('project_id', $projectId)->orderBy('order_index')->get();
    }

    public static function forSession(int $sessionId): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('session_id', $sessionId)->orderBy('order_index')->get();
    }

    public function addCanon(int $canonId): void
    {
        $related = $this->related_canon ?? [];
        if (!in_array($canonId, $related)) {
            $related[] = $canonId;
            $this->update(['related_canon' => $related]);
        }
    }

    public function removeCanon(int $canonId): void
    {
        $related = array_filter($this->related_canon ?? [], fn($id) => $id !== $canonId);
        $this->update(['related_canon' => array_values($related)]);
    }

    public function getCanonEntries(): array
    {
        if (empty($this->related_canon)) return [];
        return CanonEntry::whereIn('id', $this->related_canon)->get()->toArray();
    }

    public function reorder(int $newIndex): void
    {
        $this->update(['order_index' => $newIndex]);
    }

    public function getSummary(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'order_index' => $this->order_index,
            'event_timestamp' => $this->event_timestamp?->toISOString(),
            'related_canon_count' => count($this->related_canon ?? []),
        ];
    }
}
}
