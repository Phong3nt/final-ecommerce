<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * IMP-039 — Admin Bulk Product Status Change
 *
 * Acceptance criteria:
 *   - Admin can bulk-publish, bulk-draft, or bulk-archive selected products by IDs
 *   - Admin can select all products in a category via select_all_in_category flag
 *   - Non-admins cannot access the bulk endpoint
 *   - Empty selection returns validation error
 *   - Invalid action is rejected
 */
class AdminProductBulkStatusTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $user;
    private Category $cat;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'user',  'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->user = User::factory()->create();
        $this->user->assignRole('user');

        $this->cat = Category::factory()->create(['name' => 'Electronics']);
    }

    private function makeProduct(string $status = 'draft'): Product
    {
        return Product::factory()->create([
            'status'      => $status,
            'category_id' => $this->cat->id,
        ]);
    }

    // TC-01: Admin bulk-publishes selected products by ID
    public function test_admin_can_bulk_publish_selected_products(): void
    {
        $p1 = $this->makeProduct('draft');
        $p2 = $this->makeProduct('draft');

        $response = $this->actingAs($this->admin)->post(route('admin.products.bulkStatus'), [
            'bulk_action' => 'published',
            'product_ids' => [$p1->id, $p2->id],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('products', ['id' => $p1->id, 'status' => 'published']);
        $this->assertDatabaseHas('products', ['id' => $p2->id, 'status' => 'published']);
    }

    // TC-02: Admin bulk-drafts selected products
    public function test_admin_can_bulk_set_draft(): void
    {
        $p1 = $this->makeProduct('published');
        $p2 = $this->makeProduct('published');

        $this->actingAs($this->admin)->post(route('admin.products.bulkStatus'), [
            'bulk_action' => 'draft',
            'product_ids' => [$p1->id, $p2->id],
        ]);

        $this->assertDatabaseHas('products', ['id' => $p1->id, 'status' => 'draft']);
        $this->assertDatabaseHas('products', ['id' => $p2->id, 'status' => 'draft']);
    }

    // TC-03: Admin bulk-archives (soft-deletes) selected products
    public function test_admin_can_bulk_archive_selected_products(): void
    {
        $p1 = $this->makeProduct('published');
        $p2 = $this->makeProduct('draft');

        $this->actingAs($this->admin)->post(route('admin.products.bulkStatus'), [
            'bulk_action' => 'delete',
            'product_ids' => [$p1->id, $p2->id],
        ]);

        $this->assertSoftDeleted('products', ['id' => $p1->id]);
        $this->assertSoftDeleted('products', ['id' => $p2->id]);
    }

    // TC-04: Admin selects all in category — publishes entire category
    public function test_admin_can_bulk_publish_all_in_category(): void
    {
        $p1 = $this->makeProduct('draft');
        $p2 = $this->makeProduct('draft');

        $this->actingAs($this->admin)->post(route('admin.products.bulkStatus'), [
            'bulk_action'            => 'published',
            'select_all_in_category' => '1',
            'bulk_category_id'       => $this->cat->id,
        ]);

        $this->assertDatabaseHas('products', ['id' => $p1->id, 'status' => 'published']);
        $this->assertDatabaseHas('products', ['id' => $p2->id, 'status' => 'published']);
    }

    // TC-05: Admin selects all in category — archives entire category
    public function test_admin_can_bulk_archive_all_in_category(): void
    {
        $p1 = $this->makeProduct('published');
        $p2 = $this->makeProduct('draft');

        $this->actingAs($this->admin)->post(route('admin.products.bulkStatus'), [
            'bulk_action'            => 'delete',
            'select_all_in_category' => '1',
            'bulk_category_id'       => $this->cat->id,
        ]);

        $this->assertSoftDeleted('products', ['id' => $p1->id]);
        $this->assertSoftDeleted('products', ['id' => $p2->id]);
    }

    // TC-06: Guest is redirected to login
    public function test_guest_cannot_access_bulk_endpoint(): void
    {
        $p = $this->makeProduct();

        $this->post(route('admin.products.bulkStatus'), [
            'bulk_action' => 'published',
            'product_ids' => [$p->id],
        ])->assertRedirect(route('login'));
    }

    // TC-07: Regular user gets 403
    public function test_regular_user_cannot_access_bulk_endpoint(): void
    {
        $p = $this->makeProduct();

        $this->actingAs($this->user)->post(route('admin.products.bulkStatus'), [
            'bulk_action' => 'published',
            'product_ids' => [$p->id],
        ])->assertForbidden();
    }

    // TC-08: Empty selection returns validation error
    public function test_empty_selection_returns_error(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.products.bulkStatus'), [
            'bulk_action' => 'published',
            'product_ids' => [],
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('product_ids');
    }

    // TC-09: Invalid bulk_action is rejected with 422
    public function test_invalid_bulk_action_is_rejected(): void
    {
        $p = $this->makeProduct();

        $this->actingAs($this->admin)->post(route('admin.products.bulkStatus'), [
            'bulk_action' => 'nuke',
            'product_ids' => [$p->id],
        ])->assertSessionHasErrors('bulk_action');
    }

    // TC-10: Only the selected products are changed — others are unaffected
    public function test_only_selected_products_are_changed(): void
    {
        $target   = $this->makeProduct('draft');
        $untouched = Product::factory()->create(['status' => 'draft', 'category_id' => $this->cat->id]);

        $this->actingAs($this->admin)->post(route('admin.products.bulkStatus'), [
            'bulk_action' => 'published',
            'product_ids' => [$target->id],
        ]);

        $this->assertDatabaseHas('products', ['id' => $target->id,   'status' => 'published']);
        $this->assertDatabaseHas('products', ['id' => $untouched->id, 'status' => 'draft']);
    }

    // TC-11: select_all_in_category does not affect products in other categories
    public function test_category_bulk_does_not_affect_other_categories(): void
    {
        $otherCat = Category::factory()->create(['name' => 'Laptops']);
        $inCat    = $this->makeProduct('draft');
        $outOfCat = Product::factory()->create(['status' => 'draft', 'category_id' => $otherCat->id]);

        $this->actingAs($this->admin)->post(route('admin.products.bulkStatus'), [
            'bulk_action'            => 'published',
            'select_all_in_category' => '1',
            'bulk_category_id'       => $this->cat->id,
        ]);

        $this->assertDatabaseHas('products', ['id' => $inCat->id,    'status' => 'published']);
        $this->assertDatabaseHas('products', ['id' => $outOfCat->id, 'status' => 'draft']);
    }

    // TC-12: Success flash message contains the product count
    public function test_success_message_contains_count(): void
    {
        $p1 = $this->makeProduct('draft');
        $p2 = $this->makeProduct('draft');

        $response = $this->actingAs($this->admin)->post(route('admin.products.bulkStatus'), [
            'bulk_action' => 'published',
            'product_ids' => [$p1->id, $p2->id],
        ]);

        $response->assertSessionHas('success', fn ($msg) => str_contains($msg, '2'));
    }
}
