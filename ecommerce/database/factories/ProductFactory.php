<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);
        return [
            'name'        => $name,
            'slug'        => Str::slug($name),
            'sku'         => strtoupper(fake()->bothify('???-####')),
            'description' => fake()->sentence(),
            'price'       => fake()->randomFloat(2, 1, 500),
            'stock'       => fake()->numberBetween(0, 100),
            'image'       => null,
            'category_id' => null,
            'rating'      => fake()->randomFloat(2, 1, 5),
        ];
    }

    public function outOfStock(): static
    {
        return $this->state(['stock' => 0]);
    }
}
