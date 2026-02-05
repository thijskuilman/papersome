<?php

namespace Database\Factories;

use App\Enums\SourceType;
use App\Models\Source;
use App\Models\User;
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
            'icon' => $this->faker->optional()->imageUrl(),
            'prefix_parse_url' => $this->faker->optional()->url(),
            'html_query_filters' => [],
            'url' => $this->faker->unique()->url(),
            'type' => $this->faker->randomElement([SourceType::Rss]),
            'last_fetched_at' => $this->faker->optional()->dateTimeBetween('-7 days', 'now'),
            'user_id' => User::factory()->create(),
        ];
    }
}
