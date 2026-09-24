<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Wishlist>
 */
class WishlistFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'occasion' => fake()->randomElement(['christmas', 'birthday', 'wedding', 'anniversary', 'other']),
            'event_date' => fake()->optional()->dateTimeBetween('now', '+1 year'),
            'share_code' => Str::lower(Str::random(10)),
            'is_public' => true,
        ];
    }

    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => false,
        ]);
    }
}
