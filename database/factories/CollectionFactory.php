<?php

namespace Database\Factories;

use App\Enums\DeliveryChannel;
use App\Models\Collection;
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
            'delivery_channel' => $this->faker->randomElement([
                DeliveryChannel::Booklore,
                DeliveryChannel::Instapaper,
            ]),
            'cron' => $this->faker->optional()->time('H:i:s'),
            'enabled' => $this->faker->boolean(85),
        ];
    }
}
