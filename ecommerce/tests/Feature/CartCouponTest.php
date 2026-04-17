<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SC-005 — As a user, I want to apply a coupon/discount code so I can save money.
 *
 * Acceptance criteria:
 *   - Code validated against database
 *   - Discount applied as % or fixed
 *   - Error shown for expired/invalid codes
 */
class CartCouponTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function cartSession(float $price = 20.00, int $qty = 2): array
    {
        return [
            'cart' => [
                1 => ['product_id' => 1, 'name' => 'Widget', 'price' => $price,
                      'quantity' => $qty, 'slug' => 'widget'],
            ],
        ];
    }

    private function makePercent(string $code = 'SAVE10', float $pct = 10): Coupon
    {
        return Coupon::create([
            'code'       => $code,
            'type'       => 'percent',
            'value'      => $pct,
            'expires_at' => now()->addDays(30),
            'is_active'  => true,
        ]);
    }

    private function makeFixed(string $code = 'FIVE', float $amount = 5.00): Coupon
    {
        return Coupon::create([
            'code'       => $code,
            'type'       => 'fixed',
            'value'      => $amount,
            'expires_at' => now()->addDays(30),
            'is_active'  => true,
        ]);
    }

    // -------------------------------------------------------------------------
    // TC-01: Valid percent coupon is stored in session and redirects to cart
    // -------------------------------------------------------------------------
    public function test_sc005_valid_percent_coupon_applied_to_session(): void
    {
        $this->makePercent('SAVE10', 10);

        $this->withSession($this->cartSession())
            ->post(route('cart.coupon.apply'), ['code' => 'SAVE10'])
            ->assertRedirect(route('cart.index'));

        $this->assertEquals('SAVE10', session('checkout.coupon.code'));
        $this->assertEquals('percent', session('checkout.coupon.type'));
        $this->assertEquals(10, session('checkout.coupon.value'));
    }

    // -------------------------------------------------------------------------
    // TC-02: Valid fixed coupon is stored in session and redirects to cart
    // -------------------------------------------------------------------------
    public function test_sc005_valid_fixed_coupon_applied_to_session(): void
    {
        $this->makeFixed('FIVE', 5.00);

        $this->withSession($this->cartSession())
            ->post(route('cart.coupon.apply'), ['code' => 'FIVE'])
            ->assertRedirect(route('cart.index'));

        $this->assertEquals('fixed', session('checkout.coupon.type'));
        $this->assertEquals(5.00, session('checkout.coupon.value'));
    }

    // -------------------------------------------------------------------------
    // TC-03: Expired coupon shows error
    // -------------------------------------------------------------------------
    public function test_sc005_expired_coupon_shows_error(): void
    {
        Coupon::create([
            'code'       => 'OLD',
            'type'       => 'percent',
            'value'      => 20,
            'expires_at' => now()->subDay(),
            'is_active'  => true,
        ]);

        $this->withSession($this->cartSession())
            ->post(route('cart.coupon.apply'), ['code' => 'OLD'])
            ->assertRedirect(route('cart.index'))
            ->assertSessionHasErrors('coupon');
    }

    // -------------------------------------------------------------------------
    // TC-04: Inactive coupon shows error
    // -------------------------------------------------------------------------
    public function test_sc005_inactive_coupon_shows_error(): void
    {
        Coupon::create([
            'code'      => 'OFF',
            'type'      => 'fixed',
            'value'     => 10,
            'is_active' => false,
        ]);

        $this->withSession($this->cartSession())
            ->post(route('cart.coupon.apply'), ['code' => 'OFF'])
            ->assertRedirect(route('cart.index'))
            ->assertSessionHasErrors('coupon');
    }

    // -------------------------------------------------------------------------
    // TC-05: Non-existent code shows error
    // -------------------------------------------------------------------------
    public function test_sc005_unknown_code_shows_error(): void
    {
        $this->withSession($this->cartSession())
            ->post(route('cart.coupon.apply'), ['code' => 'NOPE'])
            ->assertRedirect(route('cart.index'))
            ->assertSessionHasErrors('coupon');
    }

    // -------------------------------------------------------------------------
    // TC-06: code field is required
    // -------------------------------------------------------------------------
    public function test_sc005_code_field_is_required(): void
    {
        $this->withSession($this->cartSession())
            ->post(route('cart.coupon.apply'), ['code' => ''])
            ->assertSessionHasErrors('code');
    }

    // -------------------------------------------------------------------------
    // TC-07: Cart page shows discount when coupon applied
    // -------------------------------------------------------------------------
    public function test_sc005_discount_shown_on_cart_page(): void
    {
        $this->makePercent('SAVE10', 10);

        $this->withSession(array_merge(
            $this->cartSession(20.00, 2),  // subtotal = 40.00
            ['checkout.coupon' => ['code' => 'SAVE10', 'type' => 'percent', 'value' => 10]]
        ))
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee('SAVE10')
            ->assertSee('4.00');  // 10% of 40.00 = 4.00
    }

    // -------------------------------------------------------------------------
    // TC-08: Percent discount amount is calculated correctly
    // -------------------------------------------------------------------------
    public function test_sc005_percent_discount_amount_is_correct(): void
    {
        $this->makePercent('TWENTY', 20);

        $this->withSession(array_merge(
            $this->cartSession(50.00, 1),   // subtotal = 50.00
            ['checkout.coupon' => ['code' => 'TWENTY', 'type' => 'percent', 'value' => 20]]
        ))
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee('10.00');  // 20% of 50.00 = 10.00
    }

    // -------------------------------------------------------------------------
    // TC-09: Fixed discount amount is applied correctly
    // -------------------------------------------------------------------------
    public function test_sc005_fixed_discount_amount_is_correct(): void
    {
        $this->makeFixed('FIVE', 5.00);

        $this->withSession(array_merge(
            $this->cartSession(20.00, 2),   // subtotal = 40.00
            ['checkout.coupon' => ['code' => 'FIVE', 'type' => 'fixed', 'value' => 5.00]]
        ))
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee('5.00');  // fixed $5 discount
    }

    // -------------------------------------------------------------------------
    // TC-10: Fixed discount is capped at cart subtotal
    // -------------------------------------------------------------------------
    public function test_sc005_fixed_discount_capped_at_subtotal(): void
    {
        // coupon $100 off on a $30 cart — discount must be capped at $30
        $this->withSession(array_merge(
            $this->cartSession(15.00, 2),   // subtotal = 30.00
            ['checkout.coupon' => ['code' => 'BIGOFF', 'type' => 'fixed', 'value' => 100.00]]
        ))
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee('30.00');  // discount capped at subtotal
    }

    // -------------------------------------------------------------------------
    // TC-11: Removing a coupon clears it from session
    // -------------------------------------------------------------------------
    public function test_sc005_removing_coupon_clears_session(): void
    {
        $this->withSession(array_merge(
            $this->cartSession(),
            ['checkout.coupon' => ['code' => 'SAVE10', 'type' => 'percent', 'value' => 10]]
        ))
            ->delete(route('cart.coupon.remove'))
            ->assertRedirect(route('cart.index'));

        $this->assertNull(session('checkout.coupon'));
    }

    // -------------------------------------------------------------------------
    // TC-12: Checkout review page shows discount line when coupon applied
    // -------------------------------------------------------------------------
    public function test_sc005_checkout_review_shows_discount(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession([
                'cart' => [
                    1 => ['product_id' => 1, 'name' => 'Widget',
                          'price' => 50.00, 'quantity' => 1, 'slug' => 'widget'],
                ],
                'checkout.address' => [
                    'id' => 1, 'name' => 'Jane', 'address_line1' => '1 Main St',
                    'address_line2' => null, 'city' => 'Springfield',
                    'state' => 'IL', 'postal_code' => '62701', 'country' => 'US',
                ],
                'checkout.shipping' => [
                    'method' => 'standard', 'label' => 'Standard Shipping', 'cost' => 5.00,
                ],
                // 10% off $50 = $5 discount; total = 50 + 5 - 5 = 50
                'checkout.coupon' => ['code' => 'SAVE10', 'type' => 'percent', 'value' => 10],
            ])
            ->get(route('checkout.review'))
            ->assertOk()
            ->assertSee('SAVE10')
            ->assertSee('5.00');  // discount amount
    }
}
