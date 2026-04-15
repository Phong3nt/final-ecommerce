<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductDetailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'user',  'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    }

    // TC-01 (Happy): Detail page returns 200 for a valid slug
    public function test_pc005_detail_page_returns_200_for_valid_slug(): void
    {
        $product = Product::factory()->create(['slug' => 'my-product']);

        $response = $this->get(route('products.show', 'my-product'));

        $response->assertStatus(200);
    }

    // TC-02 (Happy): Product name is displayed on detail page
    public function test_pc005_product_name_shown_on_detail_page(): void
    {
        $product = Product::factory()->create(['name' => 'Super Widget', 'slug' => 'super-widget']);

        $response = $this->get(route('products.show', 'super-widget'));

        $response->assertSee('Super Widget');
    }

    // TC-03 (Happy): Product description is displayed on detail page
    public function test_pc005_product_description_shown_on_detail_page(): void
    {
        $product = Product::factory()->create([
            'slug'        => 'described-product',
            'description' => 'A uniquely described product for testing.',
        ]);

        $response = $this->get(route('products.show', 'described-product'));

        $response->assertSee('A uniquely described product for testing.');
    }

    // TC-04 (Happy): Product price is displayed on detail page
    public function test_pc005_product_price_shown_on_detail_page(): void
    {
        $product = Product::factory()->create(['slug' => 'priced-product', 'price' => 49.99]);

        $response = $this->get(route('products.show', 'priced-product'));

        $response->assertSee('49.99');
    }

    // TC-05 (Happy): In Stock status shown when stock > 0
    public function test_pc005_in_stock_status_shown_when_stock_positive(): void
    {
        $product = Product::factory()->create(['slug' => 'stocked-product', 'stock' => 10]);

        $response = $this->get(route('products.show', 'stocked-product'));

        $response->assertSee('In Stock');
    }

    // TC-06 (Happy): Out of Stock status shown when stock = 0
    public function test_pc005_out_of_stock_status_shown_when_stock_zero(): void
    {
        $product = Product::factory()->outOfStock()->create(['slug' => 'empty-product']);

        $response = $this->get(route('products.show', 'empty-product'));

        $response->assertSee('Out of Stock');
    }

    // TC-07 (Happy): SKU is displayed on detail page
    public function test_pc005_sku_shown_on_detail_page(): void
    {
        $product = Product::factory()->create(['slug' => 'sku-product', 'sku' => 'TEST-1234']);

        $response = $this->get(route('products.show', 'sku-product'));

        $response->assertSee('TEST-1234');
    }

    // TC-08 (Happy): Category name is displayed on detail page
    public function test_pc005_category_shown_on_detail_page(): void
    {
        $cat     = Category::factory()->create(['name' => 'Gadgets']);
        $product = Product::factory()->create(['slug' => 'category-product', 'category_id' => $cat->id]);

        $response = $this->get(route('products.show', 'category-product'));

        $response->assertSee('Gadgets');
    }

    // TC-09 (Happy): Rating is displayed on detail page
    public function test_pc005_rating_shown_on_detail_page(): void
    {
        $product = Product::factory()->create(['slug' => 'rated-product', 'rating' => 4.7]);

        $response = $this->get(route('products.show', 'rated-product'));

        $response->assertSee('4.7');
    }

    // TC-10 (Happy): Related products section shown when same-category products exist
    public function test_pc005_related_products_section_shown(): void
    {
        $cat     = Category::factory()->create(['name' => 'Tech']);
        $product = Product::factory()->create(['name' => 'Main Product',    'slug' => 'main-product',    'category_id' => $cat->id]);
        $related = Product::factory()->create(['name' => 'Related Product', 'slug' => 'related-product', 'category_id' => $cat->id]);

        $response = $this->get(route('products.show', 'main-product'));

        $response->assertSee('Related Products');
        $response->assertSee('Related Product');
        // current product does not appear inside the related section (verified by controller: excludes self)
    }

    // TC-11 (Edge): Non-existent slug returns 404
    public function test_pc005_nonexistent_slug_returns_404(): void
    {
        $response = $this->get(route('products.show', 'does-not-exist'));

        $response->assertStatus(404);
    }

    // TC-12 (Performance/Security): Detail page accessible without login and responds within 2 seconds
    public function test_pc005_detail_page_accessible_without_login_and_fast(): void
    {
        $product = Product::factory()->create(['slug' => 'public-product']);

        $start   = microtime(true);
        $response = $this->get(route('products.show', 'public-product'));
        $elapsed = microtime(true) - $start;

        $response->assertStatus(200);
        $this->assertLessThan(2.0, $elapsed, 'Detail page exceeded 2 seconds.');
    }
}
