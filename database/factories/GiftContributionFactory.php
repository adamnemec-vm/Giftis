<?php

namespace Database\Factories;

use App\Models\GiftContribution;
use App\Models\GiftItem;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<GiftContribution>
 */
class GiftContributionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'gift_item_id' => GiftItem::factory(),
            'contributor_name' => fake()->firstName(),
            'amount' => fake()->randomFloat(2, 100, 1000),
            'contribution_token' => Str::uuid()->toString(),
        ];
    }
}
