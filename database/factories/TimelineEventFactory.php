<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\TimelineEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

class TimelineEventFactory extends Factory
{
    protected $model = TimelineEvent::class;

    public function definition(): array
    {
        return [
            'user_id' => 1,
            'project_id' => Project::factory(),
            'session_id' => null,
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->sentence(),
            'type' => $this->faker->randomElement(['event', 'milestone', 'deadline']),
            'event_date' => $this->faker->date(),
            'order_index' => $this->faker->numberBetween(1, 10),
        ];
    }
}
