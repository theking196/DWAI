<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Embedding extends Model
{
    protected $fillable = [
        'user_id', 'entity_type', 'entity_id', 'embedding_vector', 'dimensions',
        'model', 'chunk_text', 'metadata', 'status', 'error_message',
    ];

    protected $casts = [
        'metadata' => 'array',
        'dimensions' => 'integer',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public static function forCanon(CanonEntry $entry): self
    {
        return self::create([
            'user_id' => $entry->user_id,
            'entity_type' => 'canon',
            'entity_id' => $entry->id,
            'chunk_text' => substr($entry->title . ' ' . ($entry->content ?? ''), 0, 2000),
            'status' => 'pending',
        ]);
    }

    public static function forReference(ReferenceImage $ref): self
    {
        return self::create([
            'user_id' => $ref->user_id,
            'entity_type' => 'reference',
            'entity_id' => $ref->id,
            'chunk_text' => $ref->title . ' ' . ($ref->description ?? ''),
            'status' => 'pending',
        ]);
    }

    public static function forProject(Project $project): self
    {
        return self::create([
            'user_id' => $project->user_id,
            'entity_type' => 'project',
            'entity_id' => $project->id,
            'chunk_text' => $project->name . ' ' . ($project->description ?? '') . ' ' . ($project->style_notes ?? ''),
            'status' => 'pending',
        ]);
    }

    public static function forSession(Session $session): self
    {
        return self::create([
            'user_id' => $session->user_id,
            'entity_type' => 'session',
            'entity_id' => $session->id,
            'chunk_text' => $session->name . ' ' . ($session->notes ?? '') . ' ' . ($session->temp_notes ?? ''),
            'status' => 'pending',
        ]);
    }

    public function markProcessed(string $vector, ?string $model = null): void
    {
        $this->update([
            'embedding_vector' => $vector,
            'model' => $model,
            'status' => 'processed',
        ]);
    }

    public function markFailed(string $error): void
    {
        $this->update(['status' => 'failed', 'error_message' => $error]);
    }
}
