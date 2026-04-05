<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'project_id',
        'session_id',
        'action',
        'entity_type',
        'entity_id',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    public static function log(
        User $user,
        string $action,
        string $entityType,
        ?int $entityId = null,
        ?int $projectId = null,
        ?int $sessionId = null,
        ?array $metadata = null
    ): self {
        return self::create([
            'user_id' => $user->id,
            'project_id' => $projectId,
            'session_id' => $sessionId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'metadata' => $metadata,
        ]);
    }
}
