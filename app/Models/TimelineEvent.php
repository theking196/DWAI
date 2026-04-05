<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimelineEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'session_id',
        'title',
        'description',
        'type',
        'event_date',
        'order_index',
    ];

    protected $casts = [
        'event_date' => 'date',
        'order_index' => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    public function scopeEvents($query)
    {
        return $query->where('type', 'event');
    }

    public function scopeMilestones($query)
    {
        return $query->where('type', 'milestone');
    }
}
