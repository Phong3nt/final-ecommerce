<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * IMP-014 — Product catalog response caching (Laravel Cache)
 *
 * Verifies that the catalog index, product show, and search pages:
 *   - Use Cache::remember() (cache miss → hit pattern)
 *   - Store results under version-keyed cache entries
 *   - Invalidate (version bump) when a product is saved or deleted
 *   - Serve correct data from cache (no stale results)
 *   - Do NOT cache user-specific review gate data
 */
class ProductCatalogCacheTest extends TestCase
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

    // TC-01 (Happy): Products index returns 200 after caching layer is added
    public function test_imp014_products_index_returns_200(): void
    {
        Product::factory()->count(3)->create();

        $this->get(route('products.index'))->assertStatus(200);
    }

    // TC-02 (Happy): Second identical request is served from cache (version unchanged)
    public function test_imp014_second_identical_request_hits_cache(): void
    {
        Product::factory()->count(3)->create();

        $versionBefore = (int) Cache::get('catalog_version', 0);

        // First request — cache miss
        $this->get(route('products.index'))->assertStatus(200);

        // Second request — same version, cache hit; catalog_version must not change
        $this->get(route('products.index'))->assertStatus(200);

        $versionAfter = (int) Cache::get('catalog_version', 0);
        $this->assertSame($versionBefore, $versionAfter, 'Version must not change between two identical index requests.');
    }

    // TC-03 (Happy): Catalog version increments when a product is created
    public function test_imp014_version_increments_on_product_create(): void
    {
        $versionBefore = (int) Cache::get('catalog_version', 0);

        Product::factory()->create();

        $versionAfter = (int) Cache::get('catalog_version', 0);
        $this->assertGreaterThan($versionBefore, $versionAfter);
    }

    // TC-04 (Happy): Catalog version increments when a product is updated
    public function test_imp014_version_increments_on_product_update(): void
    {
        $product = Product::factory()->create();
        $versionBefore = (int) Cache::get('catalog_version', 0);

        $product->update(['name' => 'Updated Name ' . uniqid()]);

        $versionAfter = (int) Cache::get('catalog_version', 0);
        $this->assertGreaterThan($versionBefore, $versionAfter);
    }

    // TC-05 (Happy): Catalog version increments when a product is deleted
    public function test_imp014_version_increments_on_product_delete(): void
    {
        $product = Product::factory()->create();
        $versionBefore = (int) Cache::get('catalog_version', 0);

        $product->delete();

        $versionAfter = (int) Cache::get('catalog_version', 0);
        $this->assertGreaterThan($versionBefore, $versionAfter);
    }

    // TC-06 (Happy): New product appears on index after creation (cache invalidated)
    public function test_imp014_new_product_appears_after_creation(): void
    {
        // Warm the cache
        $this->get(route('products.index'))->assertStatus(200);

        // Create a new product — should bump version
        $product = Product::factory()->create(['name' => 'Brand New Widget']);

        // Next request gets fresh data
        $this->get(route('products.index'))->assertSee('Brand New Widget');
    }

    // TC-07 (Happy): Deleted product no longer appears after deletion
    public function test_imp014_deleted_product_absent_after_deletion(): void
    {
        $product = Product::factory()->create(['name' => 'To Be Deleted']);

        // Warm cache
        $this->get(route('products.index'))->assertSee('To Be Deleted');

        $product->delete();

        // Cache invalidated — fresh query should exclude soft-deleted product
        $this->get(route('products.index'))->assertDontSee('To Be Deleted');
    }

    // TC-08 (Happy): Search results page returns 200 with caching
    public function test_imp014_search_returns_200_with_caching(): void
    {
        Product::factory()->create(['name' => 'Searchable Widget']);

        $this->get(route('products.search', ['q' => 'Searchable']))->assertStatus(200);
    }

    // TC-09 (Happy): Product show page returns 200 with caching
    public function test_imp014_product_show_returns_200_with_caching(): void
    {
        $product = Product::factory()->create(['status' => 'published']);

        $this->get(route('products.show', $product->slug))->assertStatus(200);
    }

    // TC-10 (Security): User-specific canReview flag is NOT cached — eligible user
    // sees the review form even after another user's uncached request
    public function test_imp014_user_specific_can_review_not_cached(): void
    {
        $buyer   = $this->makeUser();
        $product = Product::factory()->create(['status' => 'published', 'stock' => 10]);
        $order   = Order::factory()->delivered()->create(['user_id' => $buyer->id]);
        OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $product->id]);

        // Guest warms the cache first
        $this->get(route('products.show', $product->slug))->assertStatus(200);

        // Buyer should still see the review form (canReview = true, not served from cache)
        $this->actingAs($buyer)
            ->get(route('products.show', $product->slug))
            ->assertSee('data-imp012="star-input"', false);
    }

    // TC-11 (Edge): Empty search query redirects (cache not involved)
    public function test_imp014_empty_search_redirects(): void
    {
        $this->get(route('products.search', ['q' => '']))->assertRedirect(route('products.index'));
    }

    // TC-12 (Performance): Cached index request responds within 2 seconds
    public function test_imp014_cached_index_request_responds_within_two_seconds(): void
    {
        Product::factory()->count(5)->create();

        // Warm cache
        $this->get(route('products.index'));

        $start   = microtime(true);
        $response = $this->get(route('products.index'));
        $elapsed  = microtime(true) - $start;

        $response->assertStatus(200);
        $this->assertLessThan(2.0, $elapsed, 'Cached products index exceeded 2 seconds.');
    }
}
