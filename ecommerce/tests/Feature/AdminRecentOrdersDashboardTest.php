<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * AD-004 — As an admin, I want to see recent orders on the dashboard so I can act quickly.
 *
 * Acceptance criteria:
 *   - Last 10 orders shown with status and quick-action link
 */
class AdminRecentOrdersDashboardTest extends TestCase
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

    // TC-01: Guest is redirected to login
    public function test_ad004_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('admin.dashboard'));

        $response->assertRedirect(route('login'));
    }

    // TC-02: Non-admin is forbidden
    public function test_ad004_non_admin_is_forbidden(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertStatus(403);
    }

    // TC-03: Admin sees Recent Orders section with required columns
    public function test_ad004_admin_sees_recent_orders_section(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Recent Orders');
        $response->assertSee('Order #');
        $response->assertSee('Status');
        $response->assertSee('Action');
    }

    // TC-04: Dashboard shows only last 10 orders
    public function test_ad004_dashboard_shows_only_last_10_orders(): void
    {
        $admin = $this->makeAdmin();
        $buyer = $this->makeUser();

        $orders = [];
        for ($i = 0; $i < 12; $i++) {
            $orders[] = Order::factory()->for($buyer)->create([
                'status' => 'pending',
                'created_at' => now()->subMinutes(12 - $i),
                'updated_at' => now()->subMinutes(12 - $i),
            ]);
        }

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));
        $recentOrders = $response->viewData('recentOrders');

        $this->assertCount(10, $recentOrders);
        $this->assertEquals($orders[11]->id, $recentOrders->first()->id);
        $this->assertEquals($orders[2]->id, $recentOrders->last()->id);
        $this->assertFalse($recentOrders->contains('id', $orders[0]->id));
        $this->assertFalse($recentOrders->contains('id', $orders[1]->id));
    }

    // TC-05: Orders are displayed newest first
    public function test_ad004_orders_are_sorted_newest_first(): void
    {
        $admin = $this->makeAdmin();
        $buyer = $this->makeUser();

        $oldest = Order::factory()->for($buyer)->create([
            'status' => 'pending',
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]);

        $middle = Order::factory()->for($buyer)->create([
            'status' => 'paid',
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        $newest = Order::factory()->for($buyer)->create([
            'status' => 'shipped',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));
        $recentOrders = $response->viewData('recentOrders');

        $this->assertEquals($newest->id, $recentOrders[0]->id);
        $this->assertEquals($middle->id, $recentOrders[1]->id);
        $this->assertEquals($oldest->id, $recentOrders[2]->id);
    }

    // TC-06: Status is displayed in the list
    public function test_ad004_status_is_displayed_for_each_order(): void
    {
        $admin = $this->makeAdmin();
        $buyer = $this->makeUser();

        Order::factory()->for($buyer)->create(['status' => 'pending']);
        Order::factory()->for($buyer)->create(['status' => 'shipped']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertSee('Pending');
        $response->assertSee('Shipped');
    }

    // TC-07: Quick-action link points to order detail page
    public function test_ad004_quick_action_link_points_to_order_detail(): void
    {
        $admin = $this->makeAdmin();
        $buyer = $this->makeUser();
        $order = Order::factory()->for($buyer)->create(['status' => 'processing']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertSee('View');
        $response->assertSee(route('admin.orders.show', $order), false);
    }

    // TC-08: Empty state is shown when there are no orders
    public function test_ad004_shows_empty_state_when_no_orders(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertSee('No recent orders.');
    }
}
