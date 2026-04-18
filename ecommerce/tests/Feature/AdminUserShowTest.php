<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * UM-002 — As an admin, I want to view a user's profile and order history
 *           so I can handle support requests.
 *
 * Acceptance criteria:
 *   - Read-only summary of profile + last 10 orders
 */
class AdminUserShowTest extends TestCase
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

    private function makeUser(array $overrides = []): User
    {
        $user = User::factory()->create($overrides);
        $user->assignRole('user');
        return $user;
    }

    // TC-01: Guest is redirected to login
    public function test_um002_tc01_guest_is_redirected_to_login(): void
    {
        $user = $this->makeUser();

        $this->get(route('admin.users.show', $user))
            ->assertRedirect(route('login'));
    }

    // TC-02: Non-admin gets 403
    public function test_um002_tc02_non_admin_gets_403(): void
    {
        $actor = $this->makeUser();
        $target = $this->makeUser();

        $this->actingAs($actor)
            ->get(route('admin.users.show', $target))
            ->assertForbidden();
    }

    // TC-03: Admin gets 200 for a valid user
    public function test_um002_tc03_admin_gets_200(): void
    {
        $admin = $this->makeAdmin();
        $user  = $this->makeUser();

        $this->actingAs($admin)
            ->get(route('admin.users.show', $user))
            ->assertOk();
    }

    // TC-04: Profile section shows user name
    public function test_um002_tc04_shows_user_name(): void
    {
        $admin = $this->makeAdmin();
        $user  = $this->makeUser(['name' => 'Alice Wonderland']);

        $this->actingAs($admin)
            ->get(route('admin.users.show', $user))
            ->assertOk()
            ->assertSee('Alice Wonderland');
    }

    // TC-05: Profile section shows user email
    public function test_um002_tc05_shows_user_email(): void
    {
        $admin = $this->makeAdmin();
        $user  = $this->makeUser(['email' => 'alice.um002@example.test']);

        $this->actingAs($admin)
            ->get(route('admin.users.show', $user))
            ->assertOk()
            ->assertSee('alice.um002@example.test');
    }

    // TC-06: Profile section shows user role badge
    public function test_um002_tc06_shows_user_role(): void
    {
        $admin = $this->makeAdmin();
        $user  = $this->makeUser();

        $this->actingAs($admin)
            ->get(route('admin.users.show', $user))
            ->assertOk()
            ->assertSee('user');
    }

    // TC-07: Profile section shows registration date
    public function test_um002_tc07_shows_registration_date(): void
    {
        $admin = $this->makeAdmin();
        $user  = $this->makeUser();

        $this->actingAs($admin)
            ->get(route('admin.users.show', $user))
            ->assertOk()
            ->assertSee($user->created_at->format('d M Y'));
    }

    // TC-08: Page contains the "Order History" heading
    public function test_um002_tc08_shows_order_history_section(): void
    {
        $admin = $this->makeAdmin();
        $user  = $this->makeUser();

        $this->actingAs($admin)
            ->get(route('admin.users.show', $user))
            ->assertOk()
            ->assertSee('Order History');
    }

    // TC-09: Only last 10 orders shown when user has more than 10
    public function test_um002_tc09_shows_at_most_10_orders(): void
    {
        $admin = $this->makeAdmin();
        $user  = $this->makeUser();

        // Create 12 orders; the oldest gets a distinct status we can count
        Order::factory()->count(12)->create(['user_id' => $user->id, 'status' => 'delivered']);

        $response = $this->actingAs($admin)
            ->get(route('admin.users.show', $user))
            ->assertOk();

        // Count occurrences of '#' followed by order IDs in the rendered HTML — rough proxy
        $orderIds = $user->orders()->latest()->take(10)->pluck('id');
        foreach ($orderIds as $id) {
            $response->assertSee('#' . $id);
        }

        // The 11th oldest order (not in last 10) should not appear
        $oldestId = $user->orders()->oldest()->value('id');
        if (! $orderIds->contains($oldestId)) {
            $response->assertDontSee('#' . $oldestId);
        }
    }

    // TC-10: Order status is visible in the order history table
    public function test_um002_tc10_shows_order_status(): void
    {
        $admin = $this->makeAdmin();
        $user  = $this->makeUser();
        Order::factory()->create(['user_id' => $user->id, 'status' => 'shipped']);

        $this->actingAs($admin)
            ->get(route('admin.users.show', $user))
            ->assertOk()
            ->assertSee('shipped');
    }

    // TC-11: Order total is visible in the order history table
    public function test_um002_tc11_shows_order_total(): void
    {
        $admin = $this->makeAdmin();
        $user  = $this->makeUser();
        Order::factory()->create(['user_id' => $user->id, 'total' => 123.45]);

        $this->actingAs($admin)
            ->get(route('admin.users.show', $user))
            ->assertOk()
            ->assertSee('123.45');
    }

    // TC-12: Order date is visible in the order history table
    public function test_um002_tc12_shows_order_date(): void
    {
        $admin = $this->makeAdmin();
        $user  = $this->makeUser();
        $order = Order::factory()->create(['user_id' => $user->id]);

        $this->actingAs($admin)
            ->get(route('admin.users.show', $user))
            ->assertOk()
            ->assertSee($order->created_at->format('d M Y'));
    }

    // TC-13: User with no orders shows empty state message
    public function test_um002_tc13_no_orders_shows_empty_state(): void
    {
        $admin = $this->makeAdmin();
        $user  = $this->makeUser();

        $this->actingAs($admin)
            ->get(route('admin.users.show', $user))
            ->assertOk()
            ->assertSee('No orders found');
    }

    // TC-14: Non-existent user returns 404
    public function test_um002_tc14_nonexistent_user_returns_404(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->get(route('admin.users.show', 99999))
            ->assertNotFound();
    }

    // TC-15: Page responds within 2 seconds
    public function test_um002_tc15_responds_within_2_seconds(): void
    {
        $admin = $this->makeAdmin();
        $user  = $this->makeUser();
        Order::factory()->count(10)->create(['user_id' => $user->id]);

        $start = microtime(true);

        $this->actingAs($admin)
            ->get(route('admin.users.show', $user))
            ->assertOk();

        $this->assertLessThan(2.0, microtime(true) - $start, 'Page took more than 2 seconds');
    }
}
