<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * UM-003 — As an admin, I want to activate or suspend a user account
 *           so I can enforce policies.
 *
 * Acceptance criteria:
 *   - Suspended users cannot log in (error with explanation)
 *   - Status toggle with confirmation (server-side protection)
 */
class AdminUserToggleStatusTest extends TestCase
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

    // TC-01: Guest is redirected to login on toggle endpoint
    public function test_um003_tc01_guest_redirected_to_login(): void
    {
        $user = $this->makeUser();

        $this->patch(route('admin.users.toggle-status', $user))
            ->assertRedirect(route('login'));
    }

    // TC-02: Non-admin gets 403 on toggle
    public function test_um003_tc02_non_admin_gets_403(): void
    {
        $actor  = $this->makeUser();
        $target = $this->makeUser();

        $this->actingAs($actor)
            ->patch(route('admin.users.toggle-status', $target))
            ->assertForbidden();
    }

    // TC-03: Admin can suspend an active user (is_active flips to false)
    public function test_um003_tc03_admin_can_suspend_active_user(): void
    {
        $admin = $this->makeAdmin();
        $user  = $this->makeUser(['is_active' => true]);

        $this->actingAs($admin)
            ->patch(route('admin.users.toggle-status', $user));

        $this->assertFalse($user->fresh()->is_active);
    }

    // TC-04: Admin can reactivate a suspended user (is_active flips to true)
    public function test_um003_tc04_admin_can_activate_suspended_user(): void
    {
        $admin = $this->makeAdmin();
        $user  = $this->makeUser(['is_active' => false]);

        $this->actingAs($admin)
            ->patch(route('admin.users.toggle-status', $user));

        $this->assertTrue($user->fresh()->is_active);
    }

    // TC-05: Suspended user cannot log in — login attempt redirects back with error
    public function test_um003_tc05_suspended_user_cannot_log_in(): void
    {
        $user = $this->makeUser(['password' => bcrypt('secret123'), 'is_active' => false]);

        $this->post(route('login.store'), [
            'email'    => $user->email,
            'password' => 'secret123',
        ])->assertRedirect();

        $this->assertGuest();
    }

    // TC-06: Suspended login returns error message containing "suspended"
    public function test_um003_tc06_suspended_login_shows_error_message(): void
    {
        $user = $this->makeUser(['password' => bcrypt('secret123'), 'is_active' => false]);

        $response = $this->post(route('login.store'), [
            'email'    => $user->email,
            'password' => 'secret123',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertStringContainsStringIgnoringCase(
            'suspended',
            session('errors')->first('email')
        );
    }

    // TC-07: Admin cannot suspend their own account
    public function test_um003_tc07_admin_cannot_suspend_own_account(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->patch(route('admin.users.toggle-status', $admin));

        // is_active remains true — self-suspension blocked
        $this->assertTrue($admin->fresh()->is_active);
    }

    // TC-08: Self-suspension attempt redirects with error flash
    public function test_um003_tc08_self_suspension_redirects_with_error_flash(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->patch(route('admin.users.toggle-status', $admin))
            ->assertRedirect(route('admin.users.show', $admin))
            ->assertSessionHas('error');
    }

    // TC-09: Successful toggle redirects to user show page with success flash
    public function test_um003_tc09_toggle_redirects_with_success_flash(): void
    {
        $admin = $this->makeAdmin();
        $user  = $this->makeUser();

        $this->actingAs($admin)
            ->patch(route('admin.users.toggle-status', $user))
            ->assertRedirect(route('admin.users.show', $user))
            ->assertSessionHas('success');
    }

    // TC-10: Show page displays "Active" status for active user
    public function test_um003_tc10_show_page_displays_active_status(): void
    {
        $admin = $this->makeAdmin();
        $user  = $this->makeUser(['is_active' => true]);

        $this->actingAs($admin)
            ->get(route('admin.users.show', $user))
            ->assertOk()
            ->assertSee('Active');
    }

    // TC-11: Show page displays "Suspended" status for suspended user
    public function test_um003_tc11_show_page_displays_suspended_status(): void
    {
        $admin = $this->makeAdmin();
        $user  = $this->makeUser(['is_active' => false]);

        $this->actingAs($admin)
            ->get(route('admin.users.show', $user))
            ->assertOk()
            ->assertSee('Suspended');
    }

    // TC-12: Show page contains "Suspend Account" button for active user
    public function test_um003_tc12_show_page_has_suspend_button_for_active_user(): void
    {
        $admin = $this->makeAdmin();
        $user  = $this->makeUser(['is_active' => true]);

        $this->actingAs($admin)
            ->get(route('admin.users.show', $user))
            ->assertOk()
            ->assertSee('Suspend Account');
    }

    // TC-13: Show page contains "Activate Account" button for suspended user
    public function test_um003_tc13_show_page_has_activate_button_for_suspended_user(): void
    {
        $admin = $this->makeAdmin();
        $user  = $this->makeUser(['is_active' => false]);

        $this->actingAs($admin)
            ->get(route('admin.users.show', $user))
            ->assertOk()
            ->assertSee('Activate Account');
    }

    // TC-14: Active user can log in normally
    public function test_um003_tc14_active_user_can_log_in(): void
    {
        $user = $this->makeUser(['password' => bcrypt('secret123'), 'is_active' => true]);

        $this->post(route('login.store'), [
            'email'    => $user->email,
            'password' => 'secret123',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($user);
    }

    // TC-15: Non-existent user returns 404 on toggle
    public function test_um003_tc15_nonexistent_user_returns_404(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->patch(route('admin.users.toggle-status', 99999))
            ->assertNotFound();
    }

    // TC-16: Toggle form has CSRF field (form rendered on show page)
    public function test_um003_tc16_toggle_form_has_csrf_field(): void
    {
        $admin = $this->makeAdmin();
        $user  = $this->makeUser();

        $this->actingAs($admin)
            ->get(route('admin.users.show', $user))
            ->assertOk()
            ->assertSee('_token', false);
    }

    // TC-17: Toggle responds within 2 seconds
    public function test_um003_tc17_toggle_responds_within_2_seconds(): void
    {
        $admin = $this->makeAdmin();
        $user  = $this->makeUser();

        $start = microtime(true);

        $this->actingAs($admin)
            ->patch(route('admin.users.toggle-status', $user));

        $this->assertLessThan(2.0, microtime(true) - $start, 'Toggle took more than 2 seconds');
    }
}
