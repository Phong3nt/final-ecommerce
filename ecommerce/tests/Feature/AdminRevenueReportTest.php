<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\RefundTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * RM-001 — As an admin, I want to see total revenue broken down by period
 *           so I can measure business performance.
 *
 * Acceptance criteria:
 *   - Daily, weekly, monthly, and custom date range views
 *   - Shows gross revenue (revenue-status orders), refunds (RefundTransaction amounts),
 *     and net revenue (gross − refunds)
 *   - Invalid period falls back to default (monthly)
 */
class AdminRevenueReportTest extends TestCase
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
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('admin');
        return $admin;
    }

    private function makeUser(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('user');
        return $user;
    }

    // TC-01: Guest is redirected to login when accessing revenue report
    public function test_rm001_tc01_guest_redirected_to_login(): void
    {
        $this->get(route('admin.revenue.index'))
            ->assertRedirect(route('login'));
    }

    // TC-02: Non-admin (regular user) receives 403
    public function test_rm001_tc02_non_admin_gets_403(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->get(route('admin.revenue.index'))
            ->assertStatus(403);
    }

    // TC-03: Admin gets 200 response
    public function test_rm001_tc03_admin_gets_200(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->get(route('admin.revenue.index'))
            ->assertStatus(200);
    }

    // TC-04: Default period is monthly (12 rows) when no period param given
    public function test_rm001_tc04_default_period_is_monthly_12_rows(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)
            ->get(route('admin.revenue.index'));

        $response->assertStatus(200);
        $rows = $response->viewData('rows');
        $this->assertCount(12, $rows);
    }

    // TC-05: Daily period returns exactly 7 rows
    public function test_rm001_tc05_daily_period_returns_7_rows(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)
            ->get(route('admin.revenue.index', ['period' => 'daily']));

        $response->assertStatus(200);
        $this->assertCount(7, $response->viewData('rows'));
    }

    // TC-06: Weekly period returns exactly 8 rows
    public function test_rm001_tc06_weekly_period_returns_8_rows(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)
            ->get(route('admin.revenue.index', ['period' => 'weekly']));

        $response->assertStatus(200);
        $this->assertCount(8, $response->viewData('rows'));
    }

    // TC-07: Monthly period returns exactly 12 rows
    public function test_rm001_tc07_monthly_period_returns_12_rows(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)
            ->get(route('admin.revenue.index', ['period' => 'monthly']));

        $response->assertStatus(200);
        $this->assertCount(12, $response->viewData('rows'));
    }

    // TC-08: Custom range: number of rows matches days between date_from and date_to (inclusive)
    public function test_rm001_tc08_custom_range_row_count_matches_days(): void
    {
        $admin = $this->makeAdmin();

        $from = '2024-01-01';
        $to   = '2024-01-07'; // 7 days inclusive = 7 rows (indices 0–6)

        $response = $this->actingAs($admin)->get(route('admin.revenue.index', [
            'period'    => 'custom',
            'date_from' => $from,
            'date_to'   => $to,
        ]));

        $response->assertStatus(200);
        $this->assertCount(7, $response->viewData('rows'));
    }

    // TC-09: Custom range excludes orders outside the date range
    public function test_rm001_tc09_custom_range_excludes_orders_outside_range(): void
    {
        $admin = $this->makeAdmin();
        $user  = $this->makeUser();

        // Order inside range
        $inside = Order::factory()->create([
            'user_id'    => $user->id,
            'status'     => 'paid',
            'total'      => 100.00,
            'created_at' => Carbon::parse('2024-03-15 12:00:00'),
        ]);

        // Order outside range (before)
        Order::factory()->create([
            'user_id'    => $user->id,
            'status'     => 'paid',
            'total'      => 999.00,
            'created_at' => Carbon::parse('2024-03-01 12:00:00'),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.revenue.index', [
            'period'    => 'custom',
            'date_from' => '2024-03-10',
            'date_to'   => '2024-03-16',
        ]));

        $response->assertStatus(200);
        $totals = $response->viewData('totals');
        $this->assertEquals(100.00, $totals['gross']);
    }

    // TC-10: Gross revenue sums totals of revenue-status orders only
    //        Revenue statuses: paid, processing, shipped, delivered
    public function test_rm001_tc10_gross_revenue_counts_revenue_status_orders(): void
    {
        $admin = $this->makeAdmin();
        $user  = $this->makeUser();

        $statuses = ['paid', 'processing', 'shipped', 'delivered'];
        foreach ($statuses as $status) {
            Order::factory()->create([
                'user_id'    => $user->id,
                'status'     => $status,
                'total'      => 50.00,
                'created_at' => now(),
            ]);
        }

        $response = $this->actingAs($admin)
            ->get(route('admin.revenue.index', ['period' => 'daily']));

        $response->assertStatus(200);
        $totals = $response->viewData('totals');
        $this->assertEquals(200.00, $totals['gross']); // 4 × $50
    }

    // TC-11: Gross revenue excludes non-revenue statuses (pending, cancelled, failed, refunded)
    public function test_rm001_tc11_gross_excludes_non_revenue_statuses(): void
    {
        $admin = $this->makeAdmin();
        $user  = $this->makeUser();

        foreach (['pending', 'cancelled', 'failed', 'refunded'] as $status) {
            Order::factory()->create([
                'user_id'    => $user->id,
                'status'     => $status,
                'total'      => 500.00,
                'created_at' => now(),
            ]);
        }

        $response = $this->actingAs($admin)
            ->get(route('admin.revenue.index', ['period' => 'daily']));

        $response->assertStatus(200);
        $totals = $response->viewData('totals');
        $this->assertEquals(0.00, $totals['gross']);
    }

    // TC-12: Refunds are summed from RefundTransaction records for orders in the period
    public function test_rm001_tc12_refunds_summed_from_refund_transactions(): void
    {
        $admin = $this->makeAdmin();
        $user  = $this->makeUser();

        $order = Order::factory()->create([
            'user_id'    => $user->id,
            'status'     => 'refunded',
            'total'      => 80.00,
            'created_at' => now(),
        ]);

        RefundTransaction::create([
            'order_id'         => $order->id,
            'amount'           => 80.00,
            'stripe_refund_id' => 're_test_001',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.revenue.index', ['period' => 'daily']));

        $response->assertStatus(200);
        $totals = $response->viewData('totals');
        $this->assertEquals(80.00, $totals['refunds']);
    }

    // TC-13: Net revenue equals gross minus refunds
    public function test_rm001_tc13_net_revenue_is_gross_minus_refunds(): void
    {
        $admin = $this->makeAdmin();
        $user  = $this->makeUser();

        // Revenue-generating order: $150
        Order::factory()->create([
            'user_id'    => $user->id,
            'status'     => 'paid',
            'total'      => 150.00,
            'created_at' => now(),
        ]);

        // Refunded order: $40 refund
        $refundOrder = Order::factory()->create([
            'user_id'    => $user->id,
            'status'     => 'refunded',
            'total'      => 40.00,
            'created_at' => now(),
        ]);
        RefundTransaction::create([
            'order_id'         => $refundOrder->id,
            'amount'           => 40.00,
            'stripe_refund_id' => 're_test_002',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.revenue.index', ['period' => 'daily']));

        $response->assertStatus(200);
        $totals = $response->viewData('totals');
        $this->assertEquals(150.00, $totals['gross']);
        $this->assertEquals(40.00,  $totals['refunds']);
        $this->assertEquals(110.00, $totals['net']);  // 150 - 40
    }

    // TC-14: Zero revenue is shown when no orders exist
    public function test_rm001_tc14_zero_revenue_when_no_orders(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)
            ->get(route('admin.revenue.index', ['period' => 'daily']));

        $response->assertStatus(200);
        $totals = $response->viewData('totals');
        $this->assertEquals(0.00, $totals['gross']);
        $this->assertEquals(0.00, $totals['refunds']);
        $this->assertEquals(0.00, $totals['net']);
    }

    // TC-15: Invalid period value falls back to monthly (12 rows)
    public function test_rm001_tc15_invalid_period_falls_back_to_monthly(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)
            ->get(route('admin.revenue.index', ['period' => 'biannual']));

        $response->assertStatus(200);
        $this->assertCount(12, $response->viewData('rows'));
        $this->assertEquals('monthly', $response->viewData('period'));
    }

    // TC-16: Revenue report page renders the breakdown table
    public function test_rm001_tc16_revenue_page_renders_table(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)
            ->get(route('admin.revenue.index', ['period' => 'monthly']));

        $response->assertStatus(200)
            ->assertSee('revenue-table', false);
    }

    // TC-17: Summary totals match sum of period rows
    public function test_rm001_tc17_summary_totals_match_row_sums(): void
    {
        $admin = $this->makeAdmin();
        $user  = $this->makeUser();

        Order::factory()->create([
            'user_id'    => $user->id,
            'status'     => 'paid',
            'total'      => 75.00,
            'created_at' => now()->subDays(2),
        ]);
        Order::factory()->create([
            'user_id'    => $user->id,
            'status'     => 'delivered',
            'total'      => 50.00,
            'created_at' => now()->subDays(1),
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.revenue.index', ['period' => 'daily']));

        $response->assertStatus(200);
        $rows   = $response->viewData('rows');
        $totals = $response->viewData('totals');

        $rowGross = round(array_sum(array_column($rows, 'gross')), 2);
        $this->assertEquals($rowGross, $totals['gross']);
    }

    // TC-18: Multiple refund transactions on the same order are all summed
    public function test_rm001_tc18_multiple_refund_transactions_summed(): void
    {
        $admin = $this->makeAdmin();
        $user  = $this->makeUser();

        $order = Order::factory()->create([
            'user_id'    => $user->id,
            'status'     => 'refunded',
            'total'      => 200.00,
            'created_at' => now(),
        ]);

        // Two partial refunds
        RefundTransaction::create(['order_id' => $order->id, 'amount' => 80.00, 'stripe_refund_id' => 're_a']);
        RefundTransaction::create(['order_id' => $order->id, 'amount' => 70.00, 'stripe_refund_id' => 're_b']);

        $response = $this->actingAs($admin)
            ->get(route('admin.revenue.index', ['period' => 'daily']));

        $response->assertStatus(200);
        $totals = $response->viewData('totals');
        $this->assertEquals(150.00, $totals['refunds']); // 80 + 70
    }
}
