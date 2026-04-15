<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 10, 200);
        $shipping = fake()->randomElement([5.00, 15.00]);

        return [
            'user_id' => User::factory(),
            'status' => fake()->randomElement(['pending', 'paid', 'failed', 'cancelled']),
            'subtotal' => $subtotal,
            'shipping_cost' => $shipping,
            'total' => $subtotal + $shipping,
            'shipping_method' => 'standard',
            'shipping_label' => 'Standard Shipping',
            'address' => [
                'name' => fake()->name(),
                'address_line1' => fake()->streetAddress(),
                'address_line2' => null,
                'city' => fake()->city(),
                'state' => fake()->stateAbbr(),
                'postal_code' => fake()->postcode(),
                'country' => 'US',
            ],
            'stripe_payment_intent_id' => 'pi_' . fake()->regexify('[A-Za-z0-9]{24}'),
            'stripe_client_secret' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(['status' => 'paid']);
    }

    public function pending(): static
    {
        return $this->state(['status' => 'pending']);
    }

    public function processing(): static
    {
        return $this->state(['status' => 'processing', 'processing_at' => now()]);
    }

    public function shipped(): static
    {
        return $this->state([
            'status' => 'shipped',
            'processing_at' => now()->subDay(),
            'shipped_at' => now(),
        ]);
    }

    public function delivered(): static
    {
        return $this->state([
            'status' => 'delivered',
            'processing_at' => now()->subDays(3),
            'shipped_at' => now()->subDays(2),
            'delivered_at' => now()->subDay(),
        ]);
    }
}
