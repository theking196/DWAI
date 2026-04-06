<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChangeHistory extends Model
{
    protected $fillable = [
        'user_id', 'entity_type', 'entity_id', 'field_name',
        'old_value', 'new_value', 'change_type',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public static function record(
        int $userId,
        string $entityType,
        int $entityId,
        string $fieldName,
        $oldValue,
        $newValue,
        string $changeType = 'update'
    ): self {
        return static::create([
            'user_id' => $userId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'field_name' => $fieldName,
            'old_value' => is_array($oldValue) ? json_encode($oldValue) : $oldValue,
            'new_value' => is_array($newValue) ? json_encode($newValue) : $newValue,
            'change_type' => $changeType,
        ]);
    }

    public static function forEntity(string $type, int $id): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('entity_type', $type)
            ->where('entity_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public static function fieldHistory(string $type, int $id, string $field): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('entity_type', $type)
            ->where('entity_id', $id)
            ->where('field_name', $field)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getDiff(): array
    {
        return [
            'field' => $this->field_name,
            'before' => $this->old_value,
            'after' => $this->new_value,
            'changed_at' => $this->created_at->toISOString(),
        ];
    }
}

    // ============================================================
    // Helpers
    // ============================================================

    public static function recordCanonEdit(int $userId, CanonEntry $canon, array $changes): void
    {
        foreach ($changes as $field => $newValue) {
            $oldValue = $canon->getOriginal($field);
            
            if ($oldValue != $newValue) {
                self::record($userId, 'canon', $canon->id, $field, $oldValue, $newValue, 'update');
            }
        }
    }

    public static function recordProjectUpdate(int $userId, Project $project, array $changes): void
    {
        foreach ($changes as $field => $newValue) {
            $oldValue = $project->getOriginal($field);
            
            if ($oldValue != $newValue) {
                self::record($userId, 'project', $project->id, $field, $oldValue, $newValue, 'update');
            }
        }
    }

    public static function getFieldDiff(string $type, int $id, string $field): array
    {
        $history = self::fieldHistory($type, $id, $field);
        
        return $history->map(fn($h) => $h->getDiff())->toArray();
    }
