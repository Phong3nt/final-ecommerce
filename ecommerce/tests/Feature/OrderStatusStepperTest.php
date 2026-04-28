<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * IMP-011 — Order status visual progress stepper
 *
 * Verifies that the order detail page renders the visual progress stepper
 * correctly: step elements, state classes (done / active / future),
 * timestamps, cancelled/refunded banners, accessibility attributes,
 * and graceful rendering across all order states.
 */
class OrderStatusStepperTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'user',  'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    }

    private function makeUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('user');
        return $user;
    }

    private function makeOrder(User $user, array $attrs = []): Order
    {
        return Order::factory()->for($user)->create(array_merge([
            'status'          => 'paid',
            'subtotal'        => 50.00,
            'shipping_cost'   => 5.00,
            'total'           => 55.00,
            'shipping_method' => 'standard',
            'shipping_label'  => 'Standard Shipping',
            'address' => [
                'name'         => 'Test User',
                'address_line1'=> '1 Test St',
                'address_line2'=> '',
                'city'         => 'Dublin',
                'state'        => 'Dublin',
                'postal_code'  => 'D01 X1Y2',
                'country'      => 'Ireland',
            ],
        ], $attrs));
    }

    // TC-01 (Happy): Stepper container is present on order detail page
    public function test_imp011_stepper_container_present(): void
    {
        $user  = $this->makeUser();
        $order = $this->makeOrder($user);

        $response = $this->actingAs($user)->get(route('orders.show', $order));

        $response->assertStatus(200);
        $response->assertSee('data-imp011="stepper"', false);
    }

    // TC-02 (Happy): Stepper renders all four standard step elements
    public function test_imp011_stepper_has_four_steps(): void
    {
        $user  = $this->makeUser();
        $order = $this->makeOrder($user);

        $response = $this->actingAs($user)->get(route('orders.show', $order));

        $response->assertSee('data-imp011-key="placed"',     false);
        $response->assertSee('data-imp011-key="processing"', false);
        $response->assertSee('data-imp011-key="shipped"',    false);
        $response->assertSee('data-imp011-key="delivered"',  false);
    }

    // TC-03 (Happy): Active step (paid = processing) has the imp011-active class
    public function test_imp011_active_step_has_active_class(): void
    {
        $user  = $this->makeUser();
        $order = $this->makeOrder($user, ['status' => 'paid']);

        $response = $this->actingAs($user)->get(route('orders.show', $order));

        $response->assertSee('imp011-active', false);
    }

    // TC-04 (Happy): Placed step shows the order creation timestamp
    public function test_imp011_placed_step_shows_timestamp(): void
    {
        $user  = $this->makeUser();
        $order = $this->makeOrder($user);

        $response = $this->actingAs($user)->get(route('orders.show', $order));

        $response->assertSee('data-imp011="timestamp"', false);
    }

    // TC-05 (Happy): Fully delivered order has imp011-done on placed, processing, shipped and imp011-active on delivered
    public function test_imp011_delivered_order_shows_all_done_steps(): void
    {
        $user  = $this->makeUser();
        $order = $this->makeOrder($user, [
            'status'        => 'delivered',
            'processing_at' => now()->subDays(3),
            'shipped_at'    => now()->subDays(2),
            'delivered_at'  => now()->subDay(),
        ]);

        $response = $this->actingAs($user)->get(route('orders.show', $order));

        $content = $response->getContent();

        // At least 3 done-class occurrences (placed, processing, shipped) + 1 active (delivered)
        $this->assertGreaterThanOrEqual(3, substr_count($content, 'imp011-done'));
        $this->assertStringContainsString('imp011-active', $content);
    }

    // TC-06 (Edge): Cancelled order shows cancelled-banner and imp011-cancelled class
    public function test_imp011_cancelled_order_shows_cancelled_banner(): void
    {
        $user  = $this->makeUser();
        $order = $this->makeOrder($user, [
            'status'       => 'cancelled',
            'cancelled_at' => now()->subHour(),
        ]);

        $response = $this->actingAs($user)->get(route('orders.show', $order));

        $response->assertSee('data-imp011="cancelled-banner"', false);
        $response->assertSee('imp011-cancelled', false);
        $response->assertSee('This order was cancelled', false);
    }

    // TC-07 (Edge): Refunded order shows refunded-banner
    public function test_imp011_refunded_order_shows_refunded_banner(): void
    {
        $user  = $this->makeUser();
        $order = $this->makeOrder($user, [
            'status'      => 'refunded',
            'refunded_at' => now()->subHour(),
        ]);

        $response = $this->actingAs($user)->get(route('orders.show', $order));

        $response->assertSee('data-imp011="refunded-banner"', false);
        $response->assertSee('This order has been refunded', false);
    }

    // TC-08 (Accessibility): Stepper has role=list and aria-label
    public function test_imp011_stepper_has_aria_attributes(): void
    {
        $user  = $this->makeUser();
        $order = $this->makeOrder($user);

        $response = $this->actingAs($user)->get(route('orders.show', $order));

        $response->assertSee('role="list"',               false);
        $response->assertSee('aria-label="Order progress"', false);
        $response->assertSee('role="listitem"',           false);
    }

    // TC-09 (Happy): Each step element shows its label text
    public function test_imp011_step_labels_are_visible(): void
    {
        $user  = $this->makeUser();
        $order = $this->makeOrder($user);

        $response = $this->actingAs($user)->get(route('orders.show', $order));

        $response->assertSee('Placed',     false);
        $response->assertSee('Processing', false);
        $response->assertSee('Shipped',    false);
        $response->assertSee('Delivered',  false);
    }

    // TC-10 (Happy): Shipped timestamp shown when shipped_at is set
    public function test_imp011_shipped_timestamp_shown_when_available(): void
    {
        $user  = $this->makeUser();
        $now   = now();
        $order = $this->makeOrder($user, [
            'status'        => 'shipped',
            'processing_at' => $now->copy()->subDays(2),
            'shipped_at'    => $now->copy()->subDay(),
        ]);

        $response = $this->actingAs($user)->get(route('orders.show', $order));

        // The shipped date should appear in timestamp elements
        $response->assertSee($order->shipped_at->format('d M Y'), false);
    }

    // TC-11 (Security): Guest cannot access order detail
    public function test_imp011_guest_redirected_from_order_detail(): void
    {
        $user  = $this->makeUser();
        $order = $this->makeOrder($user);

        $response = $this->get(route('orders.show', $order));

        $response->assertRedirect(route('login'));
    }

    // TC-12 (Performance): Order detail with stepper responds within 2 seconds
    public function test_imp011_order_detail_with_stepper_responds_within_two_seconds(): void
    {
        $user  = $this->makeUser();
        $order = $this->makeOrder($user, [
            'processing_at' => now()->subDays(2),
            'shipped_at'    => now()->subDay(),
        ]);

        $start    = microtime(true);
        $response = $this->actingAs($user)->get(route('orders.show', $order));
        $elapsed  = microtime(true) - $start;

        $response->assertStatus(200);
        $this->assertLessThan(2.0, $elapsed, 'Order detail with stepper exceeded 2 seconds.');
    }
}
