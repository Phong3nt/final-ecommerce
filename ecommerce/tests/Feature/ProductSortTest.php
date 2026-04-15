<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductSortTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'user',  'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    }

    // TC-01 (Happy): Default sort (no param) returns products newest first
    public function test_pc004_default_sort_is_newest_first(): void
    {
        $old = Product::factory()->create(['name' => 'Old Product', 'created_at' => now()->subMinutes(5)]);
        $new = Product::factory()->create(['name' => 'New Product']);

        $response = $this->get(route('products.index'));

        $response->assertStatus(200);
        $products = $response->viewData('products');
        $this->assertEquals($new->id, $products->first()->id);
    }

    // TC-02 (Happy): sort=newest returns products newest first
    public function test_pc004_sort_newest_returns_newest_first(): void
    {
        $old = Product::factory()->create(['name' => 'Old Product', 'created_at' => now()->subMinutes(5)]);
        $new = Product::factory()->create(['name' => 'New Product']);

        $response = $this->get(route('products.index', ['sort' => 'newest']));

        $products = $response->viewData('products');
        $this->assertEquals($new->id, $products->first()->id);
    }

    // TC-03 (Happy): sort=oldest returns products oldest first
    public function test_pc004_sort_oldest_returns_oldest_first(): void
    {
        $old = Product::factory()->create(['name' => 'Old Product']);
        $new = Product::factory()->create(['name' => 'New Product']);

        $response = $this->get(route('products.index', ['sort' => 'oldest']));

        $products = $response->viewData('products');
        $this->assertEquals($old->id, $products->first()->id);
    }

    // TC-04 (Happy): sort=price_asc returns cheapest product first
    public function test_pc004_sort_price_asc_returns_cheapest_first(): void
    {
        Product::factory()->create(['name' => 'Expensive', 'price' => 500.00]);
        Product::factory()->create(['name' => 'Cheap',     'price' => 10.00]);
        Product::factory()->create(['name' => 'Mid',       'price' => 100.00]);

        $response = $this->get(route('products.index', ['sort' => 'price_asc']));

        $products = $response->viewData('products');
        $this->assertEquals('Cheap', $products->first()->name);
    }

    // TC-05 (Happy): sort=price_desc returns most expensive product first
    public function test_pc004_sort_price_desc_returns_most_expensive_first(): void
    {
        Product::factory()->create(['name' => 'Expensive', 'price' => 500.00]);
        Product::factory()->create(['name' => 'Cheap',     'price' => 10.00]);
        Product::factory()->create(['name' => 'Mid',       'price' => 100.00]);

        $response = $this->get(route('products.index', ['sort' => 'price_desc']));

        $products = $response->viewData('products');
        $this->assertEquals('Expensive', $products->first()->name);
    }

    // TC-06 (Happy): sort=rating returns highest-rated product first
    public function test_pc004_sort_rating_returns_highest_rated_first(): void
    {
        Product::factory()->create(['name' => 'Average', 'rating' => 3.0]);
        Product::factory()->create(['name' => 'Best',    'rating' => 4.9]);
        Product::factory()->create(['name' => 'OK',      'rating' => 2.5]);

        $response = $this->get(route('products.index', ['sort' => 'rating']));

        $products = $response->viewData('products');
        $this->assertEquals('Best', $products->first()->name);
    }

    // TC-07 (Happy): Sort dropdown has at least 5 options rendered in HTML
    public function test_pc004_sort_dropdown_has_at_least_four_options(): void
    {
        $response = $this->get(route('products.index'));

        $response->assertStatus(200);
        $response->assertSee('price_asc');
        $response->assertSee('price_desc');
        $response->assertSee('newest');
        $response->assertSee('oldest');
        $response->assertSee('rating');
    }

    // TC-08 (Happy): Sort state is preserved in the dropdown after applying
    public function test_pc004_sort_selection_persisted_in_dropdown(): void
    {
        $response = $this->get(route('products.index', ['sort' => 'price_asc']));

        $response->assertSee('selected');
        // The price_asc option should carry selected attribute
        $this->assertStringContainsString(
            'value="price_asc"',
            $response->getContent()
        );
    }

    // TC-09 (Happy): Sort works together with existing category filter
    public function test_pc004_sort_combined_with_category_filter(): void
    {
        $cat = Category::factory()->create(['name' => 'Tech']);
        Product::factory()->create(['name' => 'Expensive Tech', 'price' => 300.00, 'category_id' => $cat->id]);
        Product::factory()->create(['name' => 'Cheap Tech',     'price' => 20.00,  'category_id' => $cat->id]);
        Product::factory()->create(['name' => 'Other Item',     'price' => 1.00,   'category_id' => null]);

        $response = $this->get(route('products.index', [
            'category' => $cat->id,
            'sort'     => 'price_asc',
        ]));

        $products = $response->viewData('products');
        $this->assertEquals('Cheap Tech', $products->first()->name);
        $this->assertNotEquals('Other Item', $products->first()->name);
    }

    // TC-10 (Edge): Unknown sort value falls back to newest
    public function test_pc004_unknown_sort_value_falls_back_to_newest(): void
    {
        $old = Product::factory()->create(['name' => 'Old Product', 'created_at' => now()->subMinutes(5)]);
        $new = Product::factory()->create(['name' => 'New Product']);

        $response = $this->get(route('products.index', ['sort' => 'invalid_sort_value']));

        $products = $response->viewData('products');
        $this->assertEquals($new->id, $products->first()->id);
    }

    // TC-11 (Happy): Sort param is included in pagination links (withQueryString)
    public function test_pc004_sort_param_persists_in_pagination_links(): void
    {
        Product::factory()->count(15)->create();

        $response = $this->get(route('products.index', ['sort' => 'price_asc']));

        $response->assertStatus(200);
        $response->assertSee('sort=price_asc');
    }

    // TC-12 (Performance): Sort listing responds within 2 seconds
    public function test_pc004_sorted_listing_responds_within_two_seconds(): void
    {
        Product::factory()->count(50)->create();

        $start = microtime(true);
        $this->get(route('products.index', ['sort' => 'price_asc']));
        $elapsed = microtime(true) - $start;

        $this->assertLessThan(2.0, $elapsed, 'Sorted listing exceeded 2 seconds.');
    }
}
