<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'type',
        'thumbnail',
        'visual_style_image',
        'progress',
        'status',
    ];

    protected $casts = [
        'progress' => 'integer',
    ];

    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class);
    }

    public function canonEntries(): HasMany
    {
        return $this->hasMany(CanonEntry::class);
    }

    public function referenceImages(): HasMany
    {
        return $this->hasMany(ReferenceImage::class);
    }
}