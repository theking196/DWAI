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
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }
}