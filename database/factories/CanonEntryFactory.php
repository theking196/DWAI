<?php

namespace Database\Factories;

use App\Models\CanonEntry;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class CanonEntryFactory extends Factory
{
    protected $model = CanonEntry::class;

    public function definition(): array
    {
        return [
            'user_id' => 1,
            'project_id' => Project::factory(),
            'title' => $this->faker->sentence(3),
            'type' => $this->faker->randomElement(['character', 'location', 'event', 'rule', 'artifact']),
            'content' => $this->faker->paragraph(),
            'image' => null,
            'tags' => json_encode([$this->faker->word]),
        ];
    }
}
