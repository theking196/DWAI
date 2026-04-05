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
            'user_id' => 1,
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'type' => $this->faker->randomElement(['Film', 'Comic', 'Music Video', 'TV Series', 'Short Film']),
            'thumbnail' => null,
            'visual_style_image' => null,
            'progress' => $this->faker->numberBetween(0, 100),
            'status' => $this->faker->randomElement(['draft', 'active', 'completed']),
        ];
    }

    public function boyWonder(): static
    {
        return $this->state(fn() => [
            'name' => 'Boy Wonder',
            'description' => 'A young hero\'s journey from ordinary to extraordinary in modern Lagos. A superhero comic series exploring identity and responsibility.',
            'type' => 'Comic',
            'progress' => 35,
            'status' => 'active',
        ]);
    }

    public function neuralNet(): static
    {
        return $this->state(fn() => [
            'name' => 'Neural Net',
            'description' => 'A sci-fi thriller about an AI consciousness awakening in a dystopian future.',
            'type' => 'TV Series',
            'progress' => 20,
            'status' => 'active',
        ]);
    }
}
