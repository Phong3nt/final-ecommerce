<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * PM-004 — As an admin, I want to manage product categories so products are well-organized.
 *
 * Acceptance criteria:
 *   - CRUD for categories
 *   - Hierarchical (parent/child) optional
 *   - Category filter on admin product list
 */
class AdminCategoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'user',  'guard_name' => 'web']);
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

    // TC-01: Guest is redirected from categories index → login
    public function test_pm004_guest_is_redirected_from_categories_index(): void
    {
        $response = $this->get(route('admin.categories.index'));
        $response->assertRedirect(route('login'));
    }

    // TC-02: Non-admin gets 403 on categories index
    public function test_pm004_non_admin_gets_403_on_categories_index(): void
    {
        $response = $this->actingAs($this->makeUser())
            ->get(route('admin.categories.index'));
        $response->assertStatus(403);
    }

    // TC-03: Admin can view categories index (200)
    public function test_pm004_admin_can_view_categories_index(): void
    {
        Category::factory()->create(['name' => 'Electronics']);

        $response = $this->actingAs($this->makeAdmin())
            ->get(route('admin.categories.index'));

        $response->assertStatus(200);
        $response->assertSee('Electronics');
    }

    // TC-04: Admin can create a category → redirects to index
    public function test_pm004_admin_can_create_category(): void
    {
        $response = $this->actingAs($this->makeAdmin())
            ->post(route('admin.categories.store'), ['name' => 'Books']);

        $response->assertRedirect(route('admin.categories.index'));
        $this->assertDatabaseHas('categories', ['name' => 'Books']);
    }

    // TC-05: Category name is required → 422
    public function test_pm004_category_name_is_required(): void
    {
        $response = $this->actingAs($this->makeAdmin())
            ->postJson(route('admin.categories.store'), ['name' => '']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    // TC-06: Category name must be unique → 422
    public function test_pm004_category_name_must_be_unique(): void
    {
        Category::factory()->create(['name' => 'Clothing']);

        $response = $this->actingAs($this->makeAdmin())
            ->postJson(route('admin.categories.store'), ['name' => 'Clothing']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    // TC-07: Admin can access edit form (200), pre-populated
    public function test_pm004_admin_can_access_edit_form(): void
    {
        $category = Category::factory()->create(['name' => 'Gadgets']);

        $response = $this->actingAs($this->makeAdmin())
            ->get(route('admin.categories.edit', $category));

        $response->assertStatus(200);
        $response->assertSee('Gadgets');
    }

    // TC-08: Admin can update a category name
    public function test_pm004_admin_can_update_category_name(): void
    {
        $category = Category::factory()->create(['name' => 'Old Name']);

        $this->actingAs($this->makeAdmin())
            ->patch(route('admin.categories.update', $category), ['name' => 'New Name']);

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'New Name']);
    }

    // TC-09: Admin can assign a parent category (hierarchy)
    public function test_pm004_admin_can_assign_parent_category(): void
    {
        $parent = Category::factory()->create(['name' => 'Electronics']);
        $child  = Category::factory()->create(['name' => 'Phones']);

        $this->actingAs($this->makeAdmin())
            ->patch(route('admin.categories.update', $child), [
                'name'      => 'Phones',
                'parent_id' => $parent->id,
            ]);

        $this->assertDatabaseHas('categories', ['id' => $child->id, 'parent_id' => $parent->id]);
    }

    // TC-10: Admin can delete a category → redirects to index
    public function test_pm004_admin_can_delete_category(): void
    {
        $category = Category::factory()->create(['name' => 'To Delete']);

        $response = $this->actingAs($this->makeAdmin())
            ->delete(route('admin.categories.destroy', $category));

        $response->assertRedirect(route('admin.categories.index'));
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    // TC-11: Deleting a category sets products' category_id to null
    public function test_pm004_deleting_category_nullifies_product_category(): void
    {
        $category = Category::factory()->create(['name' => 'Doomed']);
        $product  = Product::factory()->create(['category_id' => $category->id]);

        $this->actingAs($this->makeAdmin())
            ->delete(route('admin.categories.destroy', $category));

        $this->assertNull($product->fresh()->category_id);
    }

    // TC-12: Admin products index filtered by category_id shows only matching products
    public function test_pm004_admin_products_index_filtered_by_category(): void
    {
        $cat1 = Category::factory()->create(['name' => 'Alpha']);
        $cat2 = Category::factory()->create(['name' => 'Beta']);
        Product::factory()->create(['name' => 'Alpha Product', 'category_id' => $cat1->id]);
        Product::factory()->create(['name' => 'Beta Product',  'category_id' => $cat2->id]);

        $response = $this->actingAs($this->makeAdmin())
            ->get(route('admin.products.index', ['category_id' => $cat1->id]));

        $response->assertSee('Alpha Product');
        $response->assertDontSee('Beta Product');
    }
}
