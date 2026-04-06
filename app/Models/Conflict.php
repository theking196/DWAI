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
