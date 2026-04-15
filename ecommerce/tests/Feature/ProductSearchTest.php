<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'user',  'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    }

    // TC-01 (Happy): Search by name keyword returns matching product
    public function test_pc002_search_by_name_returns_matching_product(): void
    {
        Product::factory()->create(['name' => 'Blue Widget', 'description' => 'A nice widget']);
        Product::factory()->create(['name' => 'Red Gadget',  'description' => 'A red gadget']);

        $response = $this->get(route('products.search', ['q' => 'Blue']));

        $response->assertStatus(200);
        $response->assertSee('Blue Widget');
        $response->assertDontSee('Red Gadget');
    }

    // TC-02 (Happy): Search by description keyword returns matching product
    public function test_pc002_search_by_description_returns_matching_product(): void
    {
        Product::factory()->create(['name' => 'Item A', 'description' => 'contains fluffy material']);
        Product::factory()->create(['name' => 'Item B', 'description' => 'hard and solid']);

        $response = $this->get(route('products.search', ['q' => 'fluffy']));

        $response->assertStatus(200);
        $response->assertSee('Item A');
        $response->assertDontSee('Item B');
    }

    // TC-03 (Happy): Search results are paginated at 12 per page
    public function test_pc002_search_results_paginated_at_12(): void
    {
        Product::factory()->count(20)->create(['name' => 'Searchable Product']);

        $response = $this->get(route('products.search', ['q' => 'Searchable']));

        $response->assertStatus(200);
        $results = $response->viewData('results');
        $this->assertCount(12, $results->items());
    }

    // TC-04 (Happy): Search is case-insensitive
    public function test_pc002_search_is_case_insensitive(): void
    {
        Product::factory()->create(['name' => 'Organic Coffee']);

        $response = $this->get(route('products.search', ['q' => 'organic coffee']));

        $response->assertStatus(200);
        $response->assertSee('Organic Coffee');
    }

    // TC-05 (Happy): No matching results shows "no results" message
    public function test_pc002_no_matching_results_shows_no_results_message(): void
    {
        Product::factory()->create(['name' => 'Unrelated Product']);

        $response = $this->get(route('products.search', ['q' => 'xyznotexist']));

        $response->assertStatus(200);
        $response->assertSee('No products found');
    }

    // TC-06 (Edge): Empty query redirects to product listing
    public function test_pc002_empty_query_redirects_to_product_listing(): void
    {
        $response = $this->get(route('products.search', ['q' => '']));

        $response->assertRedirect(route('products.index'));
    }

    // TC-07 (Happy): Search works without login
    public function test_pc002_search_accessible_without_login(): void
    {
        $response = $this->get(route('products.search', ['q' => 'anything']));

        $response->assertStatus(200);
    }

    // TC-08 (Security): XSS in search query is escaped in output
    public function test_pc002_xss_in_search_query_is_escaped(): void
    {
        $response = $this->get(route('products.search') . '?q=' . urlencode('<script>alert(1)</script>'));

        $response->assertStatus(200);
        $response->assertDontSee('<script>alert(1)</script>', false);
    }

    // TC-09 (Happy): Search term preserved in the input field after search
    public function test_pc002_search_term_preserved_in_input_field(): void
    {
        $response = $this->get(route('products.search', ['q' => 'laptop']));

        $response->assertStatus(200);
        $response->assertSee('value="laptop"', false);
    }

    // TC-10 (Negative): Search does not return non-matching products
    public function test_pc002_search_does_not_return_non_matching_products(): void
    {
        Product::factory()->create(['name' => 'Alpha Product', 'description' => 'alpha desc']);
        Product::factory()->create(['name' => 'Beta Product',  'description' => 'beta desc']);

        $response = $this->get(route('products.search', ['q' => 'Alpha']));

        $response->assertSee('Alpha Product');
        $response->assertDontSee('Beta Product');
    }

    // TC-11 (Edge): Partial keyword match works
    public function test_pc002_partial_keyword_match_returns_results(): void
    {
        Product::factory()->create(['name' => 'Smartphone Pro Max']);

        $response = $this->get(route('products.search', ['q' => 'smart']));

        $response->assertStatus(200);
        $response->assertSee('Smartphone Pro Max');
    }

    // TC-12 (Performance): Search completes within 1 second
    public function test_pc002_search_completes_within_one_second(): void
    {
        Product::factory()->count(100)->create();

        $start = microtime(true);
        $this->get(route('products.search', ['q' => 'product']));
        $elapsed = microtime(true) - $start;

        $this->assertLessThan(1.0, $elapsed, 'Search exceeded 1 second.');
    }
}
