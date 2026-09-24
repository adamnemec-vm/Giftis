<?php

namespace Database\Factories;

use App\Models\GiftItem;
use App\Models\Wishlist;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GiftItem>
 */
class GiftItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'wishlist_id' => Wishlist::factory(),
            'title' => fake()->words(3, true),
            'url' => fake()->optional()->url(),
            'price' => fake()->randomFloat(2, 100, 5000),
            'currency' => 'Kč',
            'image_path' => null,
            'description' => fake()->optional()->sentence(),
            'priority' => fake()->randomElement(['low', 'medium', 'high']),
            'status' => 'available',
        ];
    }

    public function reserved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'reserved',
            'reserved_by_name' => fake()->firstName(),
            'reserved_at' => now(),
        ]);
    }
}
