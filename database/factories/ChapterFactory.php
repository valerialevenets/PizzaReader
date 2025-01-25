<?php

namespace Database\Factories;

use App\Models\Comic;
use Illuminate\Database\Eloquent\Factories\Factory;
/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Chapter>
 */
class ChapterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => rand(1, 1000),
            'title' => fake()->name(),
            'language' => 'en',
            'description' => fake()->text(),
            'slug' => fake()->slug(),
            'comic' => Comic::factory()->make(),
            'chapter' => fake()->numberBetween(1, 100),
            'volume' => fake()->numberBetween(1, 100),
        ];
    }
}
