<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * OM-001 — As an admin, I want to view all orders with filters so I can manage fulfilment efficiently.
 *
 * Acceptance criteria:
 *   - Filter by status, date range, customer
 *   - Sortable columns
 *   - Paginated (20/page)
 */
class AdminOrderListTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
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

    // TC-01: Guest is redirected from admin orders index → login
    public function test_pm001_guest_is_redirected_from_admin_orders_index(): void
    {
        $response = $this->get(route('admin.orders.index'));
        $response->assertRedirect(route('login'));
    }

    // TC-02: Non-admin gets 403 on admin orders index
    public function test_om001_non_admin_gets_403_on_orders_index(): void
    {
        $response = $this->actingAs($this->makeUser())
            ->get(route('admin.orders.index'));
        $response->assertStatus(403);
    }

    // TC-03: Admin can view orders list (200) with orders shown
    public function test_om001_admin_can_view_orders_list(): void
    {
        $user = User::factory()->create(['name' => 'Jane Doe']);
        $order = Order::factory()->create(['user_id' => $user->id, 'status' => 'pending']);

        $response = $this->actingAs($this->makeAdmin())
            ->get(route('admin.orders.index'));

        $response->assertStatus(200);
        $response->assertSee('Jane Doe');
        $response->assertSee('#' . $order->id);
    }

    // TC-04: Orders are paginated at 20 per page
    public function test_om001_orders_paginated_at_20_per_page(): void
    {
        Order::factory()->count(25)->create();

        $response = $this->actingAs($this->makeAdmin())
            ->get(route('admin.orders.index'));

        $response->assertStatus(200);
        // Page 1 shows 20 orders — the paginator data confirms this
        $this->assertCount(20, $response->viewData('orders'));
    }

    // TC-05: Filter by status shows only matching orders
    public function test_om001_filter_by_status_shows_only_matching_orders(): void
    {
        $user = User::factory()->create();
        Order::factory()->create(['user_id' => $user->id, 'status' => 'paid', 'total' => 50]);
        Order::factory()->create(['user_id' => $user->id, 'status' => 'pending', 'total' => 80]);

        $response = $this->actingAs($this->makeAdmin())
            ->get(route('admin.orders.index', ['status' => 'paid']));

        $response->assertStatus(200);
        $this->assertCount(1, $response->viewData('orders'));
        $this->assertSame('paid', $response->viewData('orders')->first()->status);
    }

    // TC-06: Filter by date_from excludes orders before that date
    public function test_om001_filter_by_date_from_excludes_older_orders(): void
    {
        $user = User::factory()->create();
        Order::factory()->create(['user_id' => $user->id, 'created_at' => '2026-01-01']);
        Order::factory()->create(['user_id' => $user->id, 'created_at' => '2026-04-01']);

        $response = $this->actingAs($this->makeAdmin())
            ->get(route('admin.orders.index', ['date_from' => '2026-03-01']));

        $response->assertStatus(200);
        $this->assertCount(1, $response->viewData('orders'));
        $this->assertSame('2026-04-01', $response->viewData('orders')->first()->created_at->format('Y-m-d'));
    }

    // TC-07: Filter by date_to excludes orders after that date
    public function test_om001_filter_by_date_to_excludes_newer_orders(): void
    {
        $user = User::factory()->create();
        Order::factory()->create(['user_id' => $user->id, 'created_at' => '2026-01-15']);
        Order::factory()->create(['user_id' => $user->id, 'created_at' => '2026-04-10']);

        $response = $this->actingAs($this->makeAdmin())
            ->get(route('admin.orders.index', ['date_to' => '2026-02-01']));

        $response->assertStatus(200);
        $this->assertCount(1, $response->viewData('orders'));
        $this->assertSame('2026-01-15', $response->viewData('orders')->first()->created_at->format('Y-m-d'));
    }

    // TC-08: Filter by customer name returns matching orders
    public function test_om001_filter_by_customer_name_returns_matching_orders(): void
    {
        $alice = User::factory()->create(['name' => 'Alice Smith']);
        $bob = User::factory()->create(['name' => 'Bob Jones']);
        Order::factory()->create(['user_id' => $alice->id]);
        Order::factory()->create(['user_id' => $bob->id]);

        $response = $this->actingAs($this->makeAdmin())
            ->get(route('admin.orders.index', ['customer' => 'Alice']));

        $response->assertStatus(200);
        $this->assertCount(1, $response->viewData('orders'));
        $this->assertSame($alice->id, $response->viewData('orders')->first()->user_id);
    }

    // TC-09: Combined status + customer filter works correctly
    public function test_om001_combined_status_and_customer_filter(): void
    {
        $alice = User::factory()->create(['name' => 'Alice Smith']);
        $bob = User::factory()->create(['name' => 'Bob Jones']);
        Order::factory()->create(['user_id' => $alice->id, 'status' => 'paid']);
        Order::factory()->create(['user_id' => $alice->id, 'status' => 'pending']);
        Order::factory()->create(['user_id' => $bob->id, 'status' => 'paid']);

        $response = $this->actingAs($this->makeAdmin())
            ->get(route('admin.orders.index', ['status' => 'paid', 'customer' => 'Alice']));

        $response->assertStatus(200);
        $this->assertCount(1, $response->viewData('orders'));
        $this->assertSame($alice->id, $response->viewData('orders')->first()->user_id);
        $this->assertSame('paid', $response->viewData('orders')->first()->status);
    }

    // TC-10: Default sort is newest first
    public function test_om001_default_sort_is_newest_first(): void
    {
        $user = User::factory()->create();
        Order::factory()->create(['user_id' => $user->id, 'created_at' => '2026-01-01']);
        Order::factory()->create(['user_id' => $user->id, 'created_at' => '2026-04-01']);

        $response = $this->actingAs($this->makeAdmin())
            ->get(route('admin.orders.index'));

        $response->assertStatus(200);
        $first = $response->viewData('orders')->first();
        $this->assertSame('2026-04-01', $first->created_at->format('Y-m-d'));
    }

    // TC-11: Sort by total_desc shows highest total first
    public function test_om001_sort_by_total_desc_shows_highest_total_first(): void
    {
        $user = User::factory()->create();
        Order::factory()->create(['user_id' => $user->id, 'total' => 50.00]);
        Order::factory()->create(['user_id' => $user->id, 'total' => 200.00]);

        $response = $this->actingAs($this->makeAdmin())
            ->get(route('admin.orders.index', ['sort' => 'total_desc']));

        $response->assertStatus(200);
        $this->assertSame(200.00, $response->viewData('orders')->first()->total);
    }

    // TC-12: No filters returns all orders
    public function test_om001_no_filters_returns_all_orders(): void
    {
        Order::factory()->count(5)->create();

        $response = $this->actingAs($this->makeAdmin())
            ->get(route('admin.orders.index'));

        $response->assertStatus(200);
        $this->assertCount(5, $response->viewData('orders'));
    }
}
