<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutShippingTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function withAddressSession(): array
    {
        return [
            'checkout.address' => [
                'id'            => 1,
                'name'          => 'Jane Doe',
                'address_line1' => '123 Main St',
                'address_line2' => null,
                'city'          => 'Springfield',
                'state'         => 'IL',
                'postal_code'   => '62701',
                'country'       => 'US',
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // TC-01: GET /checkout/shipping returns 200 for authenticated user
    // -------------------------------------------------------------------------

    /** @test */
    public function cp002_shipping_page_returns_200_for_auth_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
             ->withSession($this->withAddressSession())
             ->get(route('checkout.shipping'))
             ->assertOk();
    }

    // -------------------------------------------------------------------------
    // TC-02: Guest is redirected to login
    // -------------------------------------------------------------------------

    /** @test */
    public function cp002_guest_is_redirected_to_login(): void
    {
        $this->get(route('checkout.shipping'))
             ->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // TC-03: Both shipping options are visible on the page
    // -------------------------------------------------------------------------

    /** @test */
    public function cp002_both_shipping_options_visible(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
             ->withSession($this->withAddressSession())
             ->get(route('checkout.shipping'))
             ->assertOk()
             ->assertSee('standard')
             ->assertSee('express')
             ->assertSee('Standard Shipping')
             ->assertSee('Express Shipping');
    }

    // -------------------------------------------------------------------------
    // TC-04: Selecting standard stores correct data in session
    // -------------------------------------------------------------------------

    /** @test */
    public function cp002_standard_selection_stored_in_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
             ->withSession($this->withAddressSession())
             ->post(route('checkout.shipping.store'), ['method' => 'standard']);

        $this->assertEquals('standard', session('checkout.shipping.method'));
        $this->assertEquals(5.00, session('checkout.shipping.cost'));
    }

    // -------------------------------------------------------------------------
    // TC-05: Selecting express stores correct data in session
    // -------------------------------------------------------------------------

    /** @test */
    public function cp002_express_selection_stored_in_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
             ->withSession($this->withAddressSession())
             ->post(route('checkout.shipping.store'), ['method' => 'express']);

        $this->assertEquals('express', session('checkout.shipping.method'));
        $this->assertEquals(15.00, session('checkout.shipping.cost'));
    }

    // -------------------------------------------------------------------------
    // TC-06: Invalid shipping method fails validation
    // -------------------------------------------------------------------------

    /** @test */
    public function cp002_invalid_method_fails_validation(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
             ->withSession($this->withAddressSession())
             ->post(route('checkout.shipping.store'), ['method' => 'overnight'])
             ->assertSessionHasErrors('method');
    }

    // -------------------------------------------------------------------------
    // TC-07: Missing shipping method fails validation
    // -------------------------------------------------------------------------

    /** @test */
    public function cp002_missing_method_fails_validation(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
             ->withSession($this->withAddressSession())
             ->post(route('checkout.shipping.store'), [])
             ->assertSessionHasErrors('method');
    }

    // -------------------------------------------------------------------------
    // TC-08: Session contains method, label, and cost after POST
    // -------------------------------------------------------------------------

    /** @test */
    public function cp002_session_includes_method_label_and_cost(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
             ->withSession($this->withAddressSession())
             ->post(route('checkout.shipping.store'), ['method' => 'standard']);

        $shipping = session('checkout.shipping');
        $this->assertArrayHasKey('method', $shipping);
        $this->assertArrayHasKey('label',  $shipping);
        $this->assertArrayHasKey('cost',   $shipping);
        $this->assertNotEmpty($shipping['label']);
    }

    // -------------------------------------------------------------------------
    // TC-09: Successful POST redirects to checkout.review
    // -------------------------------------------------------------------------

    /** @test */
    public function cp002_redirects_to_checkout_review_on_success(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
             ->withSession($this->withAddressSession())
             ->post(route('checkout.shipping.store'), ['method' => 'express'])
             ->assertRedirect(route('checkout.review'));
    }

    // -------------------------------------------------------------------------
    // TC-10: GET redirects to address step if no checkout.address in session
    // -------------------------------------------------------------------------

    /** @test */
    public function cp002_get_redirects_to_address_if_no_address_in_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
             ->get(route('checkout.shipping'))
             ->assertRedirect(route('checkout.address'));
    }

    // -------------------------------------------------------------------------
    // TC-11: Standard cost is less than express cost
    // -------------------------------------------------------------------------

    /** @test */
    public function cp002_standard_cost_is_less_than_express_cost(): void
    {
        $user = User::factory()->create();

        $session = $this->withAddressSession();

        // Post standard and capture cost
        $this->actingAs($user)->withSession($session)
             ->post(route('checkout.shipping.store'), ['method' => 'standard']);
        $standardCost = session('checkout.shipping.cost');

        // Post express and capture cost
        $this->actingAs($user)->withSession($session)
             ->post(route('checkout.shipping.store'), ['method' => 'express']);
        $expressCost = session('checkout.shipping.cost');

        $this->assertLessThan($expressCost, $standardCost);
    }

    // -------------------------------------------------------------------------
    // TC-12: Shipping step GET responds within one second
    // -------------------------------------------------------------------------

    /** @test */
    public function cp002_shipping_step_responds_within_one_second(): void
    {
        $user  = User::factory()->create();
        $start = microtime(true);

        $this->actingAs($user)
             ->withSession($this->withAddressSession())
             ->get(route('checkout.shipping'))
             ->assertOk();

        $this->assertLessThan(1.0, microtime(true) - $start);
    }
}
