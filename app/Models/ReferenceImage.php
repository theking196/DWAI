<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferenceImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'title',
        'description',
        'path',
        'type',
        'size',
        'mime_type',
        'is_primary',
    ];

    protected $casts = [
        'size' => 'integer',
        'is_primary' => 'boolean',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function markAsPrimary()
    {
        // Remove primary from others
        $this->project->referenceImages()->update(['is_primary' => false]);
        
        // Set this as primary
        $this->update(['is_primary' => true]);
    }

    public function getUrlAttribute()
    {
        return asset('storage/' . $this->path);
    }
}
