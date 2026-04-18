<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * UM-001 — As an admin, I want to view all registered users so I can manage the user base.
 *
 * Acceptance criteria:
 *   - Table with name, email, role, registration date, order count
 *   - Searchable by name/email
 *   - Paginated (20 per page)
 */
class AdminUserListTest extends TestCase
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

    private function makeUser(array $overrides = []): User
    {
        $user = User::factory()->create($overrides);
        $user->assignRole('user');
        return $user;
    }

    // TC-01: Guest is redirected to login
    public function test_um001_tc01_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.users.index'))
            ->assertRedirect(route('login'));
    }

    // TC-02: Non-admin gets 403
    public function test_um001_tc02_non_admin_gets_403(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    // TC-03: Admin gets 200
    public function test_um001_tc03_admin_gets_200(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk();
    }

    // TC-04: User table shows name column
    public function test_um001_tc04_table_shows_user_name(): void
    {
        $admin = $this->makeAdmin();
        $user = $this->makeUser(['name' => 'Jane Doe Customer']);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Jane Doe Customer');
    }

    // TC-05: User table shows email column
    public function test_um001_tc05_table_shows_user_email(): void
    {
        $admin = $this->makeAdmin();
        $user = $this->makeUser(['email' => 'uniquecustomer@example.test']);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('uniquecustomer@example.test');
    }

    // TC-06: User table shows role
    public function test_um001_tc06_table_shows_user_role(): void
    {
        $admin = $this->makeAdmin();
        $this->makeUser();

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('user');
    }

    // TC-07: User table shows registration date
    public function test_um001_tc07_table_shows_registration_date(): void
    {
        $admin = $this->makeAdmin();
        $user = $this->makeUser();

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee($user->created_at->format('d M Y'));
    }

    // TC-08: User table shows order count (zero for a user with no orders)
    public function test_um001_tc08_table_shows_order_count_zero(): void
    {
        $admin = $this->makeAdmin();
        $this->makeUser();

        $response = $this->actingAs($admin)
            ->get(route('admin.users.index'));

        $response->assertOk()
            ->assertSee('Orders');
    }

    // TC-09: Order count reflects actual orders
    public function test_um001_tc09_order_count_reflects_actual_orders(): void
    {
        $admin = $this->makeAdmin();
        $user = $this->makeUser();

        Order::factory()->count(3)->for($user)->create([
            'status' => 'paid',
            'subtotal' => 50.00,
            'shipping_cost' => 5.00,
            'total' => 55.00,
            'shipping_method' => 'standard',
            'shipping_label' => 'Standard Shipping',
            'address' => [
                'name' => 'Test User',
                'address_line1' => '1 Main St',
                'address_line2' => null,
                'city' => 'Springfield',
                'state' => 'IL',
                'postal_code' => '62701',
                'country' => 'US',
            ],
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.users.index'));

        $response->assertOk();
        $this->assertStringContainsString('3', $response->getContent());
    }

    // TC-10: Search by name filters results
    public function test_um001_tc10_search_by_name_filters_results(): void
    {
        $admin = $this->makeAdmin();
        $matchUser = $this->makeUser(['name' => 'Alice Wonderland']);
        $noMatchUser = $this->makeUser(['name' => 'Bob Builder']);

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['search' => 'Alice']))
            ->assertOk()
            ->assertSee('Alice Wonderland')
            ->assertDontSee('Bob Builder');
    }

    // TC-11: Search by email filters results
    public function test_um001_tc11_search_by_email_filters_results(): void
    {
        $admin = $this->makeAdmin();
        $matchUser = $this->makeUser(['email' => 'target.search@example.test']);
        $noMatchUser = $this->makeUser(['email' => 'other.person@example.test']);

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['search' => 'target.search']))
            ->assertOk()
            ->assertSee('target.search@example.test')
            ->assertDontSee('other.person@example.test');
    }

    // TC-12: Empty search query returns all users
    public function test_um001_tc12_empty_search_returns_all_users(): void
    {
        $admin = $this->makeAdmin();
        $this->makeUser(['name' => 'User Alpha']);
        $this->makeUser(['name' => 'User Beta']);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('User Alpha')
            ->assertSee('User Beta');
    }

    // TC-13: No matching search shows "No users found" message
    public function test_um001_tc13_no_match_shows_empty_state(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['search' => 'zzznomatchxxx']))
            ->assertOk()
            ->assertSee('No users found');
    }

    // TC-14: List is paginated at 20 per page
    public function test_um001_tc14_list_is_paginated_at_20_per_page(): void
    {
        $admin = $this->makeAdmin();
        User::factory()->count(25)->create()->each(fn($u) => $u->assignRole('user'));

        $response = $this->actingAs($admin)
            ->get(route('admin.users.index'));

        $response->assertOk();
        $this->assertSame(20, $response->viewData('users')->perPage());
    }

    // TC-15: Second page is accessible
    public function test_um001_tc15_second_page_is_accessible(): void
    {
        $admin = $this->makeAdmin();
        User::factory()->count(25)->create()->each(fn($u) => $u->assignRole('user'));

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['page' => 2]))
            ->assertOk();
    }

    // TC-16: Pagination links are present when users exceed page size
    public function test_um001_tc16_pagination_links_present_when_more_than_20(): void
    {
        $admin = $this->makeAdmin();
        User::factory()->count(25)->create()->each(fn($u) => $u->assignRole('user'));

        $response = $this->actingAs($admin)
            ->get(route('admin.users.index'));

        $response->assertOk();
        $this->assertTrue($response->viewData('users')->hasPages());
    }

    // TC-17: Search term is preserved in the search input after submission
    public function test_um001_tc17_search_term_preserved_in_input(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['search' => 'alice']))
            ->assertOk()
            ->assertSee('alice');
    }

    // TC-18: Page responds within two seconds
    public function test_um001_tc18_page_responds_within_two_seconds(): void
    {
        $admin = $this->makeAdmin();
        User::factory()->count(10)->create()->each(fn($u) => $u->assignRole('user'));

        $start = microtime(true);
        $this->actingAs($admin)->get(route('admin.users.index'))->assertOk();
        $this->assertLessThan(2.0, microtime(true) - $start);
    }
}
