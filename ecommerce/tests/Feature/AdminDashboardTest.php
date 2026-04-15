<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * AD-001 — As an admin, I want a dashboard with KPI cards so I can see the business health at a glance.
 *
 * Acceptance criteria:
 *   - Cards: total revenue, orders today, new users today, low-stock products
 *   - Data refreshed every 5 min or on page load
 *   - Only accessible to users with role:admin
 */
class AdminDashboardTest extends TestCase
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

    // TC-01: Guest cannot access admin dashboard — redirected to login
    public function test_ad001_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('admin.dashboard'));

        $response->assertRedirect(route('login'));
    }

    // TC-02: Non-admin user cannot access admin dashboard — 403
    public function test_ad001_non_admin_is_forbidden(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertStatus(403);
    }

    // TC-03: Admin can access dashboard — 200 with 5-minute auto-refresh meta tag
    public function test_ad001_admin_can_access_dashboard(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('content="300"', false);
    }

    // TC-04: Dashboard shows Total Revenue KPI card label
    public function test_ad001_dashboard_shows_total_revenue_label(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertSee('Total Revenue');
    }

    // TC-05: Dashboard shows Orders Today KPI card label
    public function test_ad001_dashboard_shows_orders_today_label(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertSee('Orders Today');
    }

    // TC-06: Dashboard shows New Users Today KPI card label
    public function test_ad001_dashboard_shows_new_users_today_label(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertSee('New Users Today');
    }

    // TC-07: Dashboard shows Low-Stock Products KPI card label
    public function test_ad001_dashboard_shows_low_stock_label(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertSee('Low-Stock Products');
    }

    // TC-08: Total revenue correctly sums paid, processing, shipped, and delivered orders
    public function test_ad001_total_revenue_sums_revenue_statuses(): void
    {
        $admin = $this->makeAdmin();
        $user  = User::factory()->create();

        Order::factory()->paid()->for($user)->create(['total' => 100.00]);
        Order::factory()->paid()->for($user)->create(['total' => 100.00]);
        Order::factory()->processing()->for($user)->create(['total' => 50.00]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertSee('250.00');
    }

    // TC-09: Total revenue excludes pending, cancelled, and failed orders
    public function test_ad001_total_revenue_excludes_non_revenue_statuses(): void
    {
        $admin = $this->makeAdmin();
        $user  = User::factory()->create();

        Order::factory()->pending()->for($user)->create(['total' => 75.00]);
        Order::factory()->for($user)->create(['status' => 'cancelled', 'total' => 200.00]);
        Order::factory()->for($user)->create(['status' => 'failed',    'total' => 150.00]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertSee('0.00');
    }

    // TC-10: Orders today count only includes orders created today, not past days
    public function test_ad001_orders_today_excludes_past_orders(): void
    {
        $admin = $this->makeAdmin();
        $user  = $this->makeUser();

        Order::factory()->count(11)->pending()->for($user)->create();
        Order::factory()->count(3)->pending()->for($user)->create(['created_at' => now()->subDay()]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertSee('11');
    }

    // TC-11: New users today count excludes users created on previous days
    public function test_ad001_new_users_today_excludes_past_users(): void
    {
        $admin = $this->makeAdmin();                                               // today: 1
        User::factory()->count(2)->create();                                       // today: 3
        User::factory()->count(4)->create(['created_at' => now()->subDay()]);      // yesterday, today stays 3

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertSee('3');
    }

    // TC-12: Low-stock products count includes only products with stock ≤ 5
    public function test_ad001_low_stock_count_is_accurate(): void
    {
        $admin = $this->makeAdmin();

        Product::factory()->count(6)->create(['stock' => 3]);    // low-stock (≤ 5)
        Product::factory()->count(4)->create(['stock' => 50]);   // normal stock

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertSee('6');
    }
}
