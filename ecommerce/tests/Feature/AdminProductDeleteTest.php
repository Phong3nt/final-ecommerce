<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * PM-003 — As an admin, I want to delete (or archive) a product so it no longer appears in the store.
 *
 * Acceptance criteria:
 *   - Soft delete used (product hidden, not destroyed)
 *   - Confirmation modal required
 */
class AdminProductDeleteTest extends TestCase
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

    // TC-01: Guest is redirected from delete endpoint → login
    public function test_pm003_guest_is_redirected_from_delete(): void
    {
        $product = $this->makeProduct();

        $response = $this->delete(route('admin.products.destroy', $product));

        $response->assertRedirect(route('login'));
    }

    // TC-02: Non-admin gets 403 on delete
    public function test_pm003_non_admin_gets_403_on_delete(): void
    {
        $product = $this->makeProduct();

        $response = $this->actingAs($this->makeUser())
            ->delete(route('admin.products.destroy', $product));

        $response->assertStatus(403);
    }

    // TC-03: Admin can archive a product → redirects to index with success message
    public function test_pm003_admin_can_archive_product_redirects_to_index(): void
    {
        $product = $this->makeProduct();

        $response = $this->actingAs($this->makeAdmin())
            ->delete(route('admin.products.destroy', $product));

        $response->assertRedirect(route('admin.products.index'));
        $response->assertSessionHas('success');
    }

    // TC-04: Product is soft-deleted (deleted_at set, record still in DB)
    public function test_pm003_product_is_soft_deleted_not_hard_deleted(): void
    {
        $admin = $this->makeAdmin();
        $product = $this->makeProduct();
        $id = $product->id;

        $this->actingAs($admin)->delete(route('admin.products.destroy', $product));

        // Still in DB via withTrashed
        $trashed = Product::withTrashed()->find($id);
        $this->assertNotNull($trashed);
        $this->assertNotNull($trashed->deleted_at);

        // Not in normal query
        $this->assertNull(Product::find($id));
    }

    // TC-05: Soft-deleted product is hidden from storefront product listing
    public function test_pm003_archived_product_hidden_from_storefront_listing(): void
    {
        $admin = $this->makeAdmin();
        $product = $this->makeProduct(['name' => 'Doomed Product', 'status' => 'published']);

        // Visible before
        $this->get(route('products.index'))->assertSee('Doomed Product');

        $this->actingAs($admin)->delete(route('admin.products.destroy', $product));

        // Hidden after
        $this->get(route('products.index'))->assertDontSee('Doomed Product');
    }

    // TC-06: Soft-deleted product is hidden from storefront search
    public function test_pm003_archived_product_hidden_from_storefront_search(): void
    {
        $admin = $this->makeAdmin();
        $product = $this->makeProduct(['name' => 'Unique Archived Item', 'status' => 'published']);

        $this->actingAs($admin)->delete(route('admin.products.destroy', $product));

        // Search returns "no results" — product is gone from search index
        $response = $this->get(route('products.search', ['q' => 'Unique Archived Item']));
        $response->assertSee('No products found');
    }

    // TC-07: Admin product index does not show soft-deleted product
    public function test_pm003_admin_index_excludes_archived_product(): void
    {
        $admin = $this->makeAdmin();
        $product = $this->makeProduct(['name' => 'To Be Archived']);

        $this->actingAs($admin)->delete(route('admin.products.destroy', $product));

        $this->actingAs($admin)
            ->get(route('admin.products.index'))
            ->assertDontSee('To Be Archived');
    }

    // TC-08: Audit log entry is created on archive
    public function test_pm003_audit_log_entry_created_on_archive(): void
    {
        $admin = $this->makeAdmin();
        $product = $this->makeProduct(['name' => 'Logged Product']);

        $this->actingAs($admin)->delete(route('admin.products.destroy', $product));

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'product.deleted',
            'subject_type' => 'Product',
            'subject_id' => $product->id,
        ]);

        $log = AuditLog::where('subject_id', $product->id)
            ->where('action', 'product.deleted')
            ->first();
        $this->assertEquals('Logged Product', $log->old_values['name']);
        $this->assertArrayHasKey('deleted_at', $log->new_values);
    }

    // TC-09: Deleting a non-existent (already deleted) product returns 404
    public function test_pm003_deleting_already_archived_product_returns_404(): void
    {
        $admin = $this->makeAdmin();
        $product = $this->makeProduct();
        $slug = $product->slug;

        // First delete
        $this->actingAs($admin)->delete(route('admin.products.destroy', $product));

        // Second delete attempt via slug route (SoftDeletes global scope → 404)
        $response = $this->actingAs($admin)
            ->delete('/admin/products/' . $slug);

        $response->assertStatus(404);
    }

    // TC-10: Admin index view shows Archive button for each product
    public function test_pm003_admin_index_shows_archive_button(): void
    {
        $this->makeProduct(['name' => 'Archivable Product']);

        $this->actingAs($this->makeAdmin())
            ->get(route('admin.products.index'))
            ->assertSee('Archive');
    }

    // TC-11: Archive form in admin index has @csrf and @method DELETE
    public function test_pm003_delete_form_has_csrf_and_method_delete(): void
    {
        $this->makeProduct();

        $response = $this->actingAs($this->makeAdmin())
            ->get(route('admin.products.index'));

        $response->assertSee('_token', false);
        $response->assertSee('_method', false);
        $response->assertSee('DELETE', false);
    }

    // TC-12: Admin index view has data-confirm attribute for JS confirmation
    public function test_pm003_admin_index_has_confirmation_on_delete_form(): void
    {
        $this->makeProduct(['name' => 'Confirm Me']);

        $response = $this->actingAs($this->makeAdmin())
            ->get(route('admin.products.index'));

        $response->assertSee('data-confirm', false);
    }
}
