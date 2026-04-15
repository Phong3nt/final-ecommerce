<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * OH-002 — As a user, I want to view the detail of a past order so I can see exactly what was bought.
 *
 * Acceptance criteria:
 *   - Shows items, quantities, prices
 *   - Shows shipping address
 *   - Shows payment method
 *   - Shows status
 *   - Guests redirected to login
 *   - Users cannot view another user's order (403)
 */
class OrderDetailTest extends TestCase
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

    private function makeOrderWithItems(User $user, int $itemCount = 2): Order
    {
        $order = Order::factory()->for($user)->create([
            'status'       => 'paid',
            'subtotal'     => 80.00,
            'shipping_cost'=> 5.00,
            'total'        => 85.00,
            'shipping_method' => 'standard',
            'shipping_label'  => 'Standard Shipping',
            'address' => [
                'name'          => 'Jane Doe',
                'address_line1' => '42 Test Ave',
                'address_line2' => 'Apt 3',
                'city'          => 'Testville',
                'state'         => 'TX',
                'postal_code'   => '75001',
                'country'       => 'US',
            ],
        ]);

        OrderItem::factory()->count($itemCount)->for($order)->create();

        return $order;
    }

    // ---------------------------------------------------------------
    // TC-01  Guest is redirected to login
    // ---------------------------------------------------------------

    /** @test */
    public function oh002_guest_is_redirected_to_login(): void
    {
        $user  = $this->makeUser();
        $order = $this->makeOrderWithItems($user);

        $this->get(route('orders.show', $order))
            ->assertRedirect(route('login'));
    }

    // ---------------------------------------------------------------
    // TC-02  Authenticated owner gets a 200
    // ---------------------------------------------------------------

    /** @test */
    public function oh002_owner_can_view_order_detail(): void
    {
        $user  = $this->makeUser();
        $order = $this->makeOrderWithItems($user);

        $this->actingAs($user)
            ->get(route('orders.show', $order))
            ->assertStatus(200);
    }

    // ---------------------------------------------------------------
    // TC-03  Another authenticated user gets 403 (data isolation)
    // ---------------------------------------------------------------

    /** @test */
    public function oh002_other_user_gets_403(): void
    {
        $owner    = $this->makeUser();
        $attacker = $this->makeUser();
        $order    = $this->makeOrderWithItems($owner);

        $this->actingAs($attacker)
            ->get(route('orders.show', $order))
            ->assertStatus(403);
    }

    // ---------------------------------------------------------------
    // TC-04  Order ID shown in the detail page
    // ---------------------------------------------------------------

    /** @test */
    public function oh002_order_id_shown_on_detail_page(): void
    {
        $user  = $this->makeUser();
        $order = $this->makeOrderWithItems($user);

        $this->actingAs($user)
            ->get(route('orders.show', $order))
            ->assertSee('Order #' . $order->id);
    }

    // ---------------------------------------------------------------
    // TC-05  Items (product names) are shown
    // ---------------------------------------------------------------

    /** @test */
    public function oh002_item_product_names_shown(): void
    {
        $user  = $this->makeUser();
        $order = Order::factory()->for($user)->create();
        $item  = OrderItem::factory()->for($order)->create(['product_name' => 'Blue Widget']);

        $this->actingAs($user)
            ->get(route('orders.show', $order))
            ->assertSee('Blue Widget');
    }

    // ---------------------------------------------------------------
    // TC-06  Item quantity is shown
    // ---------------------------------------------------------------

    /** @test */
    public function oh002_item_quantity_shown(): void
    {
        $user  = $this->makeUser();
        $order = Order::factory()->for($user)->create();
        OrderItem::factory()->for($order)->create(['quantity' => 3, 'product_name' => 'Red Widget']);

        $this->actingAs($user)
            ->get(route('orders.show', $order))
            ->assertSee('3');
    }

    // ---------------------------------------------------------------
    // TC-07  Item unit price is shown
    // ---------------------------------------------------------------

    /** @test */
    public function oh002_item_unit_price_shown(): void
    {
        $user  = $this->makeUser();
        $order = Order::factory()->for($user)->create();
        OrderItem::factory()->for($order)->create(['unit_price' => 29.99]);

        $this->actingAs($user)
            ->get(route('orders.show', $order))
            ->assertSee('29.99');
    }

    // ---------------------------------------------------------------
    // TC-08  Order total is shown
    // ---------------------------------------------------------------

    /** @test */
    public function oh002_order_total_shown(): void
    {
        $user  = $this->makeUser();
        $order = $this->makeOrderWithItems($user);

        $this->actingAs($user)
            ->get(route('orders.show', $order))
            ->assertSee('85.00');
    }

    // ---------------------------------------------------------------
    // TC-09  Shipping address is shown
    // ---------------------------------------------------------------

    /** @test */
    public function oh002_shipping_address_shown(): void
    {
        $user  = $this->makeUser();
        $order = $this->makeOrderWithItems($user);

        $this->actingAs($user)
            ->get(route('orders.show', $order))
            ->assertSee('42 Test Ave')
            ->assertSee('Testville');
    }

    // ---------------------------------------------------------------
    // TC-10  Payment method section is shown
    // ---------------------------------------------------------------

    /** @test */
    public function oh002_payment_method_section_shown(): void
    {
        $user  = $this->makeUser();
        $order = $this->makeOrderWithItems($user);

        $this->actingAs($user)
            ->get(route('orders.show', $order))
            ->assertSee('Payment Method');
    }

    // ---------------------------------------------------------------
    // TC-11  Status is shown on the detail page
    // ---------------------------------------------------------------

    /** @test */
    public function oh002_order_status_shown_on_detail_page(): void
    {
        $user  = $this->makeUser();
        $order = $this->makeOrderWithItems($user);

        $this->actingAs($user)
            ->get(route('orders.show', $order))
            ->assertSee('Paid');
    }

    // ---------------------------------------------------------------
    // TC-12  Detail page responds within two seconds
    // ---------------------------------------------------------------

    /** @test */
    public function oh002_order_detail_page_responds_within_two_seconds(): void
    {
        $user  = $this->makeUser();
        $order = $this->makeOrderWithItems($user, itemCount: 5);

        $start   = microtime(true);
        $this->actingAs($user)->get(route('orders.show', $order))->assertStatus(200);
        $elapsed = microtime(true) - $start;

        $this->assertLessThan(2.0, $elapsed, 'Order detail page must respond within 2 seconds');
    }
}
