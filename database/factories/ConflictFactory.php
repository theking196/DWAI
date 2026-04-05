<?php

namespace Database\Factories;

use App\Models\Conflict;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConflictFactory extends Factory
{
    protected $model = Conflict::class;

    public function definition(): array
    {
        return [
            'user_id' => 1,
            'project_id' => Project::factory(),
            'session_id' => null,
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->sentence(),
            'type' => $this->faker->randomElement(['timeline', 'canon', 'character', 'plot']),
            'severity' => $this->faker->randomElement(['low', 'medium', 'high']),
            'status' => 'unresolved',
            'resolved_by' => null,
            'resolved_at' => null,
        ];
    }
}
