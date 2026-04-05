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

    // ============================================================
    // Versioning
    // ============================================================

    /**
     * Create a new version of this output.
     */
    public function createNewVersion(string $newResult, ?string $reason = null): self
    {
        // Mark current as not current
        $this->update(['is_current' => false]);

        // Create new version
        return self::create([
            'session_id' => $this->session_id,
            'prompt' => $this->prompt,
            'result' => $newResult,
            'type' => $this->type,
            'model' => $this->model,
            'metadata' => array_merge($this->metadata ?? [], [
                'previous_version' => $this->version,
                'version_note' => $reason,
            ]),
            'status' => 'completed',
            'version' => $this->version + 1,
            'parent_output_id' => $this->id,
            'is_current' => true,
        ]);
    }

    /**
     * Get all versions of this output.
     */
    public function getVersionHistory(): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('parent_output_id', $this->id)
            ->orWhere('id', $this->id)
            ->orderBy('version', 'desc')
            ->get();
    }

    /**
     * Get specific version.
     */
    public function getVersion(int $version): ?self
    {
        return self::where('parent_output_id', $this->id)
            ->where('version', $version)
            ->first();
    }

    /**
     * Restore to a specific version (creates new current version).
     */
    public function restoreToVersion(int $version): ?self
    {
        $oldVersion = $this->getVersion($version);
        
        if (!$oldVersion) {
            return null;
        }

        return $this->createNewVersion($oldVersion->result, "Restored from v{$version}");
    }

    /**
     * Get previous version.
     */
    public function getPreviousVersion(): ?self
    {
        return self::where('parent_output_id', $this->parent_output_id)
            ->where('version', $this->version - 1)
            ->first();
    }

    /**
     * Get next version.
     */
    public function getNextVersion(): ?self
    {
        return self::where('parent_output_id', $this->parent_output_id)
            ->where('version', $this->version + 1)
            ->first();
    }

    // ============================================================
    // Session Memory Integration
    // ============================================================

    /**
     * Save output to session memory as draft.
     */
    public function saveToSessionDraft(): void
    {
        $session = $this->session;
        
        // Append to draft text
        $currentDraft = $session->draft_text ?? '';
        $separator = $currentDraft ? "\n\n---\n\n" : "";
        $session->updateDraftText($currentDraft . $separator . $this->result);
        
        $this->setMetadata('saved_to_draft', true);
    }

    /**
     * Save output to session memory as reference.
     */
    public function saveToSessionReferences(): void
    {
        $session = $this->session;
        
        $ref = [
            'output_id' => $this->id,
            'type' => $this->type,
            'preview' => $this->getResultPreview(100),
            'model' => $this->model,
            'added_at' => now()->toISOString(),
        ];
        
        $session->addSessionReference($ref);
        $this->setMetadata('saved_to_references', true);
    }

    /**
     * Save output to session notes.
     */
    public function saveToSessionNotes(string $note = null): void
    {
        $session = $this->session;
        
        $noteText = $note ?? "Output #{$this->id} ({$this->type})";
        $session->appendTempNotes($noteText . ": " . $this->getResultPreview(200));
        
        $this->setMetadata('saved_to_notes', true);
    }

    /**
     * Promote this output to canon entry.
     */
    public function promoteToCanon(array $options = []): ?CanonEntry
    {
        $session = $this->session;
        
        return CanonCandidate::createFromSession($session, [
            'title' => $options['title'] ?? "Output #{$this->id}",
            'type' => $options['type'] ?? $this->mapTypeToCanon($this->type),
            'content' => $this->result,
            'tags' => $options['tags'] ?? ['from-output', $this->type],
            'importance' => $options['importance'] ?? 'minor',
        ]);
    }

    /**
     * Map output type to canon type.
     */
    protected function mapTypeToCanon(string $outputType): string
    {
        return match($outputType) {
            'text', 'scene_draft' => 'note',
            'image' => 'artifact',
            'storyboard' => 'timeline_event',
            'director_notes' => 'lore',
            default => 'note',
        };
    }
