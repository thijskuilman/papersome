<?php

namespace Database\Factories;

use App\Enums\DeliveryStatus;
use App\Models\Collection;
use App\Models\Publication;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Publication>
 */
class PublicationFactory extends Factory
{
    protected $model = Publication::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'collection_id' => Collection::factory(),
            'title' => $this->faker->sentence(3),
            'epub_file_path' => $this->faker->optional()->filePath(),
            'cover_image' => $this->faker->optional()->imageUrl(),
            'booklore_delivery_status' => $this->faker->randomElement(DeliveryStatus::cases()),
            'booklore_book_id' => $this->faker->optional()->uuid(),
        ];
    }
}
