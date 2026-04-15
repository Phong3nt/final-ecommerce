<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminMiddlewareAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'user',  'guard_name' => 'web']);
    }

    private function makeAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');
        return $user;
    }

    private function makeUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('user');
        return $user;
    }

    /** Collect every route whose URI begins with "admin/" */
    private function adminRoutes(): array
    {
        return collect(Route::getRoutes())
            ->filter(fn ($r) => str_starts_with($r->uri(), 'admin/'))
            ->values()
            ->all();
    }

    // ---------------------------------------------------------------
    // TC-01  Kernel — Spatie `role` alias is registered
    // ---------------------------------------------------------------

    /** @test */
    public function nf005_role_middleware_alias_is_registered_in_kernel(): void
    {
        $aliases = app('router')->getMiddleware();

        $this->assertArrayHasKey('role', $aliases);
        $this->assertEquals(RoleMiddleware::class, $aliases['role']);
    }

    // ---------------------------------------------------------------
    // TC-02  Kernel — Spatie `permission` alias is registered
    // ---------------------------------------------------------------

    /** @test */
    public function nf005_permission_middleware_alias_is_registered_in_kernel(): void
    {
        $aliases = app('router')->getMiddleware();

        $this->assertArrayHasKey('permission', $aliases);
        $this->assertEquals(PermissionMiddleware::class, $aliases['permission']);
    }

    // ---------------------------------------------------------------
    // TC-03  Kernel — Spatie `role_or_permission` alias is registered
    // ---------------------------------------------------------------

    /** @test */
    public function nf005_role_or_permission_middleware_alias_is_registered_in_kernel(): void
    {
        $aliases = app('router')->getMiddleware();

        $this->assertArrayHasKey('role_or_permission', $aliases);
        $this->assertEquals(RoleOrPermissionMiddleware::class, $aliases['role_or_permission']);
    }

    // ---------------------------------------------------------------
    // TC-04  Route audit — at least one admin route exists
    // ---------------------------------------------------------------

    /** @test */
    public function nf005_at_least_one_admin_route_is_registered(): void
    {
        $this->assertNotEmpty($this->adminRoutes(), 'No routes found under admin/ prefix.');
    }

    // ---------------------------------------------------------------
    // TC-05  Route audit — every admin route has `auth` middleware
    // ---------------------------------------------------------------

    /** @test */
    public function nf005_every_admin_route_has_auth_middleware(): void
    {
        foreach ($this->adminRoutes() as $route) {
            $middleware = $route->gatherMiddleware();
            $this->assertContains(
                'auth',
                $middleware,
                "Route [{$route->getName()}] is missing `auth` middleware."
            );
        }
    }

    // ---------------------------------------------------------------
    // TC-06  Route audit — every admin route has `role:admin` middleware
    // ---------------------------------------------------------------

    /** @test */
    public function nf005_every_admin_route_has_role_admin_middleware(): void
    {
        foreach ($this->adminRoutes() as $route) {
            $middleware = $route->gatherMiddleware();
            $this->assertContains(
                'role:admin',
                $middleware,
                "Route [{$route->getName()}] is missing `role:admin` middleware."
            );
        }
    }

    // ---------------------------------------------------------------
    // TC-07  HTTP — admin can access admin.dashboard (200)
    // ---------------------------------------------------------------

    /** @test */
    public function nf005_admin_can_access_admin_dashboard(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get(route('admin.dashboard'))
            ->assertStatus(200);
    }

    // ---------------------------------------------------------------
    // TC-08  HTTP — regular user is blocked from admin.dashboard (403)
    // ---------------------------------------------------------------

    /** @test */
    public function nf005_regular_user_is_blocked_from_admin_dashboard_with_403(): void
    {
        $this->actingAs($this->makeUser())
            ->get(route('admin.dashboard'))
            ->assertStatus(403);
    }

    // ---------------------------------------------------------------
    // TC-09  HTTP — guest is redirected to login for admin.dashboard
    // ---------------------------------------------------------------

    /** @test */
    public function nf005_guest_is_redirected_to_login_for_admin_dashboard(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('login'));
    }

    // ---------------------------------------------------------------
    // TC-10  HTTP — user with NO role is blocked from admin.dashboard (403)
    // ---------------------------------------------------------------

    /** @test */
    public function nf005_user_with_no_role_is_blocked_from_admin_dashboard(): void
    {
        $noRole = User::factory()->create();

        $this->actingAs($noRole)
            ->get(route('admin.dashboard'))
            ->assertStatus(403);
    }

    // ---------------------------------------------------------------
    // TC-11  Route audit — admin routes are all under `admin.` name prefix
    // ---------------------------------------------------------------

    /** @test */
    public function nf005_all_admin_routes_use_admin_name_prefix(): void
    {
        foreach ($this->adminRoutes() as $route) {
            $this->assertStringStartsWith(
                'admin.',
                (string) $route->getName(),
                "Admin route URI [{$route->uri()}] does not have the `admin.` name prefix."
            );
        }
    }

    // ---------------------------------------------------------------
    // TC-12  Performance — admin middleware check responds within 1s
    // ---------------------------------------------------------------

    /** @test */
    public function nf005_admin_dashboard_access_check_completes_within_one_second(): void
    {
        $start = microtime(true);

        $this->actingAs($this->makeAdmin())
            ->get(route('admin.dashboard'));

        $this->assertLessThan(1.0, microtime(true) - $start);
    }
}
