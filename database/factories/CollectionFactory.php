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
            'schedule' => null,
            'enabled' => $this->faker->boolean(85),
        ];
    }
}
