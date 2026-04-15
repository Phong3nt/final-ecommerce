<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'user',  'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    }

    // TC-01 (Happy): Filter by category returns only products in that category
    public function test_pc003_filter_by_category_returns_matching_products(): void
    {
        $electronics = Category::factory()->create(['name' => 'Electronics']);
        $clothing    = Category::factory()->create(['name' => 'Clothing']);

        Product::factory()->create(['name' => 'Laptop',    'category_id' => $electronics->id]);
        Product::factory()->create(['name' => 'T-Shirt',   'category_id' => $clothing->id]);

        $response = $this->get(route('products.index', ['category' => $electronics->id]));

        $response->assertStatus(200);
        $response->assertSee('Laptop');
        $response->assertDontSee('T-Shirt');
    }

    // TC-02 (Happy): Filter by min_price excludes cheaper products
    public function test_pc003_filter_by_min_price_excludes_cheaper_products(): void
    {
        Product::factory()->create(['name' => 'Cheap Item',     'price' => 5.00]);
        Product::factory()->create(['name' => 'Expensive Item', 'price' => 150.00]);

        $response = $this->get(route('products.index', ['min_price' => 50]));

        $response->assertSee('Expensive Item');
        $response->assertDontSee('Cheap Item');
    }

    // TC-03 (Happy): Filter by max_price excludes pricier products
    public function test_pc003_filter_by_max_price_excludes_expensive_products(): void
    {
        Product::factory()->create(['name' => 'Budget Item',  'price' => 10.00]);
        Product::factory()->create(['name' => 'Luxury Item',  'price' => 999.00]);

        $response = $this->get(route('products.index', ['max_price' => 100]));

        $response->assertSee('Budget Item');
        $response->assertDontSee('Luxury Item');
    }

    // TC-04 (Happy): Filter by min_rating excludes lower-rated products
    public function test_pc003_filter_by_min_rating_excludes_lower_rated(): void
    {
        Product::factory()->create(['name' => 'Top Rated',  'rating' => 4.8]);
        Product::factory()->create(['name' => 'Low Rated',  'rating' => 2.1]);

        $response = $this->get(route('products.index', ['min_rating' => 4.0]));

        $response->assertSee('Top Rated');
        $response->assertDontSee('Low Rated');
    }

    // TC-05 (Happy): Filters are combinable — category + price range together
    public function test_pc003_combined_category_and_price_filter(): void
    {
        $cat = Category::factory()->create(['name' => 'Tools']);

        Product::factory()->create(['name' => 'Hammer',         'price' => 20.00, 'category_id' => $cat->id]);
        Product::factory()->create(['name' => 'Power Drill',    'price' => 80.00, 'category_id' => $cat->id]);
        Product::factory()->create(['name' => 'Other Category', 'price' => 20.00, 'category_id' => null]);

        $response = $this->get(route('products.index', [
            'category'  => $cat->id,
            'min_price' => 50,
        ]));

        $response->assertSee('Power Drill');
        $response->assertDontSee('Hammer');
        $response->assertDontSee('Other Category');
    }

    // TC-06 (Happy): All three filters combined work together
    public function test_pc003_all_three_filters_combined(): void
    {
        $cat = Category::factory()->create(['name' => 'Gadgets']);

        Product::factory()->create(['name' => 'Perfect Gadget', 'price' => 75.00, 'rating' => 4.5, 'category_id' => $cat->id]);
        Product::factory()->create(['name' => 'Cheap Gadget',   'price' => 10.00, 'rating' => 4.5, 'category_id' => $cat->id]);
        Product::factory()->create(['name' => 'Bad Rated',      'price' => 75.00, 'rating' => 2.0, 'category_id' => $cat->id]);

        $response = $this->get(route('products.index', [
            'category'   => $cat->id,
            'min_price'  => 50,
            'max_price'  => 100,
            'min_rating' => 4.0,
        ]));

        $response->assertSee('Perfect Gadget');
        $response->assertDontSee('Cheap Gadget');
        $response->assertDontSee('Bad Rated');
    }

    // TC-07 (Happy): No filters applied returns all products
    public function test_pc003_no_filters_returns_all_products(): void
    {
        Product::factory()->count(3)->create();

        $response = $this->get(route('products.index'));

        $response->assertStatus(200);
        $products = $response->viewData('products');
        $this->assertEquals(3, $products->total());
    }

    // TC-08 (Happy): Filter state persists in URL (query string preserved in pagination)
    public function test_pc003_filter_state_persists_in_pagination_links(): void
    {
        $cat = Category::factory()->create(['name' => 'Books']);
        Product::factory()->count(15)->create(['category_id' => $cat->id]);

        $response = $this->get(route('products.index', ['category' => $cat->id]));

        $response->assertStatus(200);
        // Pagination links must carry category param
        $response->assertSee('category=' . $cat->id);
    }

    // TC-09 (Happy): Category dropdown is rendered in the filter form
    public function test_pc003_category_dropdown_rendered_in_filter_form(): void
    {
        Category::factory()->create(['name' => 'Sports']);

        $response = $this->get(route('products.index'));

        $response->assertSee('Sports');
        $response->assertSee('filter-form');
    }

    // TC-10 (Edge): Filter by non-existent category returns empty (no products)
    public function test_pc003_filter_by_nonexistent_category_returns_empty(): void
    {
        Product::factory()->create(['name' => 'Any Product']);

        $response = $this->get(route('products.index', ['category' => 99999]));

        $response->assertStatus(200);
        $response->assertSee('No products available');
    }

    // TC-11 (Security): Filter params accessible without login
    public function test_pc003_filter_accessible_without_login(): void
    {
        $response = $this->get(route('products.index', ['min_price' => 10, 'max_price' => 100]));

        $response->assertStatus(200);
    }

    // TC-12 (Performance): Filtered listing responds within 2 seconds
    public function test_pc003_filtered_listing_responds_within_two_seconds(): void
    {
        $cat = Category::factory()->create();
        Product::factory()->count(50)->create(['category_id' => $cat->id]);

        $start = microtime(true);
        $this->get(route('products.index', ['category' => $cat->id, 'min_price' => 10]));
        $elapsed = microtime(true) - $start;

        $this->assertLessThan(2.0, $elapsed, 'Filtered listing exceeded 2 seconds.');
    }
}
