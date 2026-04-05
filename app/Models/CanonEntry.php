<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CanonEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'title',
        'type',
        'content',
        'image',
        'tags',
    ];

    protected $casts = [
        'tags' => 'array',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}