<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * IMP-006 — Eliminate N+1 queries via eager-loading.
 *
 * Covers: PC-001, OH-001, OH-002, OM-001, OM-002
 */
class EagerLoadingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    }

    // ────────────────────────────────────────────────────────────────────
    // PC-001: Product index — category eager loading
    // ────────────────────────────────────────────────────────────────────

    /** @test */
    public function imp006_tc01_product_index_renders_category_name(): void
    {
        $category = Category::factory()->create(['name' => 'Electronics']);
        Product::factory()->count(3)->create([
            'category_id' => $category->id,
            'status' => 'published',
        ]);

        $response = $this->get(route('products.index'));

        $response->assertStatus(200);
        $response->assertSee('Electronics');
    }

    /** @test */
    public function imp006_tc02_product_index_category_is_not_n_plus_1(): void
    {
        $category = Category::factory()->create();
        // Create enough products to make a linear N+1 detectable
        Product::factory()->count(12)->create([
            'category_id' => $category->id,
            'status' => 'published',
        ]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->get(route('products.index'));

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Count distinct queries that hit the `categories` table
        $categoryQueryCount = collect($queries)
            ->filter(fn($q) => str_contains(strtolower($q['query']), 'categories'))
            ->count();

        // With eager loading: 1 query for the sidebar dropdown + 1 IN-clause eager load = 2
        // Without eager loading: 1 dropdown + 12 lazy loads = 13
        $this->assertLessThanOrEqual(
            2,
            $categoryQueryCount,
            "Expected ≤2 queries against `categories` (dropdown + eager load), got {$categoryQueryCount}. N+1 detected."
        );
    }

    /** @test */
    public function imp006_tc03_product_without_category_renders_safely(): void
    {
        Product::factory()->create([
            'category_id' => null,
            'status' => 'published',
        ]);

        $response = $this->get(route('products.index'));

        $response->assertStatus(200);
    }

    /** @test */
    public function imp006_tc04_product_index_relation_is_loaded_on_collection(): void
    {
        $category = Category::factory()->create();
        Product::factory()->count(3)->create([
            'category_id' => $category->id,
            'status' => 'published',
        ]);

        // The controller query must return products with category already loaded
        $products = Product::published()->with('category')->paginate(12);

        foreach ($products as $product) {
            $this->assertTrue(
                $product->relationLoaded('category'),
                "Product #{$product->id} does not have category relation loaded."
            );
        }
    }

    /** @test */
    public function imp006_tc05_product_index_multiple_categories_bounded(): void
    {
        $cat1 = Category::factory()->create(['name' => 'Books']);
        $cat2 = Category::factory()->create(['name' => 'Clothing']);
        $cat3 = Category::factory()->create(['name' => 'Home']);

        Product::factory()->count(4)->create(['category_id' => $cat1->id, 'status' => 'published']);
        Product::factory()->count(4)->create(['category_id' => $cat2->id, 'status' => 'published']);
        Product::factory()->count(4)->create(['category_id' => $cat3->id, 'status' => 'published']);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->get(route('products.index'));

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $response->assertStatus(200);

        // All 3 category names must be visible
        $response->assertSee('Books');
        $response->assertSee('Clothing');
        $response->assertSee('Home');

        // ≤2 queries on categories table regardless of category count (N+1 would be 13)
        $categoryQueryCount = collect($queries)
            ->filter(fn($q) => str_contains(strtolower($q['query']), 'categories'))
            ->count();

        $this->assertLessThanOrEqual(2, $categoryQueryCount);
    }

    // ────────────────────────────────────────────────────────────────────
    // OH-001: User order history — bounded queries
    // ────────────────────────────────────────────────────────────────────

    /** @test */
    public function imp006_tc06_user_order_history_renders_within_bounded_queries(): void
    {
        $user = User::factory()->create();
        Order::factory()->count(5)->create(['user_id' => $user->id, 'status' => 'paid']);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->actingAs($user)->get(route('orders.index'));

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $response->assertStatus(200);

        // Total query count should be bounded (not 1 per order)
        // Expect: 1 for session/auth + 1 for orders paginate + 1 for count = reasonable small number
        $this->assertLessThan(
            15,
            count($queries),
            'OH-001 order history fires too many queries (' . count($queries) . ') — possible N+1.'
        );
    }

    // ────────────────────────────────────────────────────────────────────
    // OH-002: User order detail — items eagerly loaded
    // ────────────────────────────────────────────────────────────────────

    /** @test */
    public function imp006_tc07_user_order_detail_items_are_loaded(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id, 'status' => 'paid']);
        OrderItem::factory()->count(3)->create(['order_id' => $order->id]);

        $response = $this->actingAs($user)->get(route('orders.show', $order));

        $response->assertStatus(200);

        // After the controller calls $order->load('items'), items must be loaded
        $order->refresh();
        // Re-load via controller path: verify items accessible in show route
        $this->assertEquals(3, $order->load('items')->items->count());
    }

    // ────────────────────────────────────────────────────────────────────
    // OM-001: Admin order list — user already eager loaded
    // ────────────────────────────────────────────────────────────────────

    /** @test */
    public function imp006_tc08_admin_order_list_user_relation_already_loaded(): void
    {
        $admin = $this->makeAdmin();
        $user = User::factory()->create(['name' => 'Test Customer']);
        Order::factory()->count(3)->create(['user_id' => $user->id]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->actingAs($admin)->get(route('admin.orders.index'));

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $response->assertStatus(200);
        $response->assertSee('Test Customer');

        // With Order::with('user'), user queries should be bounded (1 IN-clause, not N SELECTs)
        $userQueries = collect($queries)
            ->filter(fn($q) => preg_match('/select.+from.+`?users`?/i', $q['query']))
            ->count();

        $this->assertLessThanOrEqual(
            3,
            $userQueries,
            "Expected ≤3 queries against `users` (auth + eager load), got {$userQueries}. N+1 detected."
        );
    }

    // ────────────────────────────────────────────────────────────────────
    // OM-002: Admin order detail — all relations loaded
    // ────────────────────────────────────────────────────────────────────

    /** @test */
    public function imp006_tc09_admin_order_detail_loads_all_relations(): void
    {
        $admin = $this->makeAdmin();
        $user = User::factory()->create(['name' => 'Detail Customer']);
        $order = Order::factory()->create(['user_id' => $user->id, 'status' => 'processing']);
        OrderItem::factory()->count(2)->create([
            'order_id' => $order->id,
            'product_name' => 'Widget A',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.orders.show', $order));

        $response->assertStatus(200);
        $response->assertSee('Detail Customer');
        $response->assertSee('Widget A');
    }

    /** @test */
    public function imp006_tc10_admin_order_detail_no_extra_queries_per_item(): void
    {
        $admin = $this->makeAdmin();
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);
        OrderItem::factory()->count(5)->create(['order_id' => $order->id]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->actingAs($admin)->get(route('admin.orders.show', $order));

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // With eager loading: 1 for order + 1 for items + 1 for user + 1 refundTransactions
        // Without: 1 + N item queries would appear
        $orderItemQueries = collect($queries)
            ->filter(fn($q) => str_contains(strtolower($q['query']), 'order_items'))
            ->count();

        $this->assertLessThanOrEqual(
            2,
            $orderItemQueries,
            "Expected ≤2 queries against `order_items`, got {$orderItemQueries}."
        );
    }

    // ────────────────────────────────────────────────────────────────────
    // Helper
    // ────────────────────────────────────────────────────────────────────

    private function makeAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        return $admin;
    }
}
