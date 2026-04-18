<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * UM-004 — As an admin, I want to assign or change user roles
 *           so I can promote users to admins.
 *
 * Acceptance criteria:
 *   - Role dropdown: user / admin
 *   - Audit log records who changed the role and when
 */
class AdminUserAssignRoleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'user',  'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    }

    private function makeAdmin(array $overrides = []): User
    {
        $admin = User::factory()->create(array_merge(['is_active' => true], $overrides));
        $admin->assignRole('admin');
        return $admin;
    }

    private function makeUser(array $overrides = []): User
    {
        $user = User::factory()->create(array_merge(['is_active' => true], $overrides));
        $user->assignRole('user');
        return $user;
    }

    // TC-01: Guest is redirected to login on assign-role endpoint
    public function test_um004_tc01_guest_redirected_to_login(): void
    {
        $user = $this->makeUser();

        $this->patch(route('admin.users.assign-role', $user), ['role' => 'admin'])
            ->assertRedirect(route('login'));
    }

    // TC-02: Non-admin gets 403 on assign-role
    public function test_um004_tc02_non_admin_gets_403(): void
    {
        $actor  = $this->makeUser();
        $target = $this->makeUser();

        $this->actingAs($actor)
            ->patch(route('admin.users.assign-role', $target), ['role' => 'admin'])
            ->assertForbidden();
    }

    // TC-03: Admin can assign 'admin' role to a user
    public function test_um004_tc03_admin_can_assign_admin_role(): void
    {
        $admin  = $this->makeAdmin();
        $target = $this->makeUser();

        $this->actingAs($admin)
            ->patch(route('admin.users.assign-role', $target), ['role' => 'admin'])
            ->assertRedirect(route('admin.users.show', $target));

        $this->assertTrue($target->fresh()->hasRole('admin'));
        $this->assertFalse($target->fresh()->hasRole('user'));
    }

    // TC-04: Admin can assign 'user' role to an admin
    public function test_um004_tc04_admin_can_assign_user_role(): void
    {
        $admin  = $this->makeAdmin();
        $target = $this->makeAdmin();

        $this->actingAs($admin)
            ->patch(route('admin.users.assign-role', $target), ['role' => 'user'])
            ->assertRedirect(route('admin.users.show', $target));

        $this->assertTrue($target->fresh()->hasRole('user'));
        $this->assertFalse($target->fresh()->hasRole('admin'));
    }

    // TC-05: Invalid role value is rejected with 422
    public function test_um004_tc05_invalid_role_is_rejected(): void
    {
        $admin  = $this->makeAdmin();
        $target = $this->makeUser();

        $this->actingAs($admin)
            ->patch(route('admin.users.assign-role', $target), ['role' => 'superuser'])
            ->assertSessionHasErrors('role');
    }

    // TC-06: Missing role value is rejected with validation error
    public function test_um004_tc06_missing_role_is_rejected(): void
    {
        $admin  = $this->makeAdmin();
        $target = $this->makeUser();

        $this->actingAs($admin)
            ->patch(route('admin.users.assign-role', $target), [])
            ->assertSessionHasErrors('role');
    }

    // TC-07: Audit log is created on role change
    public function test_um004_tc07_audit_log_created_on_role_change(): void
    {
        $admin  = $this->makeAdmin();
        $target = $this->makeUser();

        $this->actingAs($admin)
            ->patch(route('admin.users.assign-role', $target), ['role' => 'admin']);

        $this->assertDatabaseHas('audit_logs', [
            'user_id'      => $admin->id,
            'action'       => 'user.role_changed',
            'subject_type' => 'User',
            'subject_id'   => $target->id,
        ]);
    }

    // TC-08: Audit log stores old role in old_values
    public function test_um004_tc08_audit_log_stores_old_role(): void
    {
        $admin  = $this->makeAdmin();
        $target = $this->makeUser(); // current role: user

        $this->actingAs($admin)
            ->patch(route('admin.users.assign-role', $target), ['role' => 'admin']);

        $log = AuditLog::where('action', 'user.role_changed')
            ->where('subject_id', $target->id)
            ->latest()
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('user', $log->old_values['role']);
    }

    // TC-09: Audit log stores new role in new_values
    public function test_um004_tc09_audit_log_stores_new_role(): void
    {
        $admin  = $this->makeAdmin();
        $target = $this->makeUser();

        $this->actingAs($admin)
            ->patch(route('admin.users.assign-role', $target), ['role' => 'admin']);

        $log = AuditLog::where('action', 'user.role_changed')
            ->where('subject_id', $target->id)
            ->latest()
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('admin', $log->new_values['role']);
    }

    // TC-10: Admin cannot change their own role (self-protection)
    public function test_um004_tc10_admin_cannot_change_own_role(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->patch(route('admin.users.assign-role', $admin), ['role' => 'user'])
            ->assertRedirect(route('admin.users.show', $admin));

        // Role must remain admin (unchanged)
        $this->assertTrue($admin->fresh()->hasRole('admin'));
    }

    // TC-11: Self-role change redirects with error flash
    public function test_um004_tc11_self_role_change_redirects_with_error_flash(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->patch(route('admin.users.assign-role', $admin), ['role' => 'user'])
            ->assertSessionHas('error');
    }

    // TC-12: Successful role change redirects with success flash
    public function test_um004_tc12_successful_role_change_has_success_flash(): void
    {
        $admin  = $this->makeAdmin();
        $target = $this->makeUser();

        $this->actingAs($admin)
            ->patch(route('admin.users.assign-role', $target), ['role' => 'admin'])
            ->assertSessionHas('success');
    }

    // TC-13: Nonexistent user returns 404
    public function test_um004_tc13_nonexistent_user_returns_404(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->patch(route('admin.users.assign-role', 9999), ['role' => 'user'])
            ->assertNotFound();
    }

    // TC-14: Show page has role dropdown with user and admin options
    public function test_um004_tc14_show_page_has_role_dropdown(): void
    {
        $admin  = $this->makeAdmin();
        $target = $this->makeUser();

        $response = $this->actingAs($admin)
            ->get(route('admin.users.show', $target));

        $response->assertStatus(200);
        $response->assertSee('name="role"', false);
        $response->assertSee('value="user"', false);
        $response->assertSee('value="admin"', false);
    }

    // TC-15: Show page pre-selects current role
    public function test_um004_tc15_show_page_preselects_current_role(): void
    {
        $admin  = $this->makeAdmin();
        $target = $this->makeUser();

        $response = $this->actingAs($admin)
            ->get(route('admin.users.show', $target));

        $response->assertStatus(200);
        // 'user' option should be selected
        $response->assertSee('value="user" selected', false);
    }

    // TC-16: Role form has CSRF field
    public function test_um004_tc16_role_form_has_csrf_field(): void
    {
        $admin  = $this->makeAdmin();
        $target = $this->makeUser();

        $response = $this->actingAs($admin)
            ->get(route('admin.users.show', $target));

        $response->assertStatus(200);
        $response->assertSee('_token', false);
    }

    // TC-17: Assign-role endpoint responds within 2 seconds
    public function test_um004_tc17_assign_role_responds_within_2_seconds(): void
    {
        $admin  = $this->makeAdmin();
        $target = $this->makeUser();

        $start = microtime(true);
        $this->actingAs($admin)
            ->patch(route('admin.users.assign-role', $target), ['role' => 'admin']);
        $elapsed = microtime(true) - $start;

        $this->assertLessThan(2.0, $elapsed, 'Assign-role endpoint exceeded 2 second threshold.');
    }
}
