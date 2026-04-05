<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
        'email_verified_at' => 'datetime',
    ];

    // ============================================================
    // Relationships
    // ============================================================

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function resolvedConflicts(): HasMany
    {
        return $this->hasMany(Conflict::class, 'resolved_by');
    }

    // ============================================================
    // Role Checks
    // ============================================================

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function canEdit(): bool
    {
        return in_array($this->role, ['admin', 'editor']);
    }

    public function canDelete(): bool
    {
        return $this->role === 'admin';
    }

    public function canManageSettings(): bool
    {
        return $this->role === 'admin';
    }

    public function canEditCanon(): bool
    {
        return in_array($this->role, ['admin', 'editor']);
    }

    public function isViewer(): bool
    {
        return $this->role === 'viewer';
    }
}
