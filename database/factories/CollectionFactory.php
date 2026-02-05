<?php

namespace Database\Factories;

use App\Enums\CoverTemplate;
use App\Enums\ScheduledDay;
use App\Enums\ScheduleRepeatType;
use App\Models\Collection;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Collection>
 */
class CollectionFactory extends Factory
{
    protected $model = Collection::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(asText: true),
            'schedule' => $this->faker->boolean(85) ? [] :
                [
                    [
                        "repeat_type" => ScheduleRepeatType::Specific->value,
                        "scheduled_days" => [ScheduledDay::Mon->value, ScheduledDay::Thu->value],
                        "time" => "07:00"
                    ],
                    [
                        'repeat_type' => ScheduleRepeatType::Daily->value,
                        'time' => '12:00',
                    ],
                    [
                        'repeat_type' => ScheduleRepeatType::Daily->value,
                        'time' => '21:00',
                    ],
                ],
            'cover_template' => $this->faker->randomElement(CoverTemplate::cases()),
            'enabled' => $this->faker->boolean(85),
            'user_id' => User::factory()->create(),
        ];
    }
}
