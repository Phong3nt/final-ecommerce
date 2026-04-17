<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * OM-004 — As an admin, I want to export orders to CSV so I can share data with logistics partners.
 *
 * Acceptance criteria:
 *   - Filtered result set is exported
 *   - CSV includes order ID, customer, items, total, status, date
 */
class AdminOrderExportCsvTest extends TestCase
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

    // TC-01: Guest is redirected from export endpoint
    public function test_om004_guest_is_redirected_from_export(): void
    {
        $response = $this->get(route('admin.orders.export'));

        $response->assertRedirect(route('login'));
    }

    // TC-02: Non-admin gets 403 on export
    public function test_om004_non_admin_gets_403_on_export(): void
    {
        $response = $this->actingAs($this->makeUser())
            ->get(route('admin.orders.export'));

        $response->assertForbidden();
    }

    // TC-03: Admin gets 200 with CSV content-type
    public function test_om004_admin_gets_csv_response(): void
    {
        $response = $this->actingAs($this->makeAdmin())
            ->get(route('admin.orders.export'));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    }

    // TC-04: Response has Content-Disposition attachment header
    public function test_om004_response_has_attachment_disposition(): void
    {
        $response = $this->actingAs($this->makeAdmin())
            ->get(route('admin.orders.export'));

        $disposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('attachment', $disposition);
        $this->assertStringContainsString('orders-', $disposition);
        $this->assertStringContainsString('.csv', $disposition);
    }

    // TC-05: CSV contains header row with required columns
    public function test_om004_csv_contains_header_row(): void
    {
        $response = $this->actingAs($this->makeAdmin())
            ->get(route('admin.orders.export'));

        $csv = $response->streamedContent();
        $this->assertStringContainsString('Order ID', $csv);
        $this->assertStringContainsString('Customer Name', $csv);
        $this->assertStringContainsString('Customer Email', $csv);
        $this->assertStringContainsString('Items', $csv);
        $this->assertStringContainsString('Total', $csv);
        $this->assertStringContainsString('Status', $csv);
        $this->assertStringContainsString('Date', $csv);
    }

    // TC-06: CSV contains order ID, customer name, and email
    public function test_om004_csv_contains_order_id_and_customer(): void
    {
        $user = User::factory()->create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
        $order = Order::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($this->makeAdmin())
            ->get(route('admin.orders.export'));

        $csv = $response->streamedContent();
        $this->assertStringContainsString((string) $order->id, $csv);
        $this->assertStringContainsString('Jane Doe', $csv);
        $this->assertStringContainsString('jane@example.com', $csv);
    }

    // TC-07: CSV contains items summary (product name x quantity)
    public function test_om004_csv_contains_items_summary(): void
    {
        $order = Order::factory()->create();
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_name' => 'Blue Widget',
            'quantity' => 3,
        ]);

        $response = $this->actingAs($this->makeAdmin())
            ->get(route('admin.orders.export'));

        $csv = $response->streamedContent();
        $this->assertStringContainsString('Blue Widget', $csv);
        $this->assertStringContainsString('x3', $csv);
    }

    // TC-08: CSV contains order total formatted to 2 decimal places
    public function test_om004_csv_contains_total(): void
    {
        $user = User::factory()->create();
        Order::factory()->create(['user_id' => $user->id, 'total' => 125.50]);

        $response = $this->actingAs($this->makeAdmin())
            ->get(route('admin.orders.export'));

        $csv = $response->streamedContent();
        $this->assertStringContainsString('125.50', $csv);
    }

    // TC-09: CSV contains status and date
    public function test_om004_csv_contains_status_and_date(): void
    {
        $user = User::factory()->create();
        Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'shipped',
            'created_at' => '2026-03-15 10:00:00',
        ]);

        $response = $this->actingAs($this->makeAdmin())
            ->get(route('admin.orders.export'));

        $csv = $response->streamedContent();
        $this->assertStringContainsString('shipped', $csv);
        $this->assertStringContainsString('2026-03-15', $csv);
    }

    // TC-10: Export with no orders returns only header row
    public function test_om004_empty_export_returns_only_header(): void
    {
        $response = $this->actingAs($this->makeAdmin())
            ->get(route('admin.orders.export'));

        $csv = $response->streamedContent();
        $lines = array_filter(explode("\n", trim($csv)));
        $this->assertCount(1, $lines); // only the header
        $this->assertStringContainsString('Order ID', $lines[array_key_first($lines)]);
    }

    // TC-11: Export respects status filter — only matching orders exported
    public function test_om004_export_respects_status_filter(): void
    {
        $user = User::factory()->create();
        Order::factory()->create(['user_id' => $user->id, 'status' => 'paid']);
        Order::factory()->create(['user_id' => $user->id, 'status' => 'pending']);

        $response = $this->actingAs($this->makeAdmin())
            ->get(route('admin.orders.export', ['status' => 'paid']));

        $csv = $response->streamedContent();
        // 'paid' appears in the data row; 'pending' status should not appear at all
        $this->assertStringContainsString('paid', $csv);
        $this->assertStringNotContainsString('pending', $csv);
        $lines = array_values(array_filter(explode("\n", trim($csv))));
        $this->assertCount(2, $lines); // header + 1 data row
    }

    // TC-12: Export respects date_from filter
    public function test_om004_export_respects_date_from_filter(): void
    {
        $user = User::factory()->create();
        Order::factory()->create(['user_id' => $user->id, 'created_at' => '2026-01-10']);
        Order::factory()->create(['user_id' => $user->id, 'created_at' => '2026-04-01']);

        $response = $this->actingAs($this->makeAdmin())
            ->get(route('admin.orders.export', ['date_from' => '2026-03-01']));

        $csv = $response->streamedContent();
        // Only the recent order (2026-04-01) should be in the export
        $this->assertStringContainsString('2026-04-01', $csv);
        $this->assertStringNotContainsString('2026-01-10', $csv);
        $lines = array_values(array_filter(explode("\n", trim($csv))));
        $this->assertCount(2, $lines); // header + 1 data row
    }

    // TC-13: Export respects customer filter
    public function test_om004_export_respects_customer_filter(): void
    {
        $bob = User::factory()->create(['name' => 'Bob Smith', 'email' => 'bob@example.com']);
        $carol = User::factory()->create(['name' => 'Carol Jones', 'email' => 'carol@example.com']);
        Order::factory()->create(['user_id' => $bob->id]);
        Order::factory()->create(['user_id' => $carol->id]);

        $response = $this->actingAs($this->makeAdmin())
            ->get(route('admin.orders.export', ['customer' => 'Bob']));

        $csv = $response->streamedContent();
        // Bob's email in CSV; Carol's must not be
        $this->assertStringContainsString('bob@example.com', $csv);
        $this->assertStringNotContainsString('carol@example.com', $csv);
        $lines = array_values(array_filter(explode("\n", trim($csv))));
        $this->assertCount(2, $lines); // header + 1 data row
    }

    // TC-14: Orders index page shows Export CSV link
    public function test_om004_orders_index_shows_export_csv_link(): void
    {
        $response = $this->actingAs($this->makeAdmin())
            ->get(route('admin.orders.index'));

        $response->assertOk();
        $response->assertSee('Export CSV');
        $response->assertSee(route('admin.orders.export'));
    }
}
