<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Services\PaymentServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * IMP-004: Guest Checkout
 * Tests for GET /checkout/guest    (checkout.guest.index)
 *           POST /checkout/guest/session (checkout.guest.session.store)
 *           POST /checkout/guest/order   (checkout.guest.place-order)
 *           GET  /checkout/guest/success (checkout.guest.success)
 */
class GuestCheckoutTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function cartSession(): array
    {
        return [
            'cart' => [
                42 => ['name' => 'Test Widget', 'price' => 20.00, 'quantity' => 2],
            ],
        ];
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'guest_email'   => 'guest@example.com',
            'name'          => 'Jane Guest',
            'address_line1' => '10 Guest Lane',
            'address_line2' => null,
            'city'          => 'Springfield',
            'state'         => 'IL',
            'postal_code'   => '62701',
            'country'       => 'US',
            'method'        => 'standard',
        ], $overrides);
    }

    private function mockPaymentService(): void
    {
        $this->mock(PaymentServiceInterface::class, function ($mock) {
            $mock->shouldReceive('createPaymentIntent')
                ->andReturn(['id' => 'pi_guest_test', 'client_secret' => 'pi_guest_test_secret']);
        });
    }

    // -------------------------------------------------------------------------
    // TC-01: GET /checkout/guest returns 200 for a guest with a cart
    // -------------------------------------------------------------------------

    /** @test */
    public function imp004_guest_checkout_page_returns_200_for_guest(): void
    {
        $this->withSession($this->cartSession())
            ->get(route('checkout.guest.index'))
            ->assertOk();
    }

    // -------------------------------------------------------------------------
    // TC-02: Authenticated user is redirected to the auth checkout
    // -------------------------------------------------------------------------

    /** @test */
    public function imp004_auth_user_redirected_to_auth_checkout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession($this->cartSession())
            ->get(route('checkout.guest.index'))
            ->assertRedirect(route('checkout.index'));
    }

    // -------------------------------------------------------------------------
    // TC-03: Page shows cart item names
    // -------------------------------------------------------------------------

    /** @test */
    public function imp004_guest_checkout_shows_cart_items(): void
    {
        $this->withSession($this->cartSession())
            ->get(route('checkout.guest.index'))
            ->assertSee('Test Widget');
    }

    // -------------------------------------------------------------------------
    // TC-04: Page shows email field
    // -------------------------------------------------------------------------

    /** @test */
    public function imp004_guest_checkout_shows_email_field(): void
    {
        $this->withSession($this->cartSession())
            ->get(route('checkout.guest.index'))
            ->assertSee('guest_email');
    }

    // -------------------------------------------------------------------------
    // TC-05: Page shows address fields
    // -------------------------------------------------------------------------

    /** @test */
    public function imp004_guest_checkout_shows_address_fields(): void
    {
        $this->withSession($this->cartSession())
            ->get(route('checkout.guest.index'))
            ->assertSee('address_line1');
    }

    // -------------------------------------------------------------------------
    // TC-06: Page shows shipping options
    // -------------------------------------------------------------------------

    /** @test */
    public function imp004_guest_checkout_shows_shipping_options(): void
    {
        $this->withSession($this->cartSession())
            ->get(route('checkout.guest.index'))
            ->assertSee('standard')
            ->assertSee('express')
            ->assertSee('Standard Shipping')
            ->assertSee('Express Shipping');
    }

    // -------------------------------------------------------------------------
    // TC-07: Page loads Stripe.js
    // -------------------------------------------------------------------------

    /** @test */
    public function imp004_guest_checkout_has_stripe_js(): void
    {
        $this->withSession($this->cartSession())
            ->get(route('checkout.guest.index'))
            ->assertSee('js.stripe.com');
    }

    // -------------------------------------------------------------------------
    // TC-08: POST /checkout/guest/session returns ok + totals
    // -------------------------------------------------------------------------

    /** @test */
    public function imp004_store_guest_session_returns_ok_and_totals(): void
    {
        $response = $this->withSession($this->cartSession())
            ->postJson(route('checkout.guest.session.store'), $this->validPayload());

        $response->assertOk()
            ->assertJsonStructure(['ok', 'subtotal', 'shipping_cost', 'total']);
    }

    // -------------------------------------------------------------------------
    // TC-09: Session is populated after storeGuestSession
    // -------------------------------------------------------------------------

    /** @test */
    public function imp004_store_guest_session_populates_session(): void
    {
        $this->withSession($this->cartSession())
            ->postJson(route('checkout.guest.session.store'), $this->validPayload());

        $this->assertEquals('guest@example.com', session('checkout.guest_email'));
        $this->assertNotNull(session('checkout.address'));
        $this->assertNotNull(session('checkout.shipping'));
    }

    // -------------------------------------------------------------------------
    // TC-10: Standard shipping cost is $5.00
    // -------------------------------------------------------------------------

    /** @test */
    public function imp004_store_guest_session_standard_shipping_cost(): void
    {
        $response = $this->withSession($this->cartSession())
            ->postJson(route('checkout.guest.session.store'), $this->validPayload(['method' => 'standard']));

        $response->assertOk()->assertJson(['shipping_cost' => 5.0]);
    }

    // -------------------------------------------------------------------------
    // TC-11: Express shipping cost is $15.00
    // -------------------------------------------------------------------------

    /** @test */
    public function imp004_store_guest_session_express_shipping_cost(): void
    {
        $response = $this->withSession($this->cartSession())
            ->postJson(route('checkout.guest.session.store'), $this->validPayload(['method' => 'express']));

        $response->assertOk()->assertJson(['shipping_cost' => 15.0]);
    }

    // -------------------------------------------------------------------------
    // TC-12: Total equals subtotal + shipping
    // -------------------------------------------------------------------------

    /** @test */
    public function imp004_total_equals_subtotal_plus_shipping(): void
    {
        // Cart: 2 × $20 = $40 subtotal; standard = $5 → total $45
        $response = $this->withSession($this->cartSession())
            ->postJson(route('checkout.guest.session.store'), $this->validPayload(['method' => 'standard']));

        $response->assertOk()->assertJson([
            'subtotal'      => 40.0,
            'shipping_cost' => 5.0,
            'total'         => 45.0,
        ]);
    }

    // -------------------------------------------------------------------------
    // TC-13: Missing email returns 422
    // -------------------------------------------------------------------------

    /** @test */
    public function imp004_store_guest_session_missing_email_returns_422(): void
    {
        $this->withSession($this->cartSession())
            ->postJson(route('checkout.guest.session.store'), $this->validPayload(['guest_email' => '']))
            ->assertStatus(422);
    }

    // -------------------------------------------------------------------------
    // TC-14: Invalid email returns 422
    // -------------------------------------------------------------------------

    /** @test */
    public function imp004_store_guest_session_invalid_email_returns_422(): void
    {
        $this->withSession($this->cartSession())
            ->postJson(route('checkout.guest.session.store'), $this->validPayload(['guest_email' => 'not-an-email']))
            ->assertStatus(422);
    }

    // -------------------------------------------------------------------------
    // TC-15: Missing address fields return 422
    // -------------------------------------------------------------------------

    /** @test */
    public function imp004_store_guest_session_missing_address_returns_422(): void
    {
        $this->withSession($this->cartSession())
            ->postJson(route('checkout.guest.session.store'), $this->validPayload(['address_line1' => '']))
            ->assertStatus(422);
    }

    // -------------------------------------------------------------------------
    // TC-16: Invalid shipping method returns 422
    // -------------------------------------------------------------------------

    /** @test */
    public function imp004_store_guest_session_invalid_method_returns_422(): void
    {
        $this->withSession($this->cartSession())
            ->postJson(route('checkout.guest.session.store'), $this->validPayload(['method' => 'overnight']))
            ->assertStatus(422);
    }

    // -------------------------------------------------------------------------
    // TC-17: placeGuestOrder creates order with null user_id and guest_email
    // -------------------------------------------------------------------------

    /** @test */
    public function imp004_place_guest_order_creates_order_with_null_user_id(): void
    {
        $this->mockPaymentService();

        $sessionData = array_merge($this->cartSession(), [
            'checkout.address' => [
                'name'          => 'Jane Guest',
                'address_line1' => '10 Guest Lane',
                'city'          => 'Springfield',
                'state'         => 'IL',
                'postal_code'   => '62701',
                'country'       => 'US',
            ],
            'checkout.shipping' => ['method' => 'standard', 'label' => 'Standard Shipping', 'cost' => 5.00],
            'checkout.guest_email' => 'guest@example.com',
        ]);

        $this->withSession($sessionData)
            ->postJson(route('checkout.guest.place-order'))
            ->assertOk()
            ->assertJsonStructure(['client_secret', 'order_id']);

        $this->assertDatabaseHas('orders', [
            'user_id'     => null,
            'guest_email' => 'guest@example.com',
        ]);
    }

    // -------------------------------------------------------------------------
    // TC-18: placeGuestOrder returns 422 when session is missing
    // -------------------------------------------------------------------------

    /** @test */
    public function imp004_place_guest_order_without_session_returns_422(): void
    {
        $this->postJson(route('checkout.guest.place-order'))
            ->assertStatus(422);
    }
}
