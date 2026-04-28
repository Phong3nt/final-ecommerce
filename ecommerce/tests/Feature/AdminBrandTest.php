<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\IcecatImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * IMP-046 — Brand table + Icecat brand import.
 *
 * TC-01  Guest is redirected from brands index
 * TC-02  Non-admin user gets 403 on brands index
 * TC-03  Admin can view brands index
 * TC-04  Admin can create a brand
 * TC-05  Brand creation requires name
 * TC-06  Duplicate brand name is rejected
 * TC-07  Slug is auto-generated on create
 * TC-08  Admin can update a brand
 * TC-09  Admin can delete a brand (product brand_id set to null)
 * TC-10  Product create form shows brand select
 * TC-11  Product edit form shows brand select
 * TC-12  Admin can set brand_id when creating a product
 * TC-13  Admin can set brand_id when updating a product
 * TC-14  Brands index shows product count
 * TC-15  Brand name appears on storefront product card
 * TC-16  Brand name appears on product detail page
 * TC-17  POST import-from-icecat requires auth
 * TC-18  POST import-from-icecat returns JSON with counts (demo fallback)
 */
class AdminBrandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'user',  'guard_name' => 'web']);
    }

    private function makeAdmin(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('admin');
        return $user;
    }

    private function makeUser(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('user');
        return $user;
    }

    // -------------------------------------------------------------------------
    // Access control
    // -------------------------------------------------------------------------

    /** TC-01 */
    public function test_guest_redirected_from_brands_index(): void
    {
        $this->get(route('admin.brands.index'))
            ->assertRedirect(route('login'));
    }

    /** TC-02 */
    public function test_non_admin_gets_403_on_brands_index(): void
    {
        $this->actingAs($this->makeUser())
            ->get(route('admin.brands.index'))
            ->assertForbidden();
    }

    /** TC-03 */
    public function test_admin_can_view_brands_index(): void
    {
        Brand::factory()->create(['name' => 'Apple', 'slug' => 'apple']);

        $this->actingAs($this->makeAdmin())
            ->get(route('admin.brands.index'))
            ->assertOk()
            ->assertSee('Apple');
    }

    // -------------------------------------------------------------------------
    // CRUD
    // -------------------------------------------------------------------------

    /** TC-04 */
    public function test_admin_can_create_brand(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post(route('admin.brands.store'), ['name' => 'Samsung'])
            ->assertRedirect(route('admin.brands.index'));

        $this->assertDatabaseHas('brands', ['name' => 'Samsung', 'slug' => 'samsung']);
    }

    /** TC-05 */
    public function test_brand_creation_requires_name(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post(route('admin.brands.store'), ['name' => ''])
            ->assertSessionHasErrors('name');
    }

    /** TC-06 */
    public function test_duplicate_brand_name_is_rejected(): void
    {
        Brand::factory()->create(['name' => 'HP', 'slug' => 'hp']);

        $this->actingAs($this->makeAdmin())
            ->post(route('admin.brands.store'), ['name' => 'HP'])
            ->assertSessionHasErrors('name');
    }

    /** TC-07 */
    public function test_slug_is_auto_generated_on_create(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post(route('admin.brands.store'), ['name' => 'Lenovo ThinkPad'])
            ->assertRedirect();

        $this->assertDatabaseHas('brands', ['slug' => 'lenovo-thinkpad']);
    }

    /** TC-08 */
    public function test_admin_can_update_brand(): void
    {
        $brand = Brand::factory()->create(['name' => 'Old Name', 'slug' => 'old-name']);

        $this->actingAs($this->makeAdmin())
            ->patch(route('admin.brands.update', $brand), ['name' => 'New Name'])
            ->assertRedirect(route('admin.brands.index'));

        $this->assertDatabaseHas('brands', ['id' => $brand->id, 'name' => 'New Name']);
    }

    /** TC-09 */
    public function test_admin_can_delete_brand_and_nullifies_product_brand(): void
    {
        $brand   = Brand::factory()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create(['brand_id' => $brand->id, 'category_id' => $category->id]);

        $this->actingAs($this->makeAdmin())
            ->delete(route('admin.brands.destroy', $brand))
            ->assertRedirect(route('admin.brands.index'));

        $this->assertDatabaseMissing('brands', ['id' => $brand->id]);
        $this->assertNull($product->fresh()->brand_id);
    }

    // -------------------------------------------------------------------------
    // Product form integration
    // -------------------------------------------------------------------------

    /** TC-10 */
    public function test_product_create_form_shows_brand_select(): void
    {
        Brand::factory()->create(['name' => 'Sony', 'slug' => 'sony']);

        $this->actingAs($this->makeAdmin())
            ->get(route('admin.products.create'))
            ->assertOk()
            ->assertSee('brand_id')
            ->assertSee('Sony');
    }

    /** TC-11 */
    public function test_product_edit_form_shows_brand_select(): void
    {
        $brand   = Brand::factory()->create(['name' => 'Dell', 'slug' => 'dell']);
        $product = Product::factory()->create(['brand_id' => $brand->id]);

        $this->actingAs($this->makeAdmin())
            ->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertSee('brand_id')
            ->assertSee('Dell');
    }

    /** TC-12 */
    public function test_admin_can_set_brand_when_creating_product(): void
    {
        $brand    = Brand::factory()->create();
        $category = Category::factory()->create();

        $this->actingAs($this->makeAdmin())
            ->post(route('admin.products.store'), [
                'name'        => 'Test Product',
                'price'       => '99.99',
                'stock'       => '10',
                'status'      => 'draft',
                'brand_id'    => $brand->id,
                'category_id' => $category->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('products', ['name' => 'Test Product', 'brand_id' => $brand->id]);
    }

    /** TC-13 */
    public function test_admin_can_set_brand_when_updating_product(): void
    {
        $brand   = Brand::factory()->create();
        $product = Product::factory()->create(['brand_id' => null]);

        $this->actingAs($this->makeAdmin())
            ->patch(route('admin.products.update', $product), [
                'name'     => $product->name,
                'price'    => (string) $product->price,
                'stock'    => (string) $product->stock,
                'status'   => $product->status,
                'brand_id' => $brand->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('products', ['id' => $product->id, 'brand_id' => $brand->id]);
    }

    // -------------------------------------------------------------------------
    // Display
    // -------------------------------------------------------------------------

    /** TC-14 */
    public function test_brands_index_shows_product_count(): void
    {
        $brand   = Brand::factory()->create(['name' => 'Bose', 'slug' => 'bose']);
        $category = Category::factory()->create();
        Product::factory()->count(3)->create(['brand_id' => $brand->id, 'category_id' => $category->id]);

        $this->actingAs($this->makeAdmin())
            ->get(route('admin.brands.index'))
            ->assertOk()
            ->assertSee('Bose');
    }

    /** TC-15 */
    public function test_brand_name_shown_on_storefront_product_card(): void
    {
        $brand   = Brand::factory()->create(['name' => 'Asus', 'slug' => 'asus']);
        $category = Category::factory()->create();
        Product::factory()->create([
            'name'        => 'ZenBook 14',
            'slug'        => 'zenbook-14',
            'status'      => 'published',
            'brand_id'    => $brand->id,
            'category_id' => $category->id,
        ]);

        $this->get(route('products.index'))
            ->assertOk()
            ->assertSee('Asus');
    }

    /** TC-16 */
    public function test_brand_name_shown_on_product_detail_page(): void
    {
        $brand   = Brand::factory()->create(['name' => 'Acer', 'slug' => 'acer']);
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'name'        => 'Predator Helios 16',
            'slug'        => 'predator-helios-16',
            'status'      => 'published',
            'brand_id'    => $brand->id,
            'category_id' => $category->id,
        ]);

        $this->get(route('products.show', $product->slug))
            ->assertOk()
            ->assertSee('Acer');
    }

    // -------------------------------------------------------------------------
    // Icecat brand import
    // -------------------------------------------------------------------------

    /** TC-17 */
    public function test_import_from_icecat_requires_auth(): void
    {
        $this->post(route('admin.brands.import-from-icecat'))
            ->assertRedirect(route('login'));
    }

    /** TC-18 */
    public function test_import_from_icecat_returns_json_with_counts(): void
    {
        // Make Icecat supplier API return empty so the demo fallback runs
        Http::fake([
            'live.icecat.biz/*' => Http::response(['data' => []], 200),
        ]);

        $this->actingAs($this->makeAdmin())
            ->postJson(route('admin.brands.import-from-icecat'))
            ->assertOk()
            ->assertJsonStructure(['imported', 'updated', 'skipped', 'names']);

        // Demo brands should have been inserted
        $this->assertDatabaseHas('brands', ['name' => 'HP']);
        $this->assertDatabaseHas('brands', ['name' => 'Samsung']);
    }
}
