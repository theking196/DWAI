<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'type' => $this->faker->randomElement(['Film', 'Comic', 'Music Video']),
            'thumbnail' => null,
            'visual_style_image' => null,
            'progress' => $this->faker->numberBetween(0, 100),
            'status' => $this->faker->randomElement(['draft', 'active', 'completed']),
        ];
    }

    public function boyWonder(): static
    {
        return $this->state(fn(array $attributes) => [
            'name' => 'Boy Wonder',
            'description' => 'A young hero\'s journey from ordinary to extraordinary in modern Lagos.',
            'type' => 'Comic',
            'progress' => 35,
            'status' => 'active',
        ]);
    }
}