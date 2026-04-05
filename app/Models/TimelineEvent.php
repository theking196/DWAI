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

    // ============================================================
    // Ordering & Sequence Logic
    // ============================================================

    /**
     * Auto-order events by timestamp.
     */
    public static function orderByTimestamp(int $projectId): void
    {
        $events = static::where('project_id', $projectId)
            ->orderBy('event_timestamp', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        $events->each(function ($event, $index) {
            $event->update(['order_index' => $index + 1]);
        });
    }

    /**
     * Detect story beats/sequences from event titles.
     */
    public static function detectSequences(int $projectId): array
    {
        $events = static::forProject($projectId);
        $sequences = [];
        $currentSequence = [];
        $sequenceNum = 1;

        foreach ($events as $event) {
            // Detect sequence patterns in title
            $isNewSequence = preg_match('/^(chapter|act|part|scene|episode|beat)\s*(\d+)/i', $event->title, $matches);
            
            if ($isNewSequence && !empty($currentSequence)) {
                $sequences[] = [
                    'sequence' => $sequenceNum++,
                    'events' => $currentSequence,
                ];
                $currentSequence = [];
            }
            
            $currentSequence[] = [
                'id' => $event->id,
                'title' => $event->title,
                'order' => $event->order_index,
            ];
        }

        if (!empty($currentSequence)) {
            $sequences[] = [
                'sequence' => $sequenceNum,
                'events' => $currentSequence,
            ];
        }

        return $sequences;
    }

    /**
     * Validate timeline consistency.
     */
    public static function validateTimeline(int $projectId): array
    {
        $events = static::forProject($projectId);
        $issues = [];

        // Check for gaps in order
        $orders = $events->pluck('order_index')->toArray();
        $expected = range(1, count($events));
        $gaps = array_diff($expected, $orders);
        
        if (!empty($gaps)) {
            $issues[] = ['type' => 'gap', 'message' => 'Missing order indices: ' . implode(', ', $gaps)];
        }

        // Check for duplicate orders
        $duplicates = $orders;
        if (count($orders) !== count(array_unique($orders))) {
            $issues[] = ['type' => 'duplicate', 'message' => 'Duplicate order indices found'];
        }

        // Check for events without order
        $unordered = $events->filter(fn($e) => $e->order_index === 0);
        if ($unordered->isNotEmpty()) {
            $issues[] = ['type' => 'unordered', 'message' => $unordered->count() . ' events have no order'];
        }

        // Check timestamp consistency (future dates in past sequence)
        foreach ($events as $i => $event) {
            if ($event->event_timestamp && $i > 0) {
                $prev = $events[$i - 1];
                if ($prev->event_timestamp && $event->event_timestamp < $prev->event_timestamp) {
                    $issues[] = ['type' => 'timestamp_order', 'message' => "Event '{$event->title}' has timestamp before previous event"];
                }
            }
        }

        return [
            'valid' => empty($issues),
            'issues' => $issues,
            'event_count' => $events->count(),
        ];
    }

    /**
     * Suggest best position for new event.
     */
    public static function suggestPosition(int $projectId, ?string $beforeTitle = null): int
    {
        if ($beforeTitle) {
            $beforeEvent = static::where('project_id', $projectId)
                ->where('title', 'like', "%{$beforeTitle}%")
                ->first();
            
            if ($beforeEvent) {
                return $beforeEvent->order_index;
            }
        }

        $maxOrder = static::where('project_id', $projectId)->max('order_index') ?? 0;
        return $maxOrder + 1;
    }

    /**
     * Reorder by dragging - move event to new position.
     */
    public static function reorderEvent(int $eventId, int $newIndex): void
    {
        $event = static::findOrFail($eventId);
        $oldIndex = $event->order_index;

        if ($oldIndex === $newIndex) return;

        $events = static::where('project_id', $event->project_id)
            ->where('id', '!=', $eventId)
            ->orderBy('order_index')
            ->get();

        // Shift others
        if ($newIndex > $oldIndex) {
            // Moving down
            foreach ($events as $e) {
                if ($e->order_index > $oldIndex && $e->order_index <= $newIndex) {
                    $e->update(['order_index' => $e->order_index - 1]);
                }
            }
        } else {
            // Moving up
            foreach ($events as $e) {
                if ($e->order_index >= $newIndex && $e->order_index < $oldIndex) {
                    $e->update(['order_index' => $e->order_index + 1]);
                }
            }
        }

        $event->update(['order_index' => $newIndex]);
    }
