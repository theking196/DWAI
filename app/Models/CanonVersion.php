<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CanonVersion extends Model
{
    public $timestamps = false;
    protected $table = 'canon_versions';

    protected $fillable = [
        'canon_entry_id', 'user_id', 'title', 'content', 'type', 'image', 'tags', 'importance',
        'change_summary', 'changes', 'created_at',
    ];

    protected $casts = [
        'tags' => 'array',
        'changes' => 'array',
        'created_at' => 'datetime',
    ];

    public function canonEntry(): BelongsTo
    {
        return $this->belongsTo(CanonEntry::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getSummary(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'type' => $this->type,
            'change_summary' => $this->change_summary,
            'created_at' => $this->created_at->toISOString(),
            'user_id' => $this->user_id,
        ];
    }
}
