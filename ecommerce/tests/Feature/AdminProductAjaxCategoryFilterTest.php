<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * IMP-040 — AJAX Category Filter for Admin Products Table
 *
 * Acceptance criteria:
 *   - GET /admin/products?_ajax=1 returns JSON with rows_html, pagination_html, total, page_ids
 *   - Adding category_id narrows the returned products
 *   - Response HTML is a partial (no <html> tag — no full layout)
 *   - Guest is redirected to login
 *   - Regular user gets 403
 *   - Pagination links do NOT include _ajax parameter
 */
class AdminProductAjaxCategoryFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $user;
    private Category $catA;
    private Category $catB;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'user',  'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->user = User::factory()->create();
        $this->user->assignRole('user');

        $this->catA = Category::factory()->create(['name' => 'Laptops']);
        $this->catB = Category::factory()->create(['name' => 'Phones']);
    }

    // TC-01: Admin gets JSON with all required keys
    public function test_admin_gets_json_with_required_keys(): void
    {
        Product::factory()->create(['category_id' => $this->catA->id]);

        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.products.index', ['_ajax' => 1]));

        $response->assertOk()
            ->assertJsonStructure(['rows_html', 'pagination_html', 'total', 'page_ids', 'category_id']);
    }

    // TC-02: No category filter returns all products in rows_html
    public function test_no_category_filter_returns_all_products(): void
    {
        Product::factory()->create(['category_id' => $this->catA->id, 'name' => 'LaptopX', 'status' => 'published']);
        Product::factory()->create(['category_id' => $this->catB->id, 'name' => 'PhoneX',  'status' => 'published']);

        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.products.index', ['_ajax' => 1]));

        $response->assertOk();
        $html = $response->json('rows_html');
        $this->assertStringContainsString('LaptopX', $html);
        $this->assertStringContainsString('PhoneX',  $html);
    }

    // TC-03: Category filter narrows rows_html to matching products only
    public function test_category_filter_narrows_products(): void
    {
        Product::factory()->create(['category_id' => $this->catA->id, 'name' => 'ThinkPad', 'status' => 'published']);
        Product::factory()->create(['category_id' => $this->catB->id, 'name' => 'Galaxy',   'status' => 'published']);

        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.products.index', ['_ajax' => 1, 'category_id' => $this->catA->id]));

        $response->assertOk();
        $html = $response->json('rows_html');
        $this->assertStringContainsString('ThinkPad', $html);
        $this->assertStringNotContainsString('Galaxy',   $html);
    }

    // TC-04: total reflects category filter count
    public function test_total_reflects_category_filter(): void
    {
        Product::factory()->count(3)->create(['category_id' => $this->catA->id]);
        Product::factory()->count(2)->create(['category_id' => $this->catB->id]);

        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.products.index', ['_ajax' => 1, 'category_id' => $this->catA->id]));

        $response->assertOk()->assertJsonPath('total', 3);
    }

    // TC-05: page_ids contains the IDs of products on the current page
    public function test_page_ids_contains_correct_ids(): void
    {
        $p = Product::factory()->create(['category_id' => $this->catA->id]);

        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.products.index', ['_ajax' => 1, 'category_id' => $this->catA->id]));

        $response->assertOk();
        $this->assertContains((string) $p->id, $response->json('page_ids'));
    }

    // TC-06: rows_html is a partial — no full HTML layout tags
    public function test_response_html_is_partial_not_full_layout(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.products.index', ['_ajax' => 1]));

        $response->assertOk();
        $html = $response->json('rows_html');
        $this->assertStringNotContainsString('<html',    $html);
        $this->assertStringNotContainsString('<!DOCTYPE', $html);
    }

    // TC-07: Empty category returns total=0 and no-products message
    public function test_empty_category_returns_no_products_message(): void
    {
        $emptyCat = Category::factory()->create(['name' => 'EmptyCat']);

        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.products.index', ['_ajax' => 1, 'category_id' => $emptyCat->id]));

        $response->assertOk()->assertJsonPath('total', 0);
        $this->assertStringContainsString('No products', $response->json('rows_html'));
    }

    // TC-08: pagination_html is present and non-null
    public function test_pagination_html_is_present(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.products.index', ['_ajax' => 1]));

        $response->assertOk();
        $this->assertNotNull($response->json('pagination_html'));
    }

    // TC-09: Guest is redirected to login (no JSON headers)
    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.products.index', ['_ajax' => 1]))
            ->assertRedirect(route('login'));
    }

    // TC-10: Regular user gets 403
    public function test_regular_user_gets_forbidden(): void
    {
        $this->actingAs($this->user)
            ->getJson(route('admin.products.index', ['_ajax' => 1]))
            ->assertForbidden();
    }

    // TC-11: Pagination links do NOT include _ajax parameter
    public function test_pagination_links_exclude_ajax_param(): void
    {
        // Create 25 products (>20 per page) so pagination renders page links
        Product::factory()->count(25)->create(['category_id' => $this->catA->id]);

        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.products.index', ['_ajax' => 1, 'category_id' => $this->catA->id]));

        $response->assertOk();
        $this->assertStringNotContainsString('_ajax', $response->json('pagination_html'));
    }

    // TC-12: Response category_id matches the filter sent
    public function test_response_includes_correct_category_id(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.products.index', ['_ajax' => 1, 'category_id' => $this->catA->id]));

        $response->assertOk()
            ->assertJsonPath('category_id', $this->catA->id);
    }
}
