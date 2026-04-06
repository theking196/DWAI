<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id', 'project_id', 'event_type', 'entity_type', 'entity_id',
        'description', 'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function project(): BelongsTo { return $this->belongsTo(Project::class); }

    public static function log(
        int $userId,
        string $eventType,
        string $description,
        array $options = []
    ): self {
        return static::create([
            'user_id' => $userId,
            'project_id' => $options['project_id'] ?? null,
            'event_type' => $eventType,
            'entity_type' => $options['entity_type'] ?? null,
            'entity_id' => $options['entity_id'] ?? null,
            'description' => $description,
            'metadata' => $options['metadata'] ?? null,
        ]);
    }

    public static function forProject(int $projectId, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('project_id', $projectId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public static function recent(int $userId, int $limit = 20): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public static function byEntity(string $type, int $id): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('entity_type', $type)
            ->where('entity_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getSummary(): array
    {
        return [
            'id' => $this->id,
            'event_type' => $this->event_type,
            'description' => $this->description,
            'entity' => $this->entity_type ? "{$this->entity_type}/{$this->entity_id}" : null,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}

    // ============================================================
    // Event Shortcuts
    // ============================================================

    public static function projectCreated(int $userId, Project $project): self
    {
        return self::log($userId, 'project.created', "Created project: {$project->name}", [
            'project_id' => $project->id,
            'entity_type' => 'project',
            'entity_id' => $project->id,
        ]);
    }

    public static function sessionStarted(int $userId, Session $session): self
    {
        return self::log($userId, 'session.started', "Started session: {$session->name}", [
            'project_id' => $session->project_id,
            'entity_type' => 'session',
            'entity_id' => $session->id,
        ]);
    }

    public static function canonEdited(int $userId, CanonEntry $canon): self
    {
        return self::log($userId, 'canon.edited', "Edited canon: {$canon->title}", [
            'project_id' => $canon->project_id,
            'entity_type' => 'canon',
            'entity_id' => $canon->id,
        ]);
    }

    public static function referenceUploaded(int $userId, ReferenceImage $ref): self
    {
        return self::log($userId, 'reference.uploaded', "Uploaded reference: {$ref->title}", [
            'project_id' => $ref->project_id,
            'entity_type' => 'reference',
            'entity_id' => $ref->id,
        ]);
    }

    public static function outputGenerated(int $userId, AIOutput $output): self
    {
        return self::log($userId, 'output.generated', "Generated {$output->type} output", [
            'project_id' => $output->session?->project_id,
            'entity_type' => 'output',
            'entity_id' => $output->id,
            'metadata' => ['type' => $output->type, 'status' => $output->status],
        ]);
    }

    public static function conflictResolved(int $userId, Conflict $conflict): self
    {
        return self::log($userId, 'conflict.resolved', "Resolved conflict: {$conflict->type}", [
            'project_id' => $conflict->project_id,
            'entity_type' => 'conflict',
            'entity_id' => $conflict->id,
        ]);
    }

    public static function memoryPromoted(int $userId, int $projectId, string $title): self
    {
        return self::log($userId, 'memory.promoted', "Promoted to canon: {$title}", [
            'project_id' => $projectId,
            'entity_type' => 'canon',
        ]);
    }

    // ============================================================
    // Detailed Log Entry
    // ============================================================

    /**
     * Get full details for audit trail.
     */
    public function getAuditDetails(): array
    {
        return [
            'id' => $this->id,
            'who' => [
                'user_id' => $this->user_id,
            ],
            'when' => [
                'created_at' => $this->created_at->toISOString(),
                'timestamp' => $this->created_at->timestamp,
            ],
            'what' => [
                'event_type' => $this->event_type,
                'entity_type' => $this->entity_type,
                'entity_id' => $this->entity_id,
                'project_id' => $this->project_id,
            ],
            'description' => $this->description,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Get entity details (the affected object).
     */
    public function getEntityDetails(): ?array
    {
        if (!$this->entity_type || !$this->entity_id) {
            return null;
        }

        $entity = match($this->entity_type) {
            'project' => Project::find($this->entity_id),
            'session' => Session::find($this->entity_id),
            'canon' => CanonEntry::find($this->entity_id),
            'reference' => ReferenceImage::find($this->entity_id),
            'output' => AIOutput::find($this->entity_id),
            'conflict' => Conflict::find($this->entity_id),
            'timeline' => TimelineEvent::find($this->entity_id),
            default => null,
        };

        if (!$entity) return null;

        return [
            'type' => $this->entity_type,
            'id' => $this->entity_id,
            'name' => $entity->name ?? $entity->title ?? "Entity #{$this->entity_id}",
        ];
    }

    /**
     * Format as human-readable audit entry.
     */
    public function toAuditString(): string
    {
        $when = $this->created_at->format('Y-m-d H:i:s');
        $what = $this->getEntityDetails();
        $entity = $what ? " [{$what['type']}: {$what['name']}]" : '';

        return "{$when} | {$this->event_type}{$entity} | {$this->description}";
    }

    /**
     * Search by event type.
     */
    public static function byEventType(string $eventType, ?int $projectId = null, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        $query = static::where('event_type', $eventType);
        
        if ($projectId) {
            $query->where('project_id', $projectId);
        }
        
        return $query->orderBy('created_at', 'desc')->limit($limit)->get();
    }

    /**
     * Get timeline for entity.
     */
    public static function getEntityTimeline(string $type, int $id): array
    {
        return static::where('entity_type', $type)
            ->where('entity_id', $id)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn($a) => [
                'when' => $a->created_at->toISOString(),
                'event' => $a->event_type,
                'description' => $a->description,
            ])
            ->toArray();
    }
