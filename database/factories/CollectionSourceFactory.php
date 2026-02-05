<?php

namespace Database\Factories;

use App\Models\Collection;
use App\Models\CollectionSource;
use App\Models\Source;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CollectionSource>
 */
class CollectionSourceFactory extends Factory
{
    protected $model = CollectionSource::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'collection_id' => Collection::factory(),
            'source_id' => Source::factory(),
        ];
    }
}
