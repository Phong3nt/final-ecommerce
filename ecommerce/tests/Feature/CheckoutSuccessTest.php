<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutSuccessTest extends TestCase
{
    use RefreshDatabase;

    // ─── helpers ──────────────────────────────────────────────────────────────

    private function createOrder(User $user, string $intentId = 'pi_test_cp005', string $status = 'pending'): Order
    {
        $order = Order::create([
            'user_id'                   => $user->id,
            'status'                    => $status,
            'subtotal'                  => 40.00,
            'shipping_cost'             => 5.00,
            'total'                     => 45.00,
            'shipping_method'           => 'standard',
            'shipping_label'            => 'Standard Shipping',
            'address'                   => ['name' => 'Jane', 'city' => 'Austin', 'country' => 'US'],
            'stripe_payment_intent_id'  => $intentId,
            'stripe_client_secret'      => 'pi_test_secret',
        ]);

        $order->items()->create([
            'product_name' => 'Blue Widget',
            'quantity'     => 2,
            'unit_price'   => 20.00,
            'subtotal'     => 40.00,
        ]);

        return $order;
    }

    // ─── TC-01: success page returns 200 for succeeded status ────────────────

    /** @test */
    public function cp005_success_page_returns_200_when_redirect_status_is_succeeded(): void
    {
        $user  = User::factory()->create();
        $this->createOrder($user);

        $this->actingAs($user)
            ->get(route('checkout.success', [
                'payment_intent'  => 'pi_test_cp005',
                'redirect_status' => 'succeeded',
            ]))
            ->assertStatus(200);
    }

    // ─── TC-02: success page shows order ID ───────────────────────────────────

    /** @test */
    public function cp005_success_page_shows_order_id(): void
    {
        $user  = User::factory()->create();
        $order = $this->createOrder($user);

        $this->actingAs($user)
            ->get(route('checkout.success', [
                'payment_intent'  => 'pi_test_cp005',
                'redirect_status' => 'succeeded',
            ]))
            ->assertSee((string) $order->id);
    }

    // ─── TC-03: success page shows order items ────────────────────────────────

    /** @test */
    public function cp005_success_page_shows_order_items(): void
    {
        $user = User::factory()->create();
        $this->createOrder($user);

        $this->actingAs($user)
            ->get(route('checkout.success', [
                'payment_intent'  => 'pi_test_cp005',
                'redirect_status' => 'succeeded',
            ]))
            ->assertSee('Blue Widget');
    }

    // ─── TC-04: success page shows order total ────────────────────────────────

    /** @test */
    public function cp005_success_page_shows_order_total(): void
    {
        $user = User::factory()->create();
        $this->createOrder($user);

        $this->actingAs($user)
            ->get(route('checkout.success', [
                'payment_intent'  => 'pi_test_cp005',
                'redirect_status' => 'succeeded',
            ]))
            ->assertSee('45.00');
    }

    // ─── TC-05: success page clears checkout session ──────────────────────────

    /** @test */
    public function cp005_success_page_clears_checkout_session(): void
    {
        $user = User::factory()->create();
        $this->createOrder($user);

        $this->actingAs($user)
            ->withSession([
                'checkout.address'  => ['city' => 'Austin'],
                'checkout.shipping' => ['method' => 'standard'],
                'cart'              => ['1' => ['name' => 'Widget', 'price' => 10, 'quantity' => 1]],
            ])
            ->get(route('checkout.success', [
                'payment_intent'  => 'pi_test_cp005',
                'redirect_status' => 'succeeded',
            ]))
            ->assertSessionMissing('checkout.address')
            ->assertSessionMissing('checkout.shipping')
            ->assertSessionMissing('cart');
    }

    // ─── TC-06: failed page returns 200 for requires_payment_method ──────────

    /** @test */
    public function cp005_failed_page_returns_200_when_redirect_status_is_requires_payment_method(): void
    {
        $user = User::factory()->create();
        $this->createOrder($user);

        $this->actingAs($user)
            ->get(route('checkout.success', [
                'payment_intent'  => 'pi_test_cp005',
                'redirect_status' => 'requires_payment_method',
            ]))
            ->assertStatus(200);
    }

    // ─── TC-07: failed page shows retry link ──────────────────────────────────

    /** @test */
    public function cp005_failed_page_shows_retry_link(): void
    {
        $user = User::factory()->create();
        $this->createOrder($user);

        $this->actingAs($user)
            ->get(route('checkout.success', [
                'payment_intent'  => 'pi_test_cp005',
                'redirect_status' => 'requires_payment_method',
            ]))
            ->assertSee(route('checkout.review'));
    }

    // ─── TC-08: failed page shows the status/reason ───────────────────────────

    /** @test */
    public function cp005_failed_page_shows_reason(): void
    {
        $user = User::factory()->create();
        $this->createOrder($user);

        $this->actingAs($user)
            ->get(route('checkout.success', [
                'payment_intent'  => 'pi_test_cp005',
                'redirect_status' => 'requires_payment_method',
            ]))
            ->assertSee('requires_payment_method');
    }

    // ─── TC-09: missing payment_intent redirects to address ───────────────────

    /** @test */
    public function cp005_missing_payment_intent_redirects_to_address(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('checkout.success', ['redirect_status' => 'succeeded']))
            ->assertRedirect(route('checkout.address'));
    }

    // ─── TC-10: payment_intent belonging to another user redirects ────────────

    /** @test */
    public function cp005_payment_intent_for_wrong_user_redirects_to_address(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $this->createOrder($owner, 'pi_owned_by_owner');

        $this->actingAs($other)
            ->get(route('checkout.success', [
                'payment_intent'  => 'pi_owned_by_owner',
                'redirect_status' => 'succeeded',
            ]))
            ->assertRedirect(route('checkout.address'));
    }

    // ─── TC-11: guest is redirected to login ──────────────────────────────────

    /** @test */
    public function cp005_guest_is_redirected_to_login(): void
    {
        $this->get(route('checkout.success', [
            'payment_intent'  => 'pi_some_id',
            'redirect_status' => 'succeeded',
        ]))
            ->assertRedirect(route('login'));
    }

    // ─── TC-12: unknown intent ID redirects to address ────────────────────────

    /** @test */
    public function cp005_unknown_order_intent_redirects_to_address(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('checkout.success', [
                'payment_intent'  => 'pi_does_not_exist',
                'redirect_status' => 'succeeded',
            ]))
            ->assertRedirect(route('checkout.address'));
    }
}
