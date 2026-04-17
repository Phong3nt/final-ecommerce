<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * AD-003 — As an admin, I want to see top-selling products so I can prioritize inventory.
 *
 * Acceptance criteria:
 *   - Top 10 list by units sold and revenue
 *   - Filterable by date range
 */
class AdminTopSellingProductsTest extends TestCase
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

    private function createOrderItem(
        User $buyer,
        Product $product,
        int $quantity,
        float $subtotal,
        string $status = 'paid',
        ?Carbon $createdAt = null
    ): void {
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
    }

    // TC-01: Guest is redirected to login
    public function test_ad003_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('admin.dashboard'));

        $response->assertRedirect(route('login'));
    }

    // TC-02: Non-admin is forbidden
    public function test_ad003_non_admin_is_forbidden(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertStatus(403);
    }

    // TC-03: Admin sees Top-Selling section and expected columns
    public function test_ad003_admin_sees_top_selling_section(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Top-Selling Products');
        $response->assertSee('Units Sold');
        $response->assertSee('Revenue ($)');
        $response->assertSee('top_selling_start', false);
        $response->assertSee('top_selling_end', false);
    }

    // TC-04: Rows are sorted by units sold desc, then revenue desc
    public function test_ad003_rows_are_sorted_by_units_then_revenue(): void
    {
        $admin = $this->makeAdmin();
        $buyer = $this->makeUser();

        $productA = Product::factory()->create(['name' => 'Sort Product A']);
        $productB = Product::factory()->create(['name' => 'Sort Product B']);
        $productC = Product::factory()->create(['name' => 'Sort Product C']);

        $this->createOrderItem($buyer, $productA, 5, 100.00, 'paid');
        $this->createOrderItem($buyer, $productB, 5, 130.00, 'paid');
        $this->createOrderItem($buyer, $productC, 8, 80.00, 'paid');

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertSeeInOrder([
            'Sort Product C',
            'Sort Product B',
            'Sort Product A',
        ]);
    }

    // TC-05: List is limited to top 10 products
    public function test_ad003_list_is_limited_to_top_10(): void
    {
        $admin = $this->makeAdmin();
        $buyer = $this->makeUser();

        for ($i = 1; $i <= 12; $i++) {
            $product = Product::factory()->create([
                'name' => sprintf('Limit Product %02d', $i),
            ]);

            $this->createOrderItem($buyer, $product, 13 - $i, (13 - $i) * 10.0, 'paid');
        }

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        for ($i = 1; $i <= 10; $i++) {
            $response->assertSee(sprintf('Limit Product %02d', $i));
        }

        $response->assertDontSee('Limit Product 11');
        $response->assertDontSee('Limit Product 12');
    }

    // TC-06: Only revenue statuses are included
    public function test_ad003_excludes_non_revenue_status_orders(): void
    {
        $admin = $this->makeAdmin();
        $buyer = $this->makeUser();

        $included = Product::factory()->create(['name' => 'Included Revenue Product']);
        $pending = Product::factory()->create(['name' => 'Pending Product']);
        $cancelled = Product::factory()->create(['name' => 'Cancelled Product']);

        $this->createOrderItem($buyer, $included, 2, 50.00, 'paid');
        $this->createOrderItem($buyer, $pending, 9, 200.00, 'pending');
        $this->createOrderItem($buyer, $cancelled, 7, 180.00, 'cancelled');

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertSee('Included Revenue Product');
        $response->assertDontSee('Pending Product');
        $response->assertDontSee('Cancelled Product');
    }

    // TC-07: Start date filter excludes older sales
    public function test_ad003_start_date_filter_excludes_older_sales(): void
    {
        $admin = $this->makeAdmin();
        $buyer = $this->makeUser();

        $oldProduct = Product::factory()->create(['name' => 'Old Date Product']);
        $newProduct = Product::factory()->create(['name' => 'New Date Product']);

        $this->createOrderItem($buyer, $oldProduct, 4, 40.00, 'paid', now()->subDays(20));
        $this->createOrderItem($buyer, $newProduct, 6, 60.00, 'paid', now()->subDays(2));

        $response = $this->actingAs($admin)->get(route('admin.dashboard', [
            'top_selling_start' => now()->subDays(7)->toDateString(),
        ]));

        $response->assertSee('New Date Product');
        $response->assertDontSee('Old Date Product');
    }

    // TC-08: End date filter excludes newer sales
    public function test_ad003_end_date_filter_excludes_newer_sales(): void
    {
        $admin = $this->makeAdmin();
        $buyer = $this->makeUser();

        $oldProduct = Product::factory()->create(['name' => 'Before End Date Product']);
        $newProduct = Product::factory()->create(['name' => 'After End Date Product']);

        $this->createOrderItem($buyer, $oldProduct, 3, 30.00, 'paid', now()->subDays(10));
        $this->createOrderItem($buyer, $newProduct, 9, 90.00, 'paid', now()->subDay());

        $response = $this->actingAs($admin)->get(route('admin.dashboard', [
            'top_selling_end' => now()->subDays(5)->toDateString(),
        ]));

        $response->assertSee('Before End Date Product');
        $response->assertDontSee('After End Date Product');
    }

    // TC-09: Empty state is shown when there is no revenue-status sales data
    public function test_ad003_shows_empty_state_when_no_sales(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertSee('No sales in this period.');
    }
}
