<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AuthSeeder extends Seeder
{
    public function run(): void
    {
        // Admin - full access
        User::create([
            'name' => 'Admin',
            'email' => 'admin@local',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
        ]);

        // Editor - can edit, cannot delete
        User::create([
            'name' => 'Editor',
            'email' => 'editor@local',
            'password' => Hash::make('editor123'),
            'role' => 'editor',
        ]);

        // Viewer - read only
        User::create([
            'name' => 'Viewer',
            'email' => 'viewer@local',
            'password' => Hash::make('viewer123'),
            'role' => 'viewer',
        ]);
    }
}
