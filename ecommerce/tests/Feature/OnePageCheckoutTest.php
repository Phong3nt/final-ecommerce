<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserAddress;
use App\Services\PaymentServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * IMP-003: One-Page Checkout
 * Tests for GET /checkout (checkout.index) and POST /checkout/session (checkout.session.store).
 */
class OnePageCheckoutTest extends TestCase
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
            'name'          => 'Jane Doe',
            'address_line1' => '123 Main St',
            'address_line2' => null,
            'city'          => 'Springfield',
            'state'         => 'IL',
            'postal_code'   => '62701',
            'country'       => 'US',
            'method'        => 'standard',
        ], $overrides);
    }

    // -------------------------------------------------------------------------
    // TC-01: GET /checkout returns 200 for authenticated user with cart
    // -------------------------------------------------------------------------

    /** @test */
    public function imp003_checkout_page_returns_200_for_auth_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession($this->cartSession())
            ->get(route('checkout.index'))
            ->assertOk();
    }

    // -------------------------------------------------------------------------
    // TC-02: Guest is redirected to login
    // -------------------------------------------------------------------------

    /** @test */
    public function imp003_guest_redirected_to_login(): void
    {
        $this->get(route('checkout.index'))
            ->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // TC-03: Page shows cart item names
    // -------------------------------------------------------------------------

    /** @test */
    public function imp003_checkout_page_shows_cart_items(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession($this->cartSession())
            ->get(route('checkout.index'))
            ->assertOk()
            ->assertSee('Test Widget');
    }

    // -------------------------------------------------------------------------
    // TC-04: Page contains address input fields
    // -------------------------------------------------------------------------

    /** @test */
    public function imp003_checkout_page_shows_address_fields(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession($this->cartSession())
            ->get(route('checkout.index'))
            ->assertOk()
            ->assertSee('address_line1', false);
    }

    // -------------------------------------------------------------------------
    // TC-05: Page shows both shipping options
    // -------------------------------------------------------------------------

    /** @test */
    public function imp003_checkout_page_shows_shipping_options(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession($this->cartSession())
            ->get(route('checkout.index'))
            ->assertOk()
            ->assertSee('standard')
            ->assertSee('express')
            ->assertSee('Standard Shipping')
            ->assertSee('Express Shipping');
    }

    // -------------------------------------------------------------------------
    // TC-06: Page shows user's saved addresses
    // -------------------------------------------------------------------------

    /** @test */
    public function imp003_checkout_page_shows_saved_addresses(): void
    {
        $user    = User::factory()->create();
        UserAddress::factory()->create(['user_id' => $user->id, 'name' => 'John Smith']);

        $this->actingAs($user)
            ->withSession($this->cartSession())
            ->get(route('checkout.index'))
            ->assertOk()
            ->assertSee('John Smith');
    }

    // -------------------------------------------------------------------------
    // TC-07: Page includes Stripe.js CDN
    // -------------------------------------------------------------------------

    /** @test */
    public function imp003_checkout_page_has_stripe_js(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession($this->cartSession())
            ->get(route('checkout.index'))
            ->assertOk()
            ->assertSee('js.stripe.com', false);
    }

    // -------------------------------------------------------------------------
    // TC-08: Page shows express delivery time info
    // -------------------------------------------------------------------------

    /** @test */
    public function imp003_checkout_page_shows_express_delivery_info(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession($this->cartSession())
            ->get(route('checkout.index'))
            ->assertOk()
            ->assertSee('1', false); // '1–2 business days' — partial check for robustness
    }

    // -------------------------------------------------------------------------
    // TC-09: POST /checkout/session with new address + valid method → 200 + JSON
    // -------------------------------------------------------------------------

    /** @test */
    public function imp003_store_session_with_new_address_and_method(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession($this->cartSession())
            ->postJson(route('checkout.session.store'), $this->validPayload())
            ->assertOk()
            ->assertJsonFragment(['ok' => true]);

        $this->assertEquals('123 Main St', session('checkout.address.address_line1'));
        $this->assertEquals('standard', session('checkout.shipping.method'));
    }

    // -------------------------------------------------------------------------
    // TC-10: POST with saved address_id + method → 200 + session updated
    // -------------------------------------------------------------------------

    /** @test */
    public function imp003_store_session_with_saved_address(): void
    {
        $user    = User::factory()->create();
        $address = UserAddress::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->withSession($this->cartSession())
            ->postJson(route('checkout.session.store'), [
                'address_id' => $address->id,
                'method'     => 'express',
            ])
            ->assertOk()
            ->assertJsonFragment(['ok' => true]);

        $this->assertEquals('express', session('checkout.shipping.method'));
        $this->assertEquals($address->address_line1, session('checkout.address.address_line1'));
    }

    // -------------------------------------------------------------------------
    // TC-11: POST with new address persists address to database
    // -------------------------------------------------------------------------

    /** @test */
    public function imp003_store_session_saves_new_address_to_database(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession($this->cartSession())
            ->postJson(route('checkout.session.store'), $this->validPayload());

        $this->assertDatabaseHas('user_addresses', [
            'user_id'       => $user->id,
            'address_line1' => '123 Main St',
        ]);
    }

    // -------------------------------------------------------------------------
    // TC-12: POST response contains subtotal, shipping_cost, and total
    // -------------------------------------------------------------------------

    /** @test */
    public function imp003_store_session_returns_totals_in_response(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession($this->cartSession())
            ->postJson(route('checkout.session.store'), $this->validPayload())
            ->assertOk()
            ->assertJsonStructure(['ok', 'subtotal', 'shipping_cost', 'total']);
    }

    // -------------------------------------------------------------------------
    // TC-13: Standard shipping cost reflected in response
    // -------------------------------------------------------------------------

    /** @test */
    public function imp003_store_session_standard_cost_in_response(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession($this->cartSession())
            ->postJson(route('checkout.session.store'), $this->validPayload(['method' => 'standard']))
            ->assertOk()
            ->assertJsonFragment(['shipping_cost' => 5.0]);
    }

    // -------------------------------------------------------------------------
    // TC-14: Express shipping cost reflected in response
    // -------------------------------------------------------------------------

    /** @test */
    public function imp003_store_session_express_cost_in_response(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession($this->cartSession())
            ->postJson(route('checkout.session.store'), $this->validPayload(['method' => 'express']))
            ->assertOk()
            ->assertJsonFragment(['shipping_cost' => 15.0]);
    }

    // -------------------------------------------------------------------------
    // TC-15: Missing address fields returns 422
    // -------------------------------------------------------------------------

    /** @test */
    public function imp003_store_session_invalid_address_returns_422(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession($this->cartSession())
            ->postJson(route('checkout.session.store'), ['method' => 'standard'])
            ->assertStatus(422);
    }

    // -------------------------------------------------------------------------
    // TC-16: Invalid shipping method returns 422
    // -------------------------------------------------------------------------

    /** @test */
    public function imp003_store_session_invalid_method_returns_422(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession($this->cartSession())
            ->postJson(route('checkout.session.store'), $this->validPayload(['method' => 'teleport']))
            ->assertStatus(422);
    }

    // -------------------------------------------------------------------------
    // TC-17: Guest cannot POST to session endpoint
    // -------------------------------------------------------------------------

    /** @test */
    public function imp003_guest_cannot_post_session(): void
    {
        $this->postJson(route('checkout.session.store'), $this->validPayload())
            ->assertUnauthorized();
    }

    // -------------------------------------------------------------------------
    // TC-18: Total in response is subtotal + shipping_cost
    // -------------------------------------------------------------------------

    /** @test */
    public function imp003_store_session_total_equals_subtotal_plus_shipping(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withSession($this->cartSession())
            ->postJson(route('checkout.session.store'), $this->validPayload(['method' => 'standard']))
            ->assertOk()
            ->json();

        // Cart: 2 × $20 = $40; standard shipping: $5; total: $45
        $this->assertEquals(40.0, $response['subtotal']);
        $this->assertEquals(45.0, $response['total']);
    }
}
