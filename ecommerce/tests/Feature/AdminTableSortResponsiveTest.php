<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * IMP-013 — Admin tables: sortable columns + responsive layout
 *
 * Verifies that the three admin index pages (Orders, Products, Users) render
 * the IMP-013 responsive table wrapper and sortable column headers with the
 * correct data attributes, aria-sort annotations, and sort indicator icons.
 */
class AdminTableSortResponsiveTest extends TestCase
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

    // TC-01 (Happy): Orders index has IMP-013 responsive table wrapper
    public function test_imp013_orders_index_has_responsive_table_wrapper(): void
    {
        $response = $this->actingAs($this->makeAdmin())
            ->get(route('admin.orders.index'));

        $response->assertStatus(200);
        $response->assertSee('data-imp013="table-wrap"', false);
    }

    // TC-02 (Happy): Products index has IMP-013 responsive table wrapper
    public function test_imp013_products_index_has_responsive_table_wrapper(): void
    {
        $response = $this->actingAs($this->makeAdmin())
            ->get(route('admin.products.index'));

        $response->assertStatus(200);
        $response->assertSee('data-imp013="table-wrap"', false);
    }

    // TC-03 (Happy): Users index has IMP-013 responsive table wrapper
    public function test_imp013_users_index_has_responsive_table_wrapper(): void
    {
        $response = $this->actingAs($this->makeAdmin())
            ->get(route('admin.users.index'));

        $response->assertStatus(200);
        $response->assertSee('data-imp013="table-wrap"', false);
    }

    // TC-04 (Happy): Orders index has sortable-th on sort columns
    public function test_imp013_orders_index_has_sortable_th_headers(): void
    {
        $response = $this->actingAs($this->makeAdmin())
            ->get(route('admin.orders.index'));

        $response->assertSee('data-imp013="sortable-th"', false);
    }

    // TC-05 (Happy): Products index has sortable-th on sort columns
    public function test_imp013_products_index_has_sortable_th_headers(): void
    {
        $response = $this->actingAs($this->makeAdmin())
            ->get(route('admin.products.index'));

        $response->assertSee('data-imp013="sortable-th"', false);
    }

    // TC-06 (Happy): Users index has sortable-th on sort columns
    public function test_imp013_users_index_has_sortable_th_headers(): void
    {
        $response = $this->actingAs($this->makeAdmin())
            ->get(route('admin.users.index'));

        $response->assertSee('data-imp013="sortable-th"', false);
    }

    // TC-07 (Accessibility): Orders index sortable columns have aria-sort attribute
    public function test_imp013_orders_sortable_headers_have_aria_sort(): void
    {
        $response = $this->actingAs($this->makeAdmin())
            ->get(route('admin.orders.index'));

        $response->assertSee('aria-sort="', false);
    }

    // TC-08 (Accessibility): Products index sortable columns have aria-sort attribute
    public function test_imp013_products_sortable_headers_have_aria_sort(): void
    {
        $response = $this->actingAs($this->makeAdmin())
            ->get(route('admin.products.index'));

        $response->assertSee('aria-sort="none"', false);
    }

    // TC-09 (Accessibility): Users index sortable columns have aria-sort attribute
    public function test_imp013_users_sortable_headers_have_aria_sort(): void
    {
        $response = $this->actingAs($this->makeAdmin())
            ->get(route('admin.users.index'));

        $response->assertSee('aria-sort="none"', false);
    }

    // TC-10 (Happy): Orders index has server-side sort link for Total column
    public function test_imp013_orders_index_has_total_sort_link(): void
    {
        $response = $this->actingAs($this->makeAdmin())
            ->get(route('admin.orders.index'));

        $response->assertSee('total_asc', false);
    }

    // TC-11 (Happy): Orders sort=oldest yields aria-sort="ascending" on Date/ID header
    public function test_imp013_orders_sort_oldest_shows_ascending_aria_sort(): void
    {
        $response = $this->actingAs($this->makeAdmin())
            ->get(route('admin.orders.index', ['sort' => 'oldest']));

        $response->assertStatus(200);
        $response->assertSee('aria-sort="ascending"', false);
    }

    // TC-12 (Performance): Admin orders page with IMP-013 responds within 2 seconds
    public function test_imp013_admin_orders_page_responds_within_two_seconds(): void
    {
        Order::factory()->count(5)->create();

        $start    = microtime(true);
        $response = $this->actingAs($this->makeAdmin())
            ->get(route('admin.orders.index'));
        $elapsed  = microtime(true) - $start;

        $response->assertStatus(200);
        $this->assertLessThan(2.0, $elapsed, 'Admin orders page exceeded 2 seconds.');
    }
}
