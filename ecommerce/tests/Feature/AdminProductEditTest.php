<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * PM-002 — As an admin, I want to edit a product so I can update its details or pricing.
 *
 * Acceptance criteria:
 *   - All fields editable
 *   - Changes reflected on storefront immediately
 *   - Audit log entry created
 */
class AdminProductEditTest extends TestCase
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

    private function makeProduct(array $attrs = []): Product
    {
        return Product::factory()->create(array_merge(['status' => 'published'], $attrs));
    }

    private function validPayload(Product $product, array $overrides = []): array
    {
        return array_merge([
            'name' => $product->name,
            'description' => $product->description,
            'price' => $product->price,
            'stock' => $product->stock,
            'status' => $product->status,
        ], $overrides);
    }

    // TC-01: Guest is redirected from edit page → login
    public function test_pm002_guest_is_redirected_from_edit_page(): void
    {
        $product = $this->makeProduct();

        $response = $this->get(route('admin.products.edit', $product));

        $response->assertRedirect(route('login'));
    }

    // TC-02: Non-admin gets 403 on edit page
    public function test_pm002_non_admin_gets_403_on_edit_page(): void
    {
        $product = $this->makeProduct();

        $response = $this->actingAs($this->makeUser())
            ->get(route('admin.products.edit', $product));

        $response->assertStatus(403);
    }

    // TC-03: Admin can access edit form (200) and it is pre-populated
    public function test_pm002_admin_can_access_edit_form_pre_populated(): void
    {
        $product = $this->makeProduct(['name' => 'Original Name', 'price' => '19.99']);

        $response = $this->actingAs($this->makeAdmin())
            ->get(route('admin.products.edit', $product));

        $response->assertStatus(200);
        $response->assertSee('Original Name');
        $response->assertSee('19.99');
    }

    // TC-04: Admin can update name and price
    public function test_pm002_admin_can_update_product_name_and_price(): void
    {
        $admin = $this->makeAdmin();
        $product = $this->makeProduct(['name' => 'Old Name', 'price' => '10.00']);

        $this->actingAs($admin)->patch(
            route('admin.products.update', $product),
            $this->validPayload($product, ['name' => 'New Name', 'price' => '49.99'])
        );

        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'New Name', 'price' => '49.99']);
    }

    // TC-05: All fields can be updated (description, stock, status, category)
    public function test_pm002_all_fields_are_editable(): void
    {
        $admin = $this->makeAdmin();
        $category = Category::factory()->create();
        $product = $this->makeProduct(['description' => 'Old desc', 'stock' => 5, 'status' => 'published']);

        $this->actingAs($admin)->patch(
            route('admin.products.update', $product),
            $this->validPayload($product, [
                'description' => 'Updated description',
                'stock' => 99,
                'status' => 'draft',
                'category_id' => $category->id,
            ])
        );

        $product->refresh();
        $this->assertEquals('Updated description', $product->description);
        $this->assertEquals(99, $product->stock);
        $this->assertEquals('draft', $product->status);
        $this->assertEquals($category->id, $product->category_id);
    }

    // TC-06: Name change auto-updates slug
    public function test_pm002_name_change_auto_updates_slug(): void
    {
        $admin = $this->makeAdmin();
        $product = $this->makeProduct(['name' => 'Original Title']);

        $this->actingAs($admin)->patch(
            route('admin.products.update', $product),
            $this->validPayload($product, ['name' => 'Completely New Title'])
        );

        $this->assertDatabaseHas('products', ['id' => $product->id, 'slug' => 'completely-new-title']);
    }

    // TC-07: Changing to published → product immediately visible on storefront
    public function test_pm002_publishing_draft_makes_product_visible_on_storefront(): void
    {
        $admin = $this->makeAdmin();
        $product = $this->makeProduct(['name' => 'Was Draft', 'status' => 'draft']);

        // Not visible before
        $this->get(route('products.index'))->assertDontSee('Was Draft');

        $this->actingAs($admin)->patch(
            route('admin.products.update', $product),
            $this->validPayload($product, ['status' => 'published'])
        );

        // Visible after
        $this->get(route('products.index'))->assertSee('Was Draft');
    }

    // TC-08: Changing to draft → product immediately hidden from storefront
    public function test_pm002_drafting_published_hides_product_on_storefront(): void
    {
        $admin = $this->makeAdmin();
        $product = $this->makeProduct(['name' => 'Visible Product', 'status' => 'published']);

        // Visible before
        $this->get(route('products.index'))->assertSee('Visible Product');

        $this->actingAs($admin)->patch(
            route('admin.products.update', $product),
            $this->validPayload($product, ['status' => 'draft'])
        );

        // Hidden after
        $this->get(route('products.index'))->assertDontSee('Visible Product');
    }

    // TC-09: An audit log entry is created with old + new values
    public function test_pm002_audit_log_entry_is_created_on_update(): void
    {
        $admin = $this->makeAdmin();
        $product = $this->makeProduct(['name' => 'Before', 'price' => '10.00']);

        $this->actingAs($admin)->patch(
            route('admin.products.update', $product),
            $this->validPayload($product, ['name' => 'After', 'price' => '20.00'])
        );

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'product.updated',
            'subject_type' => 'Product',
            'subject_id' => $product->id,
        ]);

        $log = AuditLog::where('subject_id', $product->id)->first();
        $this->assertEquals('Before', $log->old_values['name']);
        $this->assertEquals('After', $log->new_values['name']);
    }

    // TC-10: Name is required — 422 validation error
    public function test_pm002_name_is_required(): void
    {
        $admin = $this->makeAdmin();
        $product = $this->makeProduct();

        $response = $this->actingAs($admin)->patchJson(
            route('admin.products.update', $product),
            $this->validPayload($product, ['name' => ''])
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    // TC-11: Price must be positive — 422 validation error
    public function test_pm002_price_must_be_positive(): void
    {
        $admin = $this->makeAdmin();
        $product = $this->makeProduct();

        $response = $this->actingAs($admin)->patchJson(
            route('admin.products.update', $product),
            $this->validPayload($product, ['price' => '-1'])
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['price']);
    }

    // TC-12: New images are appended and stored; success redirect to index
    public function test_pm002_new_images_are_appended_and_stored(): void
    {
        Storage::fake('public');
        $admin = $this->makeAdmin();
        $product = $this->makeProduct(['images' => null]);

        $file = UploadedFile::fake()->create('new_photo.jpg', 50, 'image/jpeg');

        $response = $this->actingAs($admin)->patch(
            route('admin.products.update', $product),
            array_merge($this->validPayload($product), ['images' => [$file]])
        );

        $response->assertRedirect(route('admin.products.index'));
        $product->refresh();
        $this->assertCount(1, $product->images);
        Storage::disk('public')->assertExists($product->images[0]);
    }
}
