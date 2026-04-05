<?php

namespace Database\Factories;

use App\Models\AIOutput;
use App\Models\Session;
use Illuminate\Database\Eloquent\Factories\Factory;

class AIOutputFactory extends Factory
{
    protected $model = AIOutput::class;

    public function definition(): array
    {
        return [
            'session_id' => Session::factory(),
            'prompt' => $this->faker->sentence(),
            'result' => $this->faker->paragraph(),
            'type' => $this->faker->randomElement(['text', 'image']),
            'model' => $this->faker->randomElement(['gpt-4', 'gpt-3.5', 'dall-e-3']),
            'metadata' => null,
            'status' => 'completed',
            'error_message' => null,
        ];
    }
}
