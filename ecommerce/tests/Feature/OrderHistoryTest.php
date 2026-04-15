<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * OH-001 — As a user, I want to view my order history so I can track all past purchases.
 *
 * Acceptance criteria:
 *   - Listed newest-first with order ID, date, total, status
 *   - Paginated (10 per page)
 *   - Guests are redirected to login
 *   - Users only see their own orders
 */
class OrderHistoryTest extends TestCase
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

    // ---------------------------------------------------------------
    // TC-01  Guest is redirected to login
    // ---------------------------------------------------------------

    /** @test */
    public function oh001_guest_is_redirected_to_login(): void
    {
        $this->get(route('orders.index'))
            ->assertRedirect(route('login'));
    }

    // ---------------------------------------------------------------
    // TC-02  Authenticated user gets a 200
    // ---------------------------------------------------------------

    /** @test */
    public function oh001_auth_user_sees_order_history_page(): void
    {
        $this->actingAs($this->makeUser())
            ->get(route('orders.index'))
            ->assertStatus(200);
    }

    // ---------------------------------------------------------------
    // TC-03  Empty state shown when user has no orders
    // ---------------------------------------------------------------

    /** @test */
    public function oh001_empty_state_shown_when_no_orders(): void
    {
        $this->actingAs($this->makeUser())
            ->get(route('orders.index'))
            ->assertSee("haven't placed any orders", false);
    }

    // ---------------------------------------------------------------
    // TC-04  Orders appear in the listing
    // ---------------------------------------------------------------

    /** @test */
    public function oh001_user_orders_appear_in_listing(): void
    {
        $user = $this->makeUser();
        $order = Order::factory()->for($user)->paid()->create();

        $this->actingAs($user)
            ->get(route('orders.index'))
            ->assertStatus(200)
            ->assertSee('#' . $order->id);
    }

    // ---------------------------------------------------------------
    // TC-05  Order status is visible in the listing
    // ---------------------------------------------------------------

    /** @test */
    public function oh001_order_status_visible_in_listing(): void
    {
        $user = $this->makeUser();
        Order::factory()->for($user)->paid()->create();

        $this->actingAs($user)
            ->get(route('orders.index'))
            ->assertSee('Paid');
    }

    // ---------------------------------------------------------------
    // TC-06  Order total is visible in the listing
    // ---------------------------------------------------------------

    /** @test */
    public function oh001_order_total_visible_in_listing(): void
    {
        $user = $this->makeUser();
        $order = Order::factory()->for($user)->create(['total' => 99.99]);

        $this->actingAs($user)
            ->get(route('orders.index'))
            ->assertSee('99.99');
    }

    // ---------------------------------------------------------------
    // TC-07  Orders are listed newest-first
    // ---------------------------------------------------------------

    /** @test */
    public function oh001_orders_listed_newest_first(): void
    {
        $user = $this->makeUser();

        $older = Order::factory()->for($user)->create([
            'created_at' => now()->subDays(5),
            'total'      => 111.11,
        ]);
        $newer = Order::factory()->for($user)->create([
            'created_at' => now()->subDay(),
            'total'      => 222.22,
        ]);

        $response = $this->actingAs($user)->get(route('orders.index'));
        $content  = $response->getContent();

        // Newer order's total (222.22) should appear before older order's total (111.11)
        $posNewer = strpos($content, '222.22');
        $posOlder = strpos($content, '111.11');

        $this->assertNotFalse($posNewer, 'Newer order total not found in response');
        $this->assertNotFalse($posOlder, 'Older order total not found in response');
        $this->assertLessThan($posOlder, $posNewer, 'Newest order should appear first in the listing');
    }

    // ---------------------------------------------------------------
    // TC-08  Users only see their own orders (data isolation)
    // ---------------------------------------------------------------

    /** @test */
    public function oh001_user_cannot_see_another_users_orders(): void
    {
        $userA = $this->makeUser();
        $userB = $this->makeUser();

        // Create userB's order with a distinctive total that won't appear in CSS
        $orderB = Order::factory()->for($userB)->create(['total' => 543.21]);

        $this->actingAs($userA)
            ->get(route('orders.index'))
            ->assertDontSee('543.21');
    }

    // ---------------------------------------------------------------
    // TC-09  Paginated at 10 per page — 11th order not on first page
    // ---------------------------------------------------------------

    /** @test */
    public function oh001_pagination_limits_to_10_orders_per_page(): void
    {
        $user = $this->makeUser();
        Order::factory()->for($user)->count(11)->create();

        $response = $this->actingAs($user)->get(route('orders.index'));

        // There are 11 orders but only 10 should be rendered in the table body
        // We count occurrences of the status badge pattern to verify 10 rows
        $content = $response->getContent();
        $rowCount = substr_count($content, 'class="status-');

        $this->assertEquals(10, $rowCount, 'First page must show exactly 10 orders');
    }

    // ---------------------------------------------------------------
    // TC-10  Pagination links are present when there are more than 10 orders
    // ---------------------------------------------------------------

    /** @test */
    public function oh001_pagination_links_present_when_more_than_10_orders(): void
    {
        $user = $this->makeUser();
        Order::factory()->for($user)->count(11)->create();

        // The Blade `$orders->links()` outputs page navigation HTML
        $this->actingAs($user)
            ->get(route('orders.index'))
            ->assertSee('pagination', false);
    }

    // ---------------------------------------------------------------
    // TC-11  Second page is accessible and shows the 11th order
    // ---------------------------------------------------------------

    /** @test */
    public function oh001_second_page_is_accessible_and_shows_overflow_orders(): void
    {
        $user = $this->makeUser();

        // Create 10 newer + 1 oldest; the oldest will be on page 2
        Order::factory()->for($user)->count(10)->create([
            'created_at' => now()->subDays(1),
        ]);
        $oldest = Order::factory()->for($user)->create([
            'created_at' => now()->subDays(30),
        ]);

        $this->actingAs($user)
            ->get(route('orders.index') . '?page=2')
            ->assertStatus(200)
            ->assertSee('#' . $oldest->id);
    }

    // ---------------------------------------------------------------
    // TC-12  Page responds within an acceptable time
    // ---------------------------------------------------------------

    /** @test */
    public function oh001_order_history_page_responds_within_two_seconds(): void
    {
        $user = $this->makeUser();
        Order::factory()->for($user)->count(10)->create();

        $start = microtime(true);
        $this->actingAs($user)->get(route('orders.index'))->assertStatus(200);
        $elapsed = microtime(true) - $start;

        $this->assertLessThan(2.0, $elapsed, 'Order history page must respond within 2 seconds');
    }
}
