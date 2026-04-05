<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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

    public function timelineEvents(): HasMany
    {
        return $this->hasMany(TimelineEvent::class);
    }

    public function conflicts(): HasMany
    {
        return $this->hasMany(Conflict::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function getPrimaryReference(): ?ReferenceImage
    {
        return $this->referenceImages()->where('is_primary', true)->first();
    }

    public function getUnresolvedConflicts()
    {
        return $this->conflicts()->unresolved()->get();
    }
}
