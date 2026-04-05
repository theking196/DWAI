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
            'project_id' => Project::factory(),
            'name' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(),
            'type' => $this->faker->randomElement(['brainstorm', 'script', 'storyboard', 'edit']),
            'status' => $this->faker->randomElement(['active', 'completed', 'archived']),
            'output_count' => $this->faker->numberBetween(0, 20),
        ];
    }

    public function episode(): static
    {
        return $this->state(fn(array $attributes) => [
            'name' => 'Episode ' . $this->faker->numberBetween(1, 10),
            'type' => 'script',
            'status' => 'active',
        ]);
    }
}