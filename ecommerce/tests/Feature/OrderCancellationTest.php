<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\PaymentServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * OH-004 — As a user, I want to cancel a pending order so I can change my mind before it ships.
 *
 * Acceptance criteria:
 *   - Cancellation only allowed in "pending" status
 *   - Refund initiated automatically via gateway API (PaymentIntent cancelled)
 *   - Stock restored on cancellation
 */
class OrderCancellationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    }

    private function makeUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('user');
        return $user;
    }

    private function makeOrder(User $user, array $overrides = []): Order
    {
        return Order::factory()->for($user)->create(array_merge([
            'status' => 'pending',
            'subtotal' => 60.00,
            'shipping_cost' => 5.00,
            'total' => 65.00,
            'shipping_method' => 'standard',
            'shipping_label' => 'Standard Shipping',
            'stripe_payment_intent_id' => 'pi_test_cancel_123',
            'address' => [
                'name' => 'Test User',
                'address_line1' => '1 Main St',
                'address_line2' => null,
                'city' => 'Springfield',
                'state' => 'IL',
                'postal_code' => '62701',
                'country' => 'US',
            ],
        ], $overrides));
    }

    private function mockPayment(): void
    {
        $this->mock(PaymentServiceInterface::class, function ($mock) {
            $mock->shouldReceive('cancelPaymentIntent')->andReturn(null);
            $mock->shouldReceive('createPaymentIntent')->andReturn([
                'id' => 'pi_mock',
                'client_secret' => 'pi_mock_secret',
            ]);
            $mock->shouldReceive('constructWebhookEvent')->andReturn((object) []);
        });
    }

    // TC-01: Guest is redirected to login when attempting to cancel
    public function test_oh004_guest_is_redirected_to_login(): void
    {
        $owner = $this->makeUser();
        $order = $this->makeOrder($owner);

        $this->post(route('orders.cancel', $order))
            ->assertRedirect(route('login'));
    }

    // TC-02: Owner can cancel a pending order — status becomes 'cancelled'
    public function test_oh004_owner_can_cancel_pending_order(): void
    {
        $this->mockPayment();

        $owner = $this->makeUser();
        $order = $this->makeOrder($owner, ['status' => 'pending']);

        $this->actingAs($owner)
            ->post(route('orders.cancel', $order))
            ->assertRedirect(route('orders.index'));

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'cancelled',
        ]);
    }

    // TC-03: Non-owner gets 403 when attempting to cancel
    public function test_oh004_non_owner_gets_403(): void
    {
        $this->mockPayment();

        $owner = $this->makeUser();
        $other = $this->makeUser();
        $order = $this->makeOrder($owner, ['status' => 'pending']);

        $this->actingAs($other)
            ->post(route('orders.cancel', $order))
            ->assertForbidden();
    }

    // TC-04: Cannot cancel a paid order — redirects with error, status unchanged
    public function test_oh004_cannot_cancel_paid_order(): void
    {
        $owner = $this->makeUser();
        $order = $this->makeOrder($owner, ['status' => 'paid']);

        $this->actingAs($owner)
            ->post(route('orders.cancel', $order))
            ->assertRedirect(route('orders.show', $order))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'paid']);
    }

    // TC-05: Cannot cancel a processing order — redirects with error, status unchanged
    public function test_oh004_cannot_cancel_processing_order(): void
    {
        $owner = $this->makeUser();
        $order = $this->makeOrder($owner, ['status' => 'processing', 'processing_at' => now()]);

        $this->actingAs($owner)
            ->post(route('orders.cancel', $order))
            ->assertRedirect(route('orders.show', $order))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'processing']);
    }

    // TC-06: Cannot cancel an already-cancelled order — redirects with error
    public function test_oh004_cannot_cancel_already_cancelled_order(): void
    {
        $owner = $this->makeUser();
        $order = $this->makeOrder($owner, ['status' => 'cancelled']);

        $this->actingAs($owner)
            ->post(route('orders.cancel', $order))
            ->assertRedirect(route('orders.show', $order))
            ->assertSessionHas('error');
    }

    // TC-07: Product stock is restored on cancellation
    public function test_oh004_stock_is_restored_on_cancellation(): void
    {
        $this->mockPayment();

        $owner = $this->makeUser();
        $product = Product::factory()->create(['stock' => 10]);
        $order = $this->makeOrder($owner, ['status' => 'pending']);
        OrderItem::factory()->for($order)->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 3,
            'unit_price' => 20.00,
            'subtotal' => 60.00,
        ]);

        $this->actingAs($owner)
            ->post(route('orders.cancel', $order));

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 13,
        ]);
    }

    // TC-08: Stripe PaymentIntent is cancelled via the payment service
    public function test_oh004_stripe_payment_intent_is_cancelled(): void
    {
        $mock = $this->mock(PaymentServiceInterface::class);
        $mock->shouldReceive('cancelPaymentIntent')
            ->once()
            ->with('pi_test_cancel_123');

        $owner = $this->makeUser();
        $order = $this->makeOrder($owner, [
            'status' => 'pending',
            'stripe_payment_intent_id' => 'pi_test_cancel_123',
        ]);

        $this->actingAs($owner)
            ->post(route('orders.cancel', $order));
    }

    // TC-09: PaymentIntent NOT cancelled when stripe_payment_intent_id is null
    public function test_oh004_payment_intent_not_cancelled_when_null(): void
    {
        $mock = $this->mock(PaymentServiceInterface::class);
        $mock->shouldNotReceive('cancelPaymentIntent');

        $owner = $this->makeUser();
        $order = $this->makeOrder($owner, [
            'status' => 'pending',
            'stripe_payment_intent_id' => null,
        ]);

        $this->actingAs($owner)
            ->post(route('orders.cancel', $order))
            ->assertRedirect(route('orders.index'));
    }

    // TC-10: Cancel button is visible on the order detail page for pending orders
    public function test_oh004_cancel_button_visible_for_pending_orders(): void
    {
        $owner = $this->makeUser();
        $order = $this->makeOrder($owner, ['status' => 'pending']);

        $this->actingAs($owner)
            ->get(route('orders.show', $order))
            ->assertStatus(200)
            ->assertSee('Cancel Order');
    }

    // TC-11: Cancel button NOT shown for non-pending orders
    public function test_oh004_cancel_button_not_shown_for_non_pending_orders(): void
    {
        $owner = $this->makeUser();
        $order = $this->makeOrder($owner, ['status' => 'paid']);

        $this->actingAs($owner)
            ->get(route('orders.show', $order))
            ->assertStatus(200)
            ->assertDontSee('Cancel Order');
    }

    // TC-12: Cancel endpoint responds within two seconds
    public function test_oh004_cancel_endpoint_responds_within_two_seconds(): void
    {
        $this->mockPayment();

        $owner = $this->makeUser();
        $order = $this->makeOrder($owner, ['status' => 'pending']);

        $start = microtime(true);
        $this->actingAs($owner)
            ->post(route('orders.cancel', $order))
            ->assertRedirect();
        $elapsed = microtime(true) - $start;

        $this->assertLessThan(2.0, $elapsed, 'Cancel endpoint took longer than 2 seconds.');
    }
}
