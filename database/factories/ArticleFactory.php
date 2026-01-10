<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\Source;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    protected $model = Article::class;

    public function definition(): array
    {
        return [
            'source_id' => Source::factory(),
            'title' => $this->faker->unique()->sentence(),
            'url' => $this->faker->unique()->url(),
            'html_content' => '<p>'.$this->faker->paragraphs(asText: true).'</p>',
            'published_at' => $this->faker->optional(0.8)->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
