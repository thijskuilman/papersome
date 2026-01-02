<?php

namespace Database\Factories;

use App\Enums\SourceType;
use App\Models\Source;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Source>
 */
class SourceFactory extends Factory
{
    protected $model = Source::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->company(),
            'url' => $this->faker->unique()->url(),
            'type' => $this->faker->randomElement([SourceType::RSS]),
            'last_fetched_at' => $this->faker->optional()->dateTimeBetween('-7 days', 'now'),
        ];
    }
}
