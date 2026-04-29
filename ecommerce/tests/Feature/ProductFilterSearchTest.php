<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * IMP-047 — Admin/User Product Filter & Search Enhancements
 *
 * Storefront acceptance criteria:
 *   - Filter by brand returns only products from that brand
 *   - Keyword search matches product name
 *   - Keyword search matches product description
 *   - Brand filter combined with category filter
 *   - Brand filter shows in the sidebar when brands exist
 *   - Cache key includes new filter params (distinct caches per search/brand)
 *
 * Admin acceptance criteria:
 *   - AJAX search by name returns matching rows
 *   - AJAX search by SKU returns matching rows
 *   - AJAX search by description returns matching rows
 *   - AJAX brand filter narrows products
 *   - AJAX response includes brand_id and search fields
 *   - Combined category + brand + search filter
 */
class ProductFilterSearchTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Brand $brandA;
    private Brand $brandB;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'user',  'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $this->admin    = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->brandA   = Brand::factory()->create(['name' => 'Acme Corp']);
        $this->brandB   = Brand::factory()->create(['name' => 'Beta Tech']);
        $this->category = Category::factory()->create(['name' => 'Electronics']);
    }

    // ──────────────────────────────────────────────────────────────
    // STOREFRONT — brand filter
    // ──────────────────────────────────────────────────────────────

    // TC-01 (Happy): Filter by brand returns only products from that brand
    public function test_storefront_filter_by_brand_returns_matching_products(): void
    {
        Product::factory()->create(['name' => 'Acme Widget', 'brand_id' => $this->brandA->id, 'status' => 'published']);
        Product::factory()->create(['name' => 'Beta Gadget', 'brand_id' => $this->brandB->id, 'status' => 'published']);

        $response = $this->get(route('products.index', ['brand' => $this->brandA->id]));

        $response->assertStatus(200);
        $response->assertSee('Acme Widget');
        $response->assertDontSee('Beta Gadget');
    }

    // TC-02 (Happy): Filter by brand excludes products with no brand
    public function test_storefront_filter_by_brand_excludes_unbranded_products(): void
    {
        Product::factory()->create(['name' => 'Branded Product', 'brand_id' => $this->brandA->id, 'status' => 'published']);
        Product::factory()->create(['name' => 'No Brand Product', 'brand_id' => null, 'status' => 'published']);

        $response = $this->get(route('products.index', ['brand' => $this->brandA->id]));

        $response->assertSee('Branded Product');
        $response->assertDontSee('No Brand Product');
    }

    // TC-03 (Happy): Brand filter combined with category filter
    public function test_storefront_combined_brand_and_category_filter(): void
    {
        $otherCat = Category::factory()->create(['name' => 'Books']);

        Product::factory()->create([
            'name'        => 'Acme Electronics',
            'brand_id'    => $this->brandA->id,
            'category_id' => $this->category->id,
            'status'      => 'published',
        ]);
        Product::factory()->create([
            'name'        => 'Acme Book',
            'brand_id'    => $this->brandA->id,
            'category_id' => $otherCat->id,
            'status'      => 'published',
        ]);

        $response = $this->get(route('products.index', [
            'brand'    => $this->brandA->id,
            'category' => $this->category->id,
        ]));

        $response->assertSee('Acme Electronics');
        $response->assertDontSee('Acme Book');
    }

    // TC-04 (Edge): No products match brand — shows empty state
    public function test_storefront_brand_filter_empty_state(): void
    {
        $emptyBrand = Brand::factory()->create(['name' => 'Ghost Brand']);
        Product::factory()->create(['name' => 'Some Product', 'brand_id' => $this->brandA->id, 'status' => 'published']);

        $response = $this->get(route('products.index', ['brand' => $emptyBrand->id]));

        $response->assertStatus(200);
        $response->assertDontSee('Some Product');
    }

    // TC-05 (Happy): Brand select visible in sidebar when brands exist
    public function test_storefront_brand_select_visible_when_brands_exist(): void
    {
        $response = $this->get(route('products.index'));

        $response->assertStatus(200);
        $response->assertSee('name="brand"', false);
        $response->assertSee('Acme Corp');
        $response->assertSee('Beta Tech');
    }

    // ──────────────────────────────────────────────────────────────
    // STOREFRONT — keyword search
    // ──────────────────────────────────────────────────────────────

    // TC-06 (Happy): Search by name matches product
    public function test_storefront_search_by_name_returns_matching_product(): void
    {
        Product::factory()->create(['name' => 'Wireless Keyboard', 'description' => 'A keyboard', 'status' => 'published']);
        Product::factory()->create(['name' => 'USB Cable',         'description' => 'A cable',    'status' => 'published']);

        $response = $this->get(route('products.index', ['search' => 'Wireless']));

        $response->assertSee('Wireless Keyboard');
        $response->assertDontSee('USB Cable');
    }

    // TC-07 (Happy): Search by description matches product
    public function test_storefront_search_by_description_returns_matching_product(): void
    {
        Product::factory()->create(['name' => 'Widget A', 'description' => 'mechanical switches', 'status' => 'published']);
        Product::factory()->create(['name' => 'Widget B', 'description' => 'standard rubber dome', 'status' => 'published']);

        $response = $this->get(route('products.index', ['search' => 'mechanical']));

        $response->assertSee('Widget A');
        $response->assertDontSee('Widget B');
    }

    // TC-08 (Happy): Search is case-insensitive
    public function test_storefront_search_is_case_insensitive(): void
    {
        Product::factory()->create(['name' => 'Gaming Mouse', 'status' => 'published']);

        $response = $this->get(route('products.index', ['search' => 'gaming mouse']));

        $response->assertSee('Gaming Mouse');
    }

    // TC-09 (Edge): Empty search returns all published products
    public function test_storefront_empty_search_returns_all_products(): void
    {
        Product::factory()->create(['name' => 'Product One', 'status' => 'published']);
        Product::factory()->create(['name' => 'Product Two', 'status' => 'published']);

        $response = $this->get(route('products.index', ['search' => '']));

        $response->assertSee('Product One');
        $response->assertSee('Product Two');
    }

    // TC-10 (Happy): Search combined with brand filter
    public function test_storefront_search_combined_with_brand_filter(): void
    {
        Product::factory()->create([
            'name'     => 'Acme Pro Laptop',
            'brand_id' => $this->brandA->id,
            'status'   => 'published',
        ]);
        Product::factory()->create([
            'name'     => 'Beta Pro Laptop',
            'brand_id' => $this->brandB->id,
            'status'   => 'published',
        ]);

        $response = $this->get(route('products.index', [
            'search' => 'Pro Laptop',
            'brand'  => $this->brandA->id,
        ]));

        $response->assertSee('Acme Pro Laptop');
        $response->assertDontSee('Beta Pro Laptop');
    }

    // ──────────────────────────────────────────────────────────────
    // ADMIN — AJAX search
    // ──────────────────────────────────────────────────────────────

    // TC-11 (Happy): Admin AJAX search by name returns matching rows
    public function test_admin_ajax_search_by_name_returns_matching_rows(): void
    {
        Product::factory()->create(['name' => 'AlphaProduct', 'status' => 'published']);
        Product::factory()->create(['name' => 'BetaProduct',  'status' => 'published']);

        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.products.index', ['_ajax' => 1, 'search' => 'AlphaProduct']));

        $response->assertOk();
        $this->assertStringContainsString('AlphaProduct', $response->json('rows_html'));
        $this->assertStringNotContainsString('BetaProduct', $response->json('rows_html'));
    }

    // TC-12 (Happy): Admin AJAX search by SKU returns matching rows
    public function test_admin_ajax_search_by_sku_returns_matching_rows(): void
    {
        Product::factory()->create(['name' => 'SKU Product',  'sku' => 'SKU-XYZ-001', 'status' => 'published']);
        Product::factory()->create(['name' => 'Other Product', 'sku' => 'SKU-ABC-999', 'status' => 'published']);

        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.products.index', ['_ajax' => 1, 'search' => 'SKU-XYZ']));

        $response->assertOk();
        $this->assertStringContainsString('SKU Product', $response->json('rows_html'));
        $this->assertStringNotContainsString('Other Product', $response->json('rows_html'));
    }

    // TC-13 (Happy): Admin AJAX brand filter narrows products
    public function test_admin_ajax_brand_filter_narrows_products(): void
    {
        Product::factory()->create(['name' => 'Acme Device', 'brand_id' => $this->brandA->id]);
        Product::factory()->create(['name' => 'Beta Device', 'brand_id' => $this->brandB->id]);

        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.products.index', ['_ajax' => 1, 'brand_id' => $this->brandA->id]));

        $response->assertOk();
        $this->assertStringContainsString('Acme Device', $response->json('rows_html'));
        $this->assertStringNotContainsString('Beta Device', $response->json('rows_html'));
    }

    // TC-14 (Happy): Admin AJAX response includes brand_id and search fields
    public function test_admin_ajax_response_includes_brand_and_search_fields(): void
    {
        Product::factory()->create(['brand_id' => $this->brandA->id]);

        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.products.index', [
                '_ajax'     => 1,
                'brand_id'  => $this->brandA->id,
                'search'    => 'laptop',
            ]));

        $response->assertOk()
            ->assertJsonStructure(['rows_html', 'pagination_html', 'total', 'page_ids', 'category_id', 'brand_id', 'search']);

        $this->assertEquals($this->brandA->id, $response->json('brand_id'));
        $this->assertEquals('laptop', $response->json('search'));
    }

    // TC-15 (Happy): Admin AJAX combined category + brand + search filter
    public function test_admin_ajax_combined_category_brand_search_filter(): void
    {
        $otherCat = Category::factory()->create(['name' => 'Books']);

        Product::factory()->create([
            'name'        => 'Acme Smart TV',
            'brand_id'    => $this->brandA->id,
            'category_id' => $this->category->id,
            'status'      => 'published',
        ]);
        Product::factory()->create([
            'name'        => 'Acme Textbook',
            'brand_id'    => $this->brandA->id,
            'category_id' => $otherCat->id,
            'status'      => 'published',
        ]);
        Product::factory()->create([
            'name'        => 'Beta Smart TV',
            'brand_id'    => $this->brandB->id,
            'category_id' => $this->category->id,
            'status'      => 'published',
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.products.index', [
                '_ajax'       => 1,
                'category_id' => $this->category->id,
                'brand_id'    => $this->brandA->id,
                'search'      => 'Smart',
            ]));

        $response->assertOk();
        $html = $response->json('rows_html');
        $this->assertStringContainsString('Acme Smart TV',  $html);
        $this->assertStringNotContainsString('Acme Textbook', $html);
        $this->assertStringNotContainsString('Beta Smart TV', $html);
    }

    // TC-16 (Security): Guest cannot access admin AJAX search endpoint
    public function test_guest_cannot_access_admin_ajax_search(): void
    {
        $response = $this->getJson(route('admin.products.index', ['_ajax' => 1, 'search' => 'test']));
        $response->assertStatus(401);
    }

    // TC-17 (Security): Regular user cannot access admin AJAX search endpoint
    public function test_regular_user_cannot_access_admin_ajax_search(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        $response = $this->actingAs($user)
            ->getJson(route('admin.products.index', ['_ajax' => 1, 'search' => 'test']));
        $response->assertStatus(403);
    }

    // TC-18 (Edge): Admin AJAX empty search returns all products
    public function test_admin_ajax_empty_search_returns_all_products(): void
    {
        Product::factory()->create(['name' => 'Product Alpha']);
        Product::factory()->create(['name' => 'Product Beta']);

        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.products.index', ['_ajax' => 1, 'search' => '']));

        $response->assertOk();
        $html = $response->json('rows_html');
        $this->assertStringContainsString('Product Alpha', $html);
        $this->assertStringContainsString('Product Beta',  $html);
    }
}
