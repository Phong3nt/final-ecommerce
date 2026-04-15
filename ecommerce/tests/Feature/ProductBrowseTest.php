<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductBrowseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    }

    // TC-01 (Happy): GET /products returns 200 without login
    public function test_pc001_product_listing_accessible_without_login(): void
    {
        $response = $this->get(route('products.index'));

        $response->assertStatus(200);
    }

    // TC-02 (Happy): Product name, price, and stock status visible in listing
    public function test_pc001_product_listing_shows_name_price_and_stock_status(): void
    {
        Product::factory()->create(['name' => 'Test Widget', 'price' => 19.99, 'stock' => 5]);

        $response = $this->get(route('products.index'));

        $response->assertSee('Test Widget');
        $response->assertSee('19.99');
        $response->assertSee('In Stock');
    }

    // TC-03 (Happy): Out-of-stock product shows "Out of Stock"
    public function test_pc001_out_of_stock_product_shows_correct_status(): void
    {
        Product::factory()->outOfStock()->create(['name' => 'Sold Out Item']);

        $response = $this->get(route('products.index'));

        $response->assertSee('Sold Out Item');
        $response->assertSee('Out of Stock');
    }

    // TC-04 (Happy): Paginated at 12 per page — first page shows 12 products
    public function test_pc001_listing_paginates_at_12_per_page(): void
    {
        Product::factory()->count(20)->create();

        $response = $this->get(route('products.index'));

        $response->assertStatus(200);
        $products = $response->viewData('products');
        $this->assertCount(12, $products->items());
    }

    // TC-05 (Happy): Second page accessible and shows remaining products
    public function test_pc001_second_page_shows_remaining_products(): void
    {
        Product::factory()->count(20)->create();

        $response = $this->get(route('products.index') . '?page=2');

        $response->assertStatus(200);
        $products = $response->viewData('products');
        $this->assertCount(8, $products->items());
    }

    // TC-06 (Happy): Empty product table shows "No products available"
    public function test_pc001_empty_catalog_shows_no_products_message(): void
    {
        $response = $this->get(route('products.index'));

        $response->assertSee('No products available');
    }

    // TC-07 (Happy): Products are ordered newest first (latest)
    public function test_pc001_products_ordered_newest_first(): void
    {
        $old = Product::factory()->create(['name' => 'Old Product', 'created_at' => now()->subDays(2)]);
        $new = Product::factory()->create(['name' => 'New Product', 'created_at' => now()]);

        $response = $this->get(route('products.index'));

        $content = $response->getContent();
        $this->assertLessThan(
            strpos($content, 'Old Product'),
            strpos($content, 'New Product'),
            'Newest product should appear before older product.'
        );
    }

    // TC-08 (Happy): Pagination links present when more than 12 products
    public function test_pc001_pagination_links_present_with_more_than_12_products(): void
    {
        Product::factory()->count(15)->create();

        $response = $this->get(route('products.index'));

        // Paginator renders a nav or pagination list
        $response->assertSee('page=2');
    }

    // TC-09 (Security): XSS in product name is escaped in output
    public function test_pc001_xss_in_product_name_is_escaped(): void
    {
        Product::factory()->create(['name' => '<script>alert("xss")</script>']);

        $response = $this->get(route('products.index'));

        $response->assertDontSee('<script>alert("xss")</script>', false);
        $response->assertSee('&lt;script&gt;', false);
    }

    // TC-10 (Edge): Product with null image renders without error
    public function test_pc001_product_with_no_image_renders_without_error(): void
    {
        Product::factory()->create(['name' => 'No Image Product', 'image' => null]);

        $response = $this->get(route('products.index'));

        $response->assertStatus(200);
        $response->assertSee('No Image Product');
    }

    // TC-11 (Edge): page=999 beyond last page returns 200 with empty grid
    public function test_pc001_out_of_range_page_returns_200(): void
    {
        Product::factory()->count(3)->create();

        $response = $this->get(route('products.index') . '?page=999');

        $response->assertStatus(200);
    }

    // TC-12 (Performance): Product listing responds within 2 seconds
    public function test_pc001_product_listing_responds_within_two_seconds(): void
    {
        Product::factory()->count(12)->create();

        $start = microtime(true);
        $this->get(route('products.index'));
        $elapsed = microtime(true) - $start;

        $this->assertLessThan(2.0, $elapsed, 'Product listing exceeded 2 seconds.');
    }
}
