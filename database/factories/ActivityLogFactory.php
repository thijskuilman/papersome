<?php

namespace Database\Factories;

use App\Enums\ActivityLogChannel;
use App\Enums\ActivityLogType;
use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityLog>
 */
class ActivityLogFactory extends Factory
{
    protected $model = ActivityLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => $this->faker->randomElement(ActivityLogType::cases()),
            'channel' => $this->faker->optional()->randomElement(ActivityLogChannel::cases()),
            'message' => $this->faker->sentence(),
            'data' => $this->faker->optional()->randomElement([
                ['error' => $this->faker->sentence()],
                [],
            ]),
        ];
    }
}
