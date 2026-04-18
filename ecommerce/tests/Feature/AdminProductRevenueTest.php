<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * RM-002 — As an admin, I want to see revenue by category/product
 *           so I can identify bestsellers.
 *
 * Acceptance criteria:
 *   - Sortable table (product name, category, units sold, gross revenue)
 *   - Filterable by category and date range
 *   - Exportable to CSV
 */
class AdminProductRevenueTest extends TestCase
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

    /**
     * Create an order with one item, returning the order.
     */
    private function createSale(
        User $buyer,
        Product $product,
        int $quantity,
        float $subtotal,
        string $status = 'paid',
        ?Carbon $createdAt = null
    ): Order {
        $createdAt = $createdAt ?? now();

        $order = Order::factory()->for($buyer)->create([
            'status' => $status,
            'subtotal' => $subtotal,
            'shipping_cost' => 0,
            'total' => $subtotal,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        OrderItem::factory()->for($order)->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => $quantity,
            'unit_price' => round($subtotal / max($quantity, 1), 2),
            'subtotal' => $subtotal,
        ]);

        return $order;
    }

    // ── Auth ─────────────────────────────────────────────────────────────────

    // TC-01: Guest redirected to login for products page
    public function test_rm002_tc01_guest_redirected_to_login(): void
    {
        $this->get(route('admin.revenue.products'))
            ->assertRedirect(route('login'));
    }

    // TC-02: Non-admin gets 403 on products page
    public function test_rm002_tc02_non_admin_gets_403(): void
    {
        $this->actingAs($this->makeUser())
            ->get(route('admin.revenue.products'))
            ->assertStatus(403);
    }

    // TC-03: Admin gets 200 on products page
    public function test_rm002_tc03_admin_gets_200(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get(route('admin.revenue.products'))
            ->assertStatus(200);
    }

    // TC-04: Guest redirected to login for CSV export
    public function test_rm002_tc04_guest_redirected_for_export(): void
    {
        $this->get(route('admin.revenue.products.export'))
            ->assertRedirect(route('login'));
    }

    // TC-05: Non-admin gets 403 on CSV export
    public function test_rm002_tc05_non_admin_gets_403_on_export(): void
    {
        $this->actingAs($this->makeUser())
            ->get(route('admin.revenue.products.export'))
            ->assertStatus(403);
    }

    // ── Revenue data ──────────────────────────────────────────────────────────

    // TC-06: Products appear in the table with correct units_sold and gross_revenue
    public function test_rm002_tc06_product_revenue_sums_correctly(): void
    {
        $admin = $this->makeAdmin();
        $buyer = $this->makeUser();
        $product = Product::factory()->create(['name' => 'Widget A']);

        $this->createSale($buyer, $product, 3, 90.00, 'paid');   // 3 units, $90
        $this->createSale($buyer, $product, 2, 60.00, 'paid');   // 2 more,  $60

        $response = $this->actingAs($admin)->get(route('admin.revenue.products'));

        $response->assertStatus(200);
        $rows = $response->viewData('rows');

        $row = collect($rows)->firstWhere('product_name', 'Widget A');
        $this->assertNotNull($row);
        $this->assertEquals(5, $row->units_sold);
        $this->assertEquals(150.00, (float) $row->gross_revenue);
    }

    // TC-07: Only revenue-status orders are counted (paid/processing/shipped/delivered)
    public function test_rm002_tc07_only_revenue_status_orders_counted(): void
    {
        $admin = $this->makeAdmin();
        $buyer = $this->makeUser();
        $product = Product::factory()->create(['name' => 'Widget B']);

        $this->createSale($buyer, $product, 5, 250.00, 'paid');
        $this->createSale($buyer, $product, 9, 450.00, 'cancelled');
        $this->createSale($buyer, $product, 4, 200.00, 'pending');

        $response = $this->actingAs($admin)->get(route('admin.revenue.products'));
        $rows = $response->viewData('rows');

        $row = collect($rows)->firstWhere('product_name', 'Widget B');
        $this->assertNotNull($row);
        $this->assertEquals(5, $row->units_sold);
        $this->assertEquals(250.00, (float) $row->gross_revenue);
    }

    // TC-08: Category name is shown in the table row
    public function test_rm002_tc08_category_name_shown(): void
    {
        $admin = $this->makeAdmin();
        $buyer = $this->makeUser();
        $category = Category::factory()->create(['name' => 'Electronics']);
        $product = Product::factory()->create(['name' => 'Gadget', 'category_id' => $category->id]);

        $this->createSale($buyer, $product, 1, 100.00, 'paid');

        $response = $this->actingAs($admin)->get(route('admin.revenue.products'));
        $rows = $response->viewData('rows');

        $row = collect($rows)->firstWhere('product_name', 'Gadget');
        $this->assertNotNull($row);
        $this->assertEquals('Electronics', $row->category_name);
    }

    // TC-09: Filtering by category returns only products in that category
    public function test_rm002_tc09_filter_by_category(): void
    {
        $admin = $this->makeAdmin();
        $buyer = $this->makeUser();
        $catA = Category::factory()->create(['name' => 'Category A']);
        $catB = Category::factory()->create(['name' => 'Category B']);
        $prodA = Product::factory()->create(['name' => 'Prod A', 'category_id' => $catA->id]);
        $prodB = Product::factory()->create(['name' => 'Prod B', 'category_id' => $catB->id]);

        $this->createSale($buyer, $prodA, 2, 80.00, 'paid');
        $this->createSale($buyer, $prodB, 3, 90.00, 'paid');

        $response = $this->actingAs($admin)
            ->get(route('admin.revenue.products', ['category' => $catA->id]));

        $response->assertStatus(200);
        $rows = $response->viewData('rows');

        $this->assertCount(1, $rows);
        $this->assertEquals('Prod A', $rows->first()->product_name);
    }

    // TC-10: Date range filter (date_from) excludes older orders
    public function test_rm002_tc10_date_from_excludes_old_orders(): void
    {
        $admin = $this->makeAdmin();
        $buyer = $this->makeUser();
        $product = Product::factory()->create(['name' => 'Timely Product']);

        $this->createSale($buyer, $product, 2, 100.00, 'paid', Carbon::parse('2024-01-01'));
        $this->createSale($buyer, $product, 3, 150.00, 'paid', Carbon::parse('2024-06-01'));

        $response = $this->actingAs($admin)->get(route('admin.revenue.products', [
            'date_from' => '2024-05-01',
        ]));

        $response->assertStatus(200);
        $rows = $response->viewData('rows');

        $row = collect($rows)->firstWhere('product_name', 'Timely Product');
        $this->assertNotNull($row);
        $this->assertEquals(3, $row->units_sold);
        $this->assertEquals(150.00, (float) $row->gross_revenue);
    }

    // TC-11: Date range filter (date_to) excludes newer orders
    public function test_rm002_tc11_date_to_excludes_new_orders(): void
    {
        $admin = $this->makeAdmin();
        $buyer = $this->makeUser();
        $product = Product::factory()->create(['name' => 'Old Product']);

        $this->createSale($buyer, $product, 4, 200.00, 'paid', Carbon::parse('2024-02-01'));
        $this->createSale($buyer, $product, 7, 350.00, 'paid', Carbon::parse('2024-08-01'));

        $response = $this->actingAs($admin)->get(route('admin.revenue.products', [
            'date_to' => '2024-04-30',
        ]));

        $response->assertStatus(200);
        $rows = $response->viewData('rows');

        $row = collect($rows)->firstWhere('product_name', 'Old Product');
        $this->assertNotNull($row);
        $this->assertEquals(4, $row->units_sold);
        $this->assertEquals(200.00, (float) $row->gross_revenue);
    }

    // TC-12: Default sort is gross_revenue descending (highest first)
    public function test_rm002_tc12_default_sort_is_gross_revenue_desc(): void
    {
        $admin = $this->makeAdmin();
        $buyer = $this->makeUser();
        $cheap = Product::factory()->create(['name' => 'Cheap Product']);
        $pricey = Product::factory()->create(['name' => 'Pricey Product']);

        $this->createSale($buyer, $cheap, 1, 10.00, 'paid');
        $this->createSale($buyer, $pricey, 1, 500.00, 'paid');

        $response = $this->actingAs($admin)->get(route('admin.revenue.products'));

        $response->assertStatus(200);
        $rows = $response->viewData('rows');

        $this->assertEquals('Pricey Product', $rows->first()->product_name);
    }

    // TC-13: Sort by units_sold descending
    public function test_rm002_tc13_sort_by_units_sold_desc(): void
    {
        $admin = $this->makeAdmin();
        $buyer = $this->makeUser();
        $few = Product::factory()->create(['name' => 'Few Units']);
        $many = Product::factory()->create(['name' => 'Many Units']);

        $this->createSale($buyer, $few, 2, 200.00, 'paid');
        $this->createSale($buyer, $many, 9, 90.00, 'paid');

        $response = $this->actingAs($admin)->get(route('admin.revenue.products', [
            'sort' => 'units_sold',
            'direction' => 'desc',
        ]));

        $response->assertStatus(200);
        $rows = $response->viewData('rows');
        $this->assertEquals('Many Units', $rows->first()->product_name);
    }

    // TC-14: Sort by product_name ascending
    public function test_rm002_tc14_sort_by_product_name_asc(): void
    {
        $admin = $this->makeAdmin();
        $buyer = $this->makeUser();
        $prodZ = Product::factory()->create(['name' => 'Zebra']);
        $prodA = Product::factory()->create(['name' => 'Apple']);

        $this->createSale($buyer, $prodZ, 1, 10.00, 'paid');
        $this->createSale($buyer, $prodA, 1, 10.00, 'paid');

        $response = $this->actingAs($admin)->get(route('admin.revenue.products', [
            'sort' => 'product_name',
            'direction' => 'asc',
        ]));

        $response->assertStatus(200);
        $rows = $response->viewData('rows');
        $this->assertEquals('Apple', $rows->first()->product_name);
    }

    // TC-15: Invalid sort column falls back to gross_revenue
    public function test_rm002_tc15_invalid_sort_falls_back_to_gross_revenue(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->get(route('admin.revenue.products', [
            'sort' => 'injection_attempt; DROP TABLE orders;--',
        ]));

        $response->assertStatus(200);
        $this->assertEquals('gross_revenue', $response->viewData('sort'));
    }

    // TC-16: Zero rows shown when no revenue-status orders exist
    public function test_rm002_tc16_zero_rows_when_no_revenue_orders(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->get(route('admin.revenue.products'));

        $response->assertStatus(200);
        $this->assertCount(0, $response->viewData('rows'));
    }

    // ── CSV export ─────────────────────────────────────────────────────────────

    // TC-17: CSV export returns 200 with correct Content-Type
    public function test_rm002_tc17_csv_export_returns_200_with_correct_content_type(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)
            ->get(route('admin.revenue.products.export'));

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    }

    // TC-18: CSV export has correct header row
    public function test_rm002_tc18_csv_export_has_correct_header_row(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)
            ->get(route('admin.revenue.products.export'));

        $response->assertStatus(200);
        $content = $response->getContent();
        $this->assertStringContainsString('Product,Category,Units Sold,Gross Revenue', $content);
    }

    // TC-19: CSV export contains product data rows
    public function test_rm002_tc19_csv_export_contains_product_data(): void
    {
        $admin = $this->makeAdmin();
        $buyer = $this->makeUser();
        $product = Product::factory()->create(['name' => 'Export Product']);

        $this->createSale($buyer, $product, 4, 200.00, 'paid');

        $response = $this->actingAs($admin)
            ->get(route('admin.revenue.products.export'));

        $response->assertStatus(200);
        $content = $response->getContent();
        $this->assertStringContainsString('Export Product', $content);
        $this->assertStringContainsString('200.00', $content);
    }

    // TC-20: CSV export Content-Disposition is an attachment with .csv filename
    public function test_rm002_tc20_csv_export_content_disposition_is_attachment(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)
            ->get(route('admin.revenue.products.export'));

        $response->assertStatus(200);
        $disposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('attachment', $disposition);
        $this->assertStringContainsString('.csv', $disposition);
    }

    // TC-21: CSV export respects category filter
    public function test_rm002_tc21_csv_export_respects_category_filter(): void
    {
        $admin = $this->makeAdmin();
        $buyer = $this->makeUser();
        $catA = Category::factory()->create(['name' => 'Export Cat A']);
        $catB = Category::factory()->create(['name' => 'Export Cat B']);
        $prodA = Product::factory()->create(['name' => 'CSV Prod A', 'category_id' => $catA->id]);
        $prodB = Product::factory()->create(['name' => 'CSV Prod B', 'category_id' => $catB->id]);

        $this->createSale($buyer, $prodA, 1, 50.00, 'paid');
        $this->createSale($buyer, $prodB, 1, 75.00, 'paid');

        $response = $this->actingAs($admin)
            ->get(route('admin.revenue.products.export', ['category' => $catA->id]));

        $content = $response->getContent();
        $this->assertStringContainsString('CSV Prod A', $content);
        $this->assertStringNotContainsString('CSV Prod B', $content);
    }
}
