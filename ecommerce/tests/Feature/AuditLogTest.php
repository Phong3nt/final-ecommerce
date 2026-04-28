<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * IMP-016: Consolidated audit log — auth events + admin actions.
 *
 * Coverage: AU-002, AU-003, AU-004, PM-002, UM-004
 */
class AuditLogTest extends TestCase
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

    // ---------------------------------------------------------------
    // Happy-path: auth events
    // ---------------------------------------------------------------

    /** IMP-016-01: Successful login creates an auth.login audit log entry. */
    public function test_imp016_successful_login_creates_audit_log(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password123')]);

        $this->post(route('login.store'), [
            'email'    => $user->email,
            'password' => 'password123',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id'      => $user->id,
            'action'       => 'auth.login',
            'subject_type' => 'User',
            'subject_id'   => $user->id,
        ]);
    }

    /** IMP-016-02: Failed login creates an auth.login_failed audit log entry. */
    public function test_imp016_failed_login_creates_audit_log(): void
    {
        $user = User::factory()->create(['password' => bcrypt('correct')]);

        $this->post(route('login.store'), [
            'email'    => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action'       => 'auth.login_failed',
            'subject_type' => 'User',
        ]);
    }

    /** IMP-016-03: Logout creates an auth.logout audit log entry. */
    public function test_imp016_logout_creates_audit_log(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->post(route('logout'));

        $this->assertDatabaseHas('audit_logs', [
            'user_id'      => $user->id,
            'action'       => 'auth.logout',
            'subject_type' => 'User',
            'subject_id'   => $user->id,
        ]);
    }

    /** IMP-016-04: Registration creates an auth.register audit log entry. */
    public function test_imp016_registration_creates_audit_log(): void
    {
        $this->post(route('register.store'), [
            'name'                  => 'Test User',
            'email'                 => 'newuser@example.com',
            'password'              => 'Password1!',
            'password_confirmation' => 'Password1!',
        ]);

        $user = User::where('email', 'newuser@example.com')->first();
        $this->assertNotNull($user);

        $this->assertDatabaseHas('audit_logs', [
            'user_id'      => $user->id,
            'action'       => 'auth.register',
            'subject_type' => 'User',
            'subject_id'   => $user->id,
        ]);
    }

    // ---------------------------------------------------------------
    // Happy-path: admin actions
    // ---------------------------------------------------------------

    /** IMP-016-05: Admin toggling user status creates a user.status_changed log. */
    public function test_imp016_toggle_status_creates_audit_log(): void
    {
        $admin  = $this->makeAdmin();
        $target = $this->makeUser();

        $this->actingAs($admin)
            ->patch(route('admin.users.toggle-status', $target));

        $this->assertDatabaseHas('audit_logs', [
            'user_id'      => $admin->id,
            'action'       => 'user.status_changed',
            'subject_type' => 'User',
            'subject_id'   => $target->id,
        ]);
    }

    /** IMP-016-06: Admin assigning a role creates a user.role_changed log (existing). */
    public function test_imp016_assign_role_creates_audit_log(): void
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

    /** IMP-016-07: Admin editing a product creates a product.updated log (existing). */
    public function test_imp016_product_update_creates_audit_log(): void
    {
        $admin   = $this->makeAdmin();
        $product = Product::factory()->create(['status' => 'published']);

        $this->actingAs($admin)->patch(route('admin.products.update', $product), [
            'name'        => 'Updated Name',
            'price'       => '99.99',
            'stock'       => '10',
            'status'      => 'published',
            'description' => 'Test',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id'      => $admin->id,
            'action'       => 'product.updated',
            'subject_type' => 'Product',
            'subject_id'   => $product->id,
        ]);
    }

    // ---------------------------------------------------------------
    // Admin audit log view
    // ---------------------------------------------------------------

    /** IMP-016-08: Admin can view the audit log page. */
    public function test_imp016_admin_can_view_audit_log_page(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->get(route('admin.audit-log.index'));

        $response->assertStatus(200);
        $response->assertSee('Audit Log');
        $response->assertSee('data-imp016="audit-table"', false);
    }

    /** IMP-016-09: Audit log page shows existing entries. */
    public function test_imp016_audit_log_page_shows_entries(): void
    {
        $admin = $this->makeAdmin();

        AuditLog::create([
            'user_id'      => $admin->id,
            'action'       => 'auth.login',
            'subject_type' => 'User',
            'subject_id'   => $admin->id,
            'ip_address'   => '127.0.0.1',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.audit-log.index'));

        $response->assertStatus(200);
        $response->assertSee('auth.login');
        $response->assertSee('data-imp016="audit-row"', false);
    }

    /** IMP-016-10: Audit log page supports filtering by action. */
    public function test_imp016_filter_by_action_returns_matching_entries(): void
    {
        $admin = $this->makeAdmin();

        AuditLog::create(['user_id' => $admin->id, 'action' => 'auth.login',   'subject_type' => 'User', 'subject_id' => $admin->id]);
        AuditLog::create(['user_id' => $admin->id, 'action' => 'auth.login',   'subject_type' => 'User', 'subject_id' => $admin->id]);
        AuditLog::create(['user_id' => $admin->id, 'action' => 'user.status_changed', 'subject_type' => 'User', 'subject_id' => $admin->id]);

        $response = $this->actingAs($admin)
            ->get(route('admin.audit-log.index', ['action' => 'auth.login']));

        $response->assertStatus(200);

        // Exactly 2 rows should match (both auth.login entries)
        $html = $response->getContent();
        $rowCount = substr_count($html, 'data-imp016="audit-row"');
        $this->assertEquals(2, $rowCount, "Expected 2 filtered rows, got {$rowCount}.");
    }

    // ---------------------------------------------------------------
    // Security / negative
    // ---------------------------------------------------------------

    /** IMP-016-11: Non-admin (regular user) cannot access the audit log page. */
    public function test_imp016_non_admin_cannot_access_audit_log(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user)->get(route('admin.audit-log.index'));

        $response->assertStatus(403);
    }

    // ---------------------------------------------------------------
    // Performance
    // ---------------------------------------------------------------

    /** IMP-016-12: Audit log page responds within 2 seconds with 50 entries. */
    public function test_imp016_performance_audit_log_page_under_2_seconds(): void
    {
        $admin = $this->makeAdmin();

        $rows = [];
        for ($i = 0; $i < 50; $i++) {
            $rows[] = [
                'user_id'      => $admin->id,
                'action'       => 'auth.login',
                'subject_type' => 'User',
                'subject_id'   => $admin->id,
                'old_values'   => null,
                'new_values'   => json_encode(['email' => 'test@test.com']),
                'ip_address'   => '127.0.0.1',
                'created_at'   => now(),
                'updated_at'   => now(),
            ];
        }
        AuditLog::insert($rows);

        $start    = microtime(true);
        $response = $this->actingAs($admin)->get(route('admin.audit-log.index'));
        $elapsed  = microtime(true) - $start;

        $response->assertStatus(200);
        $this->assertLessThan(2.0, $elapsed, "Audit log page took {$elapsed}s — exceeds 2s budget.");
    }
}
