<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AIOutput extends Model
    protected $table = 'ai_outputs';
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
