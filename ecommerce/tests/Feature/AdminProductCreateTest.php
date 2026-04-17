<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * PM-001 — As an admin, I want to create a product so it appears in the storefront.
 *
 * Acceptance criteria:
 *   - Fields: name, slug (auto-gen), description, price, stock, category, images (multi-upload), status (draft/published)
 *   - Validation on all required fields
 *   - Only accessible to users with role:admin
 *   - Published products appear on storefront; draft products do not
 */
class AdminProductCreateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    }

    private function makeAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        return $admin;
    }

    private function makeUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('user');
        return $user;
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Test Product',
            'description' => 'A great product.',
            'price' => '29.99',
            'stock' => '10',
            'status' => 'published',
        ], $overrides);
    }

    // TC-01: Guest cannot access create product page — redirected to login
    public function test_pm001_guest_is_redirected_from_create_page(): void
    {
        $response = $this->get(route('admin.products.create'));

        $response->assertRedirect(route('login'));
    }

    // TC-02: Non-admin gets 403 on create product page
    public function test_pm001_non_admin_gets_403_on_create_page(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user)->get(route('admin.products.create'));

        $response->assertStatus(403);
    }

    // TC-03: Admin can access create product form (200)
    public function test_pm001_admin_can_access_create_form(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->get(route('admin.products.create'));

        $response->assertStatus(200);
    }

    // TC-04: Create form contains expected fields
    public function test_pm001_create_form_has_expected_fields(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->get(route('admin.products.create'));

        $response->assertSee('name="name"', false);
        $response->assertSee('name="description"', false);
        $response->assertSee('name="price"', false);
        $response->assertSee('name="stock"', false);
        $response->assertSee('name="status"', false);
        $response->assertSee('name="images[]"', false);
    }

    // TC-05: Admin can create a published product — redirected to products list
    public function test_pm001_admin_can_create_published_product(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->post(route('admin.products.store'), $this->validPayload());

        $response->assertRedirect(route('admin.products.index'));
        $this->assertDatabaseHas('products', [
            'name' => 'Test Product',
            'status' => 'published',
        ]);
    }

    // TC-06: Admin can create a draft product
    public function test_pm001_admin_can_create_draft_product(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->post(
            route('admin.products.store'),
            $this->validPayload(['name' => 'Draft Item', 'status' => 'draft'])
        );

        $this->assertDatabaseHas('products', ['name' => 'Draft Item', 'status' => 'draft']);
    }

    // TC-07: Slug is auto-generated from the product name
    public function test_pm001_slug_is_auto_generated_from_name(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->post(
            route('admin.products.store'),
            $this->validPayload(['name' => 'My Awesome Widget'])
        );

        $this->assertDatabaseHas('products', ['slug' => 'my-awesome-widget']);
    }

    // TC-08: Images can be uploaded and stored
    public function test_pm001_images_are_uploaded_and_stored(): void
    {
        Storage::fake('public');
        $admin = $this->makeAdmin();

        $file1 = UploadedFile::fake()->create('photo1.jpg', 50, 'image/jpeg');
        $file2 = UploadedFile::fake()->create('photo2.jpg', 50, 'image/jpeg');

        $this->actingAs($admin)->post(
            route('admin.products.store'),
            array_merge($this->validPayload(['name' => 'Imaged Product']), ['images' => [$file1, $file2]])
        );

        $product = Product::where('name', 'Imaged Product')->first();
        $this->assertNotNull($product->images);
        $this->assertCount(2, $product->images);
        foreach ($product->images as $path) {
            Storage::disk('public')->assertExists($path);
        }
    }

    // TC-09: Name is required
    public function test_pm001_name_is_required(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->postJson(
            route('admin.products.store'),
            $this->validPayload(['name' => ''])
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    // TC-10: Price must be a positive number
    public function test_pm001_price_must_be_positive(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->postJson(
            route('admin.products.store'),
            $this->validPayload(['price' => '-5'])
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['price']);
    }

    // TC-11: Stock must be a non-negative integer
    public function test_pm001_stock_must_be_non_negative(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->postJson(
            route('admin.products.store'),
            $this->validPayload(['stock' => '-1'])
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['stock']);
    }

    // TC-12: Published product appears on public storefront; draft does not
    public function test_pm001_published_product_visible_on_storefront_draft_is_not(): void
    {
        Product::factory()->create(['name' => 'Visible Product', 'status' => 'published']);
        Product::factory()->create(['name' => 'Hidden Draft', 'status' => 'draft']);

        $response = $this->get(route('products.index'));

        $response->assertStatus(200);
        $response->assertSee('Visible Product');
        $response->assertDontSee('Hidden Draft');
    }
}
