<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Session;
use Illuminate\Database\Eloquent\Factories\Factory;

class SessionFactory extends Factory
{
    protected $model = Session::class;

    public function definition(): array
    {
        return [
            'user_id' => 1,
            'project_id' => Project::factory(),
            'name' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(),
            'type' => $this->faker->randomElement(['brainstorm', 'script', 'storyboard', 'edit']),
            'status' => $this->faker->randomElement(['active', 'completed']),
            'output_count' => $this->faker->numberBetween(0, 10),
        ];
    }
}
