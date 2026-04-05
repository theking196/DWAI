<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AuthSeeder extends Seeder
{
    public function run(): void
    {
        // Default local user
        User::create([
            'name' => 'Developer',
            'email' => 'dev@local',
            'password' => Hash::make('password'),
        ]);
    }
}
