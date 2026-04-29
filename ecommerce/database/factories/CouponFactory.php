<?php

namespace Database\Factories;

use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Coupon>
 */
class CouponFactory extends Factory
{
    protected $model = Coupon::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('??##??')),
            'name' => fake()->sentence(3),
            'description' => fake()->sentence(12),
            'coupon_template_id' => null,
            'user_id' => null,
            'type' => fake()->randomElement(['percent', 'fixed']),
            'value' => fake()->randomFloat(2, 1, 50),
            'expires_at' => null,
            'usage_limit' => null,
            'min_order_amount' => 1,
            'is_active' => true,
            'times_used' => 0,
            'assigned_at' => null,
        ];
    }

    public function percent(): static
    {
        return $this->state(['type' => 'percent', 'value' => 20.00]);
    }

    public function fixed(): static
    {
        return $this->state(['type' => 'fixed', 'value' => 10.00]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function expired(): static
    {
        return $this->state(['expires_at' => now()->subDay()]);
    }
}
