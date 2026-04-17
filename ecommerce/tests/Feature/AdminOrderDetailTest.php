<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * OM-002 — As an admin, I want to view a single order's details so I can process or investigate it.
 *
 * Acceptance criteria:
 *   - Shows customer, items, totals, shipping address, payment status, status history
 */
class AdminOrderDetailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'user',  'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    }

    private function makeAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        return $admin;
    }

    private function makeUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('user');
        return $user;
    }

    private function makeOrderWithItems(): Order
    {
        $user  = User::factory()->create(['name' => 'Test Customer', 'email' => 'customer@example.com']);
        $order = Order::factory()->create([
            'user_id'       => $user->id,
            'status'        => 'paid',
            'subtotal'      => 100.00,
            'shipping_cost' => 10.00,
            'total'         => 110.00,
            'shipping_label' => 'Standard Shipping',
        ]);
        OrderItem::factory()->create([
            'order_id'     => $order->id,
            'product_name' => 'Fancy Widget',
            'quantity'     => 2,
            'unit_price'   => 50.00,
            'subtotal'     => 100.00,
        ]);
        return $order;
    }

    // TC-01: Guest is redirected from admin order detail → login
    public function test_om002_guest_is_redirected_from_admin_order_detail(): void
    {
        $order = Order::factory()->create();
        $this->get(route('admin.orders.show', $order))
            ->assertRedirect(route('login'));
    }

    // TC-02: Non-admin gets 403 on admin order detail
    public function test_om002_non_admin_gets_403_on_order_detail(): void
    {
        $order = Order::factory()->create();
        $this->actingAs($this->makeUser())
            ->get(route('admin.orders.show', $order))
            ->assertStatus(403);
    }

    // TC-03: Admin gets 200 for existing order
    public function test_om002_admin_gets_200_for_order_detail(): void
    {
        $order = $this->makeOrderWithItems();
        $this->actingAs($this->makeAdmin())
            ->get(route('admin.orders.show', $order))
            ->assertStatus(200);
    }

    // TC-04: Order ID is displayed on detail page
    public function test_om002_order_id_shown_on_detail_page(): void
    {
        $order = $this->makeOrderWithItems();
        $this->actingAs($this->makeAdmin())
            ->get(route('admin.orders.show', $order))
            ->assertSee('Order #' . $order->id);
    }

    // TC-05: Customer name and email are shown
    public function test_om002_customer_name_and_email_shown(): void
    {
        $order = $this->makeOrderWithItems();
        $response = $this->actingAs($this->makeAdmin())
            ->get(route('admin.orders.show', $order));

        $response->assertSee('Test Customer');
        $response->assertSee('customer@example.com');
    }

    // TC-06: Item product name, quantity, unit price, subtotal are shown
    public function test_om002_item_details_shown(): void
    {
        $order = $this->makeOrderWithItems();
        $response = $this->actingAs($this->makeAdmin())
            ->get(route('admin.orders.show', $order));

        $response->assertSee('Fancy Widget');
        $response->assertSee('2');           // quantity
        $response->assertSee('50.00');       // unit price
        $response->assertSee('100.00');      // subtotal
    }

    // TC-07: Order totals (subtotal, shipping, total) are shown
    public function test_om002_order_totals_shown(): void
    {
        $order = $this->makeOrderWithItems();
        $response = $this->actingAs($this->makeAdmin())
            ->get(route('admin.orders.show', $order));

        $response->assertSee('110.00');          // total
        $response->assertSee('Standard Shipping');
    }

    // TC-08: Shipping address is shown
    public function test_om002_shipping_address_shown(): void
    {
        $order = $this->makeOrderWithItems();
        $response = $this->actingAs($this->makeAdmin())
            ->get(route('admin.orders.show', $order));

        $addr = $order->address;
        $response->assertSee($addr['name']);
        $response->assertSee($addr['address_line1']);
        $response->assertSee($addr['city']);
    }

    // TC-09: Payment section shows Stripe and payment intent ID
    public function test_om002_payment_section_shows_stripe_info(): void
    {
        $order = $this->makeOrderWithItems();
        $response = $this->actingAs($this->makeAdmin())
            ->get(route('admin.orders.show', $order));

        $response->assertSee('Stripe');
        $response->assertSee($order->stripe_payment_intent_id);
    }

    // TC-10: Status history section is visible (timeline)
    public function test_om002_status_history_section_visible(): void
    {
        $order = $this->makeOrderWithItems();
        $this->actingAs($this->makeAdmin())
            ->get(route('admin.orders.show', $order))
            ->assertSee('Status History')
            ->assertSee('Placed');
    }

    // TC-11: Processing timestamp shown when order is in processing status
    public function test_om002_processing_timestamp_shown_for_processing_order(): void
    {
        $user  = User::factory()->create();
        $order = Order::factory()->create([
            'user_id'        => $user->id,
            'status'         => 'processing',
            'processing_at'  => '2026-04-10 10:00:00',
        ]);

        $this->actingAs($this->makeAdmin())
            ->get(route('admin.orders.show', $order))
            ->assertSee('10 Apr 2026');
    }

    // TC-12: Status update form is present on detail page
    public function test_om002_status_update_form_present(): void
    {
        $order = $this->makeOrderWithItems();
        $response = $this->actingAs($this->makeAdmin())
            ->get(route('admin.orders.show', $order));

        $response->assertSee('Update Status');
        $response->assertSee(route('admin.orders.status', $order), false);
    }
}
