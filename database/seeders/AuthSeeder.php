<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AuthSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        User::create([
            'name' => 'Admin',
            'email' => 'admin@local',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
        ]);

        // Editor
        User::create([
            'name' => 'Editor',
            'email' => 'editor@local',
            'password' => Hash::make('editor123'),
            'role' => 'editor',
        ]);

        // Viewer
        User::create([
            'name' => 'Viewer',
            'email' => 'viewer@local',
            'password' => Hash::make('viewer123'),
            'role' => 'viewer',
        ]);
    }
}
