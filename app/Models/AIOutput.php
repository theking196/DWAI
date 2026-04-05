<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AIOutput extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'prompt',
        'result',
        'type',
        'model',
        'metadata',
        'status',
        'error_message',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    public function markAsPending(): self
    {
        $this->update(['status' => 'pending']);
        return $this;
    }

    public function markAsProcessing(): self
    {
        $this->update(['status' => 'processing']);
        return $this;
    }

    public function markAsCompleted(string $result): self
    {
        $this->update(['status' => 'completed', 'result' => $result]);
        return $this;
    }

    public function markAsFailed(string $error): self
    {
        $this->update(['status' => 'failed', 'error_message' => $error]);
        return $this;
    }

    public function isText(): bool
    {
        return $this->type === 'text';
    }

    public function isImage(): bool
    {
        return $this->type === 'image';
    }
}

    // ============================================================
    // Additional Output Types
    // ============================================================

    public function isStoryboard(): bool { return $this->type === 'storyboard'; }
    public function isDirectorNotes(): bool { return $this->type === 'director_notes'; }
    public function isSceneDraft(): bool { return $this->type === 'scene_draft'; }

    public function getTypeLabel(): string
    {
        return match($this->type) {
            'text' => '📝 Text',
            'image' => '🎨 Image',
            'storyboard' => '🎬 Storyboard',
            'director_notes' => '📋 Director Notes',
            'scene_draft' => '✍️ Scene Draft',
            default => ucfirst($this->type ?? 'Unknown'),
        };
    }

    public function getResultPreview(int $length = 100): string
    {
        $result = $this->result ?? '';
        if (is_array($result)) {
            return json_encode($result);
        }
        return strlen($result) > $length ? substr($result, 0, $length) . '...' : $result;
    }

    public function getMetadata(string $key, $default = null)
    {
        return ($this->metadata ?? [])[$key] ?? $default;
    }

    public function setMetadata(string $key, $value): void
    {
        $meta = $this->metadata ?? [];
        $meta[$key] = $value;
        $this->update(['metadata' => $meta]);
    }

    public function getSummary(): array
    {
        return [
            'id' => $this->id,
            'session_id' => $this->session_id,
            'type' => $this->type,
            'type_label' => $this->getTypeLabel(),
            'model' => $this->model,
            'status' => $this->status,
            'preview' => $this->getResultPreview(50),
            'created_at' => $this->created_at->toISOString(),
        ];
    }

    public static function createTextOutput(Session $session, string $prompt, string $result, ?string $model = null): self
    {
        return self::create([
            'session_id' => $session->id,
            'prompt' => $prompt,
            'result' => $result,
            'type' => 'text',
            'model' => $model ?? 'mock-text-v1',
            'status' => 'completed',
        ]);
    }

    public static function createImageOutput(Session $session, string $prompt, array $images, ?string $model = null): self
    {
        return self::create([
            'session_id' => $session->id,
            'prompt' => $prompt,
            'result' => json_encode($images),
            'type' => 'image',
            'model' => $model ?? 'mock-image-v1',
            'status' => 'completed',
            'metadata' => ['image_count' => count($images)],
        ]);
    }

    public static function createStoryboardOutput(Session $session, string $prompt, array $frames, ?string $model = null): self
    {
        return self::create([
            'session_id' => $session->id,
            'prompt' => $prompt,
            'result' => json_encode($frames),
            'type' => 'storyboard',
            'model' => $model ?? 'mock-storyboard-v1',
            'status' => 'completed',
            'metadata' => ['frame_count' => count($frames)],
        ]);
    }

    public static function createDirectorNotes(Session $session, string $notes): self
    {
        return self::create([
            'session_id' => $session->id,
            'prompt' => 'Generated director notes',
            'result' => $notes,
            'type' => 'director_notes',
            'model' => 'auto-generated',
            'status' => 'completed',
        ]);
    }

    public static function createSceneDraft(Session $session, string $draft): self
    {
        return self::create([
            'session_id' => $session->id,
            'prompt' => 'Scene draft',
            'result' => $draft,
            'type' => 'scene_draft',
            'model' => 'auto-generated',
            'status' => 'completed',
        ]);
    }
