<?php

namespace Database\Factories;

use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\odel>
 */
class AmazonProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::all()->random()->id,
            'user_id' => User::all()->random()->id,
            'sku' => fake()->word(),
            'title' => fake()->word(),
            'asin' => fake()->word(),
            'main_image' => fake()->imageUrl(),
            'is_product_process' => fake()->randomElement(['0', '1', '2'])
        ];
    }
}
