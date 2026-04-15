<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleAccessControlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    }

    private function makeUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('user');
        return $user;
    }

    private function makeAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        return $admin;
    }

    // -------------------------------------------------------
    // TC-AU006-01 | HAPPY — admin can access /admin/dashboard
    // -------------------------------------------------------
    public function test_AU006_adminUser_canAccessAdminDashboard(): void
    {
        $response = $this->actingAs($this->makeAdmin())->get('/admin/dashboard');
        $response->assertStatus(200);
    }

    // -------------------------------------------------------
    // TC-AU006-02 | SECURITY — regular user is blocked with 403
    // -------------------------------------------------------
    public function test_AU006_regularUser_receives403OnAdminRoute(): void
    {
        $response = $this->actingAs($this->makeUser())->get('/admin/dashboard');
        $response->assertStatus(403);
    }

    // -------------------------------------------------------
    // TC-AU006-03 | SECURITY — guest is redirected to login (auth middleware first)
    // -------------------------------------------------------
    public function test_AU006_guest_isRedirectedToLoginForAdminRoute(): void
    {
        $response = $this->get('/admin/dashboard');
        $response->assertRedirect('/login');
    }

    // -------------------------------------------------------
    // TC-AU006-04 | HAPPY — admin has 'admin' role
    // -------------------------------------------------------
    public function test_AU006_adminUser_hasAdminRole(): void
    {
        $admin = $this->makeAdmin();
        $this->assertTrue($admin->hasRole('admin'));
    }

    // -------------------------------------------------------
    // TC-AU006-05 | HAPPY — regular user has 'user' role, not 'admin'
    // -------------------------------------------------------
    public function test_AU006_regularUser_doesNotHaveAdminRole(): void
    {
        $user = $this->makeUser();
        $this->assertFalse($user->hasRole('admin'));
        $this->assertTrue($user->hasRole('user'));
    }

    // -------------------------------------------------------
    // TC-AU006-06 | EDGE — user dashboard still accessible to regular user
    // -------------------------------------------------------
    public function test_AU006_regularUser_canStillAccessUserDashboard(): void
    {
        $response = $this->actingAs($this->makeUser())->get('/dashboard');
        $response->assertStatus(200);
    }

    // -------------------------------------------------------
    // TC-AU006-07 | EDGE — admin can also access user dashboard
    // -------------------------------------------------------
    public function test_AU006_adminUser_canAlsoAccessUserDashboard(): void
    {
        $response = $this->actingAs($this->makeAdmin())->get('/dashboard');
        $response->assertStatus(200);
    }

    // -------------------------------------------------------
    // TC-AU006-08 | SECURITY — user with no role is blocked from admin (403)
    // -------------------------------------------------------
    public function test_AU006_userWithNoRole_isBlockedFromAdminRoute(): void
    {
        $noRole = User::factory()->create(); // no role assigned
        $response = $this->actingAs($noRole)->get('/admin/dashboard');
        $response->assertStatus(403);
    }

    // -------------------------------------------------------
    // TC-AU006-09 | EDGE — both roles exist in database after seeder
    // -------------------------------------------------------
    public function test_AU006_rolesExistInDatabase(): void
    {
        $this->assertDatabaseHas('roles', ['name' => 'user', 'guard_name' => 'web']);
        $this->assertDatabaseHas('roles', ['name' => 'admin', 'guard_name' => 'web']);
    }

    // -------------------------------------------------------
    // TC-AU006-10 | SECURITY — role middleware is registered in Kernel
    // -------------------------------------------------------
    public function test_AU006_roleMiddlewareIsRegistered(): void
    {
        $aliases = app(\Illuminate\Contracts\Http\Kernel::class)->getMiddlewareAliases();
        $this->assertArrayHasKey('role', $aliases);
        $this->assertEquals(\Spatie\Permission\Middleware\RoleMiddleware::class, $aliases['role']);
    }

    // -------------------------------------------------------
    // TC-AU006-11 | EDGE — admin route name resolves correctly
    // -------------------------------------------------------
    public function test_AU006_adminDashboardRouteNameResolves(): void
    {
        $this->assertEquals('/admin/dashboard', route('admin.dashboard', [], false));
    }

    // -------------------------------------------------------
    // TC-AU006-12 | PERFORMANCE — admin dashboard responds within 2 seconds
    // -------------------------------------------------------
    public function test_AU006_adminDashboard_respondsWithinTwoSeconds(): void
    {
        $admin = $this->makeAdmin();
        $start = microtime(true);
        $this->actingAs($admin)->get('/admin/dashboard');
        $elapsed = microtime(true) - $start;
        $this->assertLessThan(2.0, $elapsed, 'Admin dashboard took longer than 2 seconds');
    }
}
