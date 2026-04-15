<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * AD-002 — As an admin, I want a revenue chart (daily/weekly/monthly) so I can identify trends.
 *
 * Acceptance criteria:
 *   - Line/bar chart using Chart.js
 *   - Toggle between daily, weekly, monthly ranges
 *   - Shows gross revenue and order count per period
 *   - Endpoint: GET /admin/chart-data?range={daily|weekly|monthly}
 */
class AdminRevenueChartTest extends TestCase
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

    // TC-01: Guest is redirected to login when accessing chart data endpoint
    public function test_ad002_guest_is_redirected_from_chart_endpoint(): void
    {
        $response = $this->get(route('admin.chart-data', ['range' => 'daily']));

        $response->assertRedirect(route('login'));
    }

    // TC-02: Non-admin user receives 403 from chart data endpoint
    public function test_ad002_non_admin_is_forbidden_from_chart_endpoint(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user)->get(route('admin.chart-data', ['range' => 'daily']));

        $response->assertStatus(403);
    }

    // TC-03: Admin gets a 200 JSON response with the correct structure
    public function test_ad002_admin_gets_json_with_correct_structure(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->getJson(route('admin.chart-data', ['range' => 'daily']));

        $response->assertStatus(200)
            ->assertJsonStructure(['labels', 'revenue', 'orders']);
    }

    // TC-04: Daily range returns exactly 7 data points
    public function test_ad002_daily_range_returns_7_data_points(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->getJson(route('admin.chart-data', ['range' => 'daily']));

        $response->assertStatus(200);
        $this->assertCount(7, $response->json('labels'));
        $this->assertCount(7, $response->json('revenue'));
        $this->assertCount(7, $response->json('orders'));
    }

    // TC-05: Weekly range returns exactly 8 data points
    public function test_ad002_weekly_range_returns_8_data_points(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->getJson(route('admin.chart-data', ['range' => 'weekly']));

        $response->assertStatus(200);
        $this->assertCount(8, $response->json('labels'));
        $this->assertCount(8, $response->json('revenue'));
        $this->assertCount(8, $response->json('orders'));
    }

    // TC-06: Monthly range returns exactly 12 data points
    public function test_ad002_monthly_range_returns_12_data_points(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->getJson(route('admin.chart-data', ['range' => 'monthly']));

        $response->assertStatus(200);
        $this->assertCount(12, $response->json('labels'));
        $this->assertCount(12, $response->json('revenue'));
        $this->assertCount(12, $response->json('orders'));
    }

    // TC-07: Missing range parameter returns 422 validation error
    public function test_ad002_missing_range_returns_422(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->getJson(route('admin.chart-data'));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['range']);
    }

    // TC-08: Invalid range value returns 422 validation error
    public function test_ad002_invalid_range_value_returns_422(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->getJson(route('admin.chart-data', ['range' => 'yearly']));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['range']);
    }

    // TC-09: Revenue data only sums paid, processing, shipped, and delivered orders
    public function test_ad002_revenue_sums_only_revenue_status_orders(): void
    {
        $admin = $this->makeAdmin();
        $user = User::factory()->create();

        Order::factory()->paid()->for($user)->create(['total' => 120.00]);
        Order::factory()->processing()->for($user)->create(['total' => 80.00]);

        $response = $this->actingAs($admin)->getJson(route('admin.chart-data', ['range' => 'daily']));

        $revenue = $response->json('revenue');
        $this->assertEquals(200.0, array_sum($revenue));
    }

    // TC-10: Revenue excludes pending, cancelled, and failed orders
    public function test_ad002_revenue_excludes_non_revenue_status_orders(): void
    {
        $admin = $this->makeAdmin();
        $user = User::factory()->create();

        Order::factory()->pending()->for($user)->create(['total' => 500.00]);
        Order::factory()->for($user)->create(['status' => 'cancelled', 'total' => 300.00]);
        Order::factory()->for($user)->create(['status' => 'failed', 'total' => 200.00]);

        $response = $this->actingAs($admin)->getJson(route('admin.chart-data', ['range' => 'daily']));

        $revenue = $response->json('revenue');
        $this->assertEquals(0.0, array_sum($revenue));
    }

    // TC-11: Order count includes all orders regardless of status
    public function test_ad002_order_count_includes_all_statuses(): void
    {
        $admin = $this->makeAdmin();
        $user = User::factory()->create();

        Order::factory()->paid()->for($user)->create();
        Order::factory()->pending()->for($user)->create();
        Order::factory()->for($user)->create(['status' => 'cancelled']);

        $response = $this->actingAs($admin)->getJson(route('admin.chart-data', ['range' => 'daily']));

        $orders = $response->json('orders');
        $this->assertEquals(3, array_sum($orders));
    }

    // TC-12: Dashboard view contains canvas element and Chart.js CDN reference
    public function test_ad002_dashboard_contains_chart_canvas_and_chartjs(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('id="revenue-chart"', false);
        $response->assertSee('cdn.jsdelivr.net/npm/chart.js', false);
        $response->assertSee('data-range="daily"', false);
        $response->assertSee('data-range="weekly"', false);
        $response->assertSee('data-range="monthly"', false);
    }
}
