<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ReferenceImage;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReferenceImageFactory extends Factory
{
    protected $model = ReferenceImage::class;

    public function definition(): array
    {
        return [
            'user_id' => 1,
            'project_id' => Project::factory(),
            'title' => $this->faker->sentence(2),
            'description' => $this->faker->sentence(),
            'path' => 'references/' . $this->faker->uuid() . '.jpg',
            'type' => $this->faker->randomElement(['character', 'location', 'prop', 'costume']),
            'size' => $this->faker->numberBetween(100000, 5000000),
            'mime_type' => 'image/jpeg',
            'is_primary' => false,
        ];
    }
}
