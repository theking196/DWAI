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

    public function markAsPending(): void
    {
        $this->update(['status' => 'pending']);
    }

    public function markAsProcessing(): void
    {
        $this->update(['status' => 'processing']);
    }

    public function markAsCompleted(string $result): void
    {
        $this->update([
            'status' => 'completed',
            'result' => $result,
        ]);
    }

    public function markAsFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $error,
        ]);
    }
}
