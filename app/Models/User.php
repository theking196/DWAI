<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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

    /**
     * Check if user is admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user can edit (admin or editor).
     */
    public function canEdit(): bool
    {
        return in_array($this->role, ['admin', 'editor']);
    }

    /**
     * Check if user can delete (admin only).
     */
    public function canDelete(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user can manage settings (admin only).
     */
    public function canManageSettings(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user can edit canon entries.
     */
    public function canEditCanon(): bool
    {
        return in_array($this->role, ['admin', 'editor']);
    }

    /**
     * Check if user is viewer (read-only).
     */
    public function isViewer(): bool
    {
        return $this->role === 'viewer';
    }
}
