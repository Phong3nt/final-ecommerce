<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Services\PaymentServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class CheckoutReviewTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function sessionWithCheckout(): array
    {
        return [
            'cart' => [
                42 => ['name' => 'Test Widget', 'price' => 20.00, 'quantity' => 2],
            ],
            'checkout.address' => [
                'id' => 1,
                'name' => 'Jane Doe',
                'address_line1' => '123 Main St',
                'address_line2' => null,
                'city' => 'Springfield',
                'state' => 'IL',
                'postal_code' => '62701',
                'country' => 'US',
            ],
            'checkout.shipping' => [
                'method' => 'standard',
                'label' => 'Standard Shipping',
                'cost' => 5.00,
            ],
        ];
    }

    private function mockPaymentService(string $clientSecret = 'pi_test_secret', string $intentId = 'pi_test_id'): void
    {
        $this->mock(PaymentServiceInterface::class, function ($mock) use ($clientSecret, $intentId) {
            $mock->shouldReceive('createPaymentIntent')
                ->andReturn(['id' => $intentId, 'client_secret' => $clientSecret]);
        });
    }

    // -------------------------------------------------------------------------
    // TC-01: GET returns 200 for authenticated user with full session
    // -------------------------------------------------------------------------

    /** @test */
    public function cp003_review_page_returns_200_for_auth_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession($this->sessionWithCheckout())
            ->get(route('checkout.review'))
            ->assertOk();
    }

    // -------------------------------------------------------------------------
    // TC-02: Guest is redirected to login
    // -------------------------------------------------------------------------

    /** @test */
    public function cp003_guest_is_redirected_to_login(): void
    {
        $this->get(route('checkout.review'))
            ->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // TC-03: GET redirects to address step if checkout.address missing
    // -------------------------------------------------------------------------

    /** @test */
    public function cp003_get_redirects_to_address_if_no_address_in_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['checkout.shipping' => ['method' => 'standard', 'label' => 'Standard', 'cost' => 5.00]])
            ->get(route('checkout.review'))
            ->assertRedirect(route('checkout.address'));
    }

    // -------------------------------------------------------------------------
    // TC-04: GET redirects to shipping if checkout.shipping missing
    // -------------------------------------------------------------------------

    /** @test */
    public function cp003_get_redirects_to_shipping_if_no_shipping_in_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['checkout.address' => ['id' => 1, 'name' => 'Jane']])
            ->get(route('checkout.review'))
            ->assertRedirect(route('checkout.shipping'));
    }

    // -------------------------------------------------------------------------
    // TC-05: Review page shows cart items
    // -------------------------------------------------------------------------

    /** @test */
    public function cp003_review_page_shows_cart_items(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession($this->sessionWithCheckout())
            ->get(route('checkout.review'))
            ->assertOk()
            ->assertSee('Test Widget')
            ->assertSee('20.00');
    }

    // -------------------------------------------------------------------------
    // TC-06: Review page shows shipping method and cost
    // -------------------------------------------------------------------------

    /** @test */
    public function cp003_review_page_shows_shipping_method_and_cost(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession($this->sessionWithCheckout())
            ->get(route('checkout.review'))
            ->assertOk()
            ->assertSee('Standard Shipping')
            ->assertSee('5.00');
    }

    // -------------------------------------------------------------------------
    // TC-07: POST creates Order record in database
    // -------------------------------------------------------------------------

    /** @test */
    public function cp003_place_order_creates_order_in_database(): void
    {
        $this->mockPaymentService();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession($this->sessionWithCheckout())
            ->postJson(route('checkout.place-order'));

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'status' => 'pending',
        ]);
    }

    // -------------------------------------------------------------------------
    // TC-08: POST creates OrderItems in database
    // -------------------------------------------------------------------------

    /** @test */
    public function cp003_place_order_creates_order_items_in_database(): void
    {
        $this->mockPaymentService();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession($this->sessionWithCheckout())
            ->postJson(route('checkout.place-order'));

        $this->assertDatabaseHas('order_items', [
            'product_name' => 'Test Widget',
            'quantity' => 2,
        ]);
    }

    // -------------------------------------------------------------------------
    // TC-09: POST returns JSON with client_secret and order_id
    // -------------------------------------------------------------------------

    /** @test */
    public function cp003_place_order_returns_client_secret_and_order_id(): void
    {
        $this->mockPaymentService('pi_secret_abc');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession($this->sessionWithCheckout())
            ->postJson(route('checkout.place-order'))
            ->assertOk()
            ->assertJsonStructure(['client_secret', 'order_id'])
            ->assertJsonFragment(['client_secret' => 'pi_secret_abc']);
    }

    // -------------------------------------------------------------------------
    // TC-10: Order status is 'pending' after POST
    // -------------------------------------------------------------------------

    /** @test */
    public function cp003_order_status_is_pending_after_place_order(): void
    {
        $this->mockPaymentService();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession($this->sessionWithCheckout())
            ->postJson(route('checkout.place-order'));

        $order = Order::where('user_id', $user->id)->first();
        $this->assertNotNull($order);
        $this->assertEquals('pending', $order->status);
    }

    // -------------------------------------------------------------------------
    // TC-11: Order total = subtotal + shipping_cost
    // -------------------------------------------------------------------------

    /** @test */
    public function cp003_order_total_equals_subtotal_plus_shipping(): void
    {
        $this->mockPaymentService();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession($this->sessionWithCheckout())
            ->postJson(route('checkout.place-order'));

        $order = Order::where('user_id', $user->id)->first();
        // cart: 2 × $20 = $40 subtotal + $5 shipping = $45 total
        $this->assertEquals(40.00, $order->subtotal);
        $this->assertEquals(5.00, $order->shipping_cost);
        $this->assertEquals(45.00, $order->total);
    }

    // -------------------------------------------------------------------------
    // TC-12: Webhook marks order as paid on payment_intent.succeeded
    // -------------------------------------------------------------------------

    /** @test */
    public function cp003_webhook_marks_order_paid_on_payment_intent_succeeded(): void
    {
        // Create a pending order linked to a fake PaymentIntent
        $user = User::factory()->create();
        $order = Order::create([
            'user_id' => $user->id,
            'status' => 'pending',
            'subtotal' => 40.00,
            'shipping_cost' => 5.00,
            'total' => 45.00,
            'shipping_method' => 'standard',
            'shipping_label' => 'Standard Shipping',
            'address' => ['name' => 'Jane'],
            'stripe_payment_intent_id' => 'pi_test_webhook',
            'stripe_client_secret' => 'pi_test_webhook_secret',
        ]);

        // Build a minimal Stripe event payload
        $payload = json_encode([
            'type' => 'payment_intent.succeeded',
            'data' => ['object' => ['id' => 'pi_test_webhook']],
        ]);

        // Mock constructWebhookEvent to bypass signature verification in tests
        $fakeEvent = json_decode($payload);
        $this->mock(PaymentServiceInterface::class, function ($mock) use ($fakeEvent) {
            $mock->shouldReceive('constructWebhookEvent')
                ->andReturn($fakeEvent);
        });

        $this->postJson(route('webhook.stripe'), [], ['Stripe-Signature' => 'fake'])
            ->assertOk();

        $this->assertEquals('paid', $order->fresh()->status);
    }
}
