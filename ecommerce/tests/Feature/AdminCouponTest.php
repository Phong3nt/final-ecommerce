<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * RM-003 — As an admin, I want to manage discount coupons so I can run promotions.
 *
 * Acceptance criteria:
 *   - CRUD for coupons
 *   - Fields: code, type (%), value, expiry, usage limit, min order amount
 *   - Active/inactive toggle
 */
class AdminCouponTest extends TestCase
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
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('admin');
        return $admin;
    }

    private function makeUser(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('user');
        return $user;
    }

    // -------------------------------------------------------------------------
    // TC-01  Guest is redirected to login (index)
    // -------------------------------------------------------------------------
    public function test_rm003_tc01_guest_redirected_to_coupon_index(): void
    {
        $this->get(route('admin.coupons.index'))
            ->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // TC-02  Non-admin gets 403 (index)
    // -------------------------------------------------------------------------
    public function test_rm003_tc02_non_admin_gets_403_on_coupon_index(): void
    {
        $this->actingAs($this->makeUser())
            ->get(route('admin.coupons.index'))
            ->assertForbidden();
    }

    // -------------------------------------------------------------------------
    // TC-03  Admin can view coupon index
    // -------------------------------------------------------------------------
    public function test_rm003_tc03_admin_can_view_coupon_index(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get(route('admin.coupons.index'))
            ->assertOk();
    }

    // -------------------------------------------------------------------------
    // TC-04  Coupon appears in index listing
    // -------------------------------------------------------------------------
    public function test_rm003_tc04_coupon_appears_in_index(): void
    {
        $coupon = Coupon::factory()->create(['code' => 'TESTSHOW']);

        $this->actingAs($this->makeAdmin())
            ->get(route('admin.coupons.index'))
            ->assertOk()
            ->assertSee('TESTSHOW');
    }

    // -------------------------------------------------------------------------
    // TC-05  Index shows active/inactive badge
    // -------------------------------------------------------------------------
    public function test_rm003_tc05_index_shows_active_and_inactive_status(): void
    {
        Coupon::factory()->create(['code' => 'ACTIVE1', 'is_active' => true]);
        Coupon::factory()->create(['code' => 'INACTIVE1', 'is_active' => false]);

        $response = $this->actingAs($this->makeAdmin())
            ->get(route('admin.coupons.index'));

        $response->assertOk()
            ->assertSee('Active')
            ->assertSee('Inactive');
    }

    // -------------------------------------------------------------------------
    // TC-06  Admin can view create form
    // -------------------------------------------------------------------------
    public function test_rm003_tc06_admin_can_view_create_form(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get(route('admin.coupons.create'))
            ->assertOk()
            ->assertSee('New Coupon');
    }

    // -------------------------------------------------------------------------
    // TC-07  Admin can create a valid coupon
    // -------------------------------------------------------------------------
    public function test_rm003_tc07_admin_can_create_coupon(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->post(route('admin.coupons.store'), [
            'code' => 'PROMO20',
            'type' => 'percent',
            'value' => 20,
            'expires_at' => '2030-12-31',
            'usage_limit' => 100,
            'min_order_amount' => 50,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.coupons.index'));
        $this->assertDatabaseHas('coupons', [
            'code' => 'PROMO20',
            'type' => 'percent',
            'value' => 20,
            'usage_limit' => 100,
            'is_active' => true,
        ]);
    }

    // -------------------------------------------------------------------------
    // TC-08  Code is stored as upper case
    // -------------------------------------------------------------------------
    public function test_rm003_tc08_code_stored_uppercase(): void
    {
        $this->actingAs($this->makeAdmin())->post(route('admin.coupons.store'), [
            'code' => 'lower',
            'type' => 'fixed',
            'value' => 5,
        ]);

        $this->assertDatabaseHas('coupons', ['code' => 'LOWER']);
    }

    // -------------------------------------------------------------------------
    // TC-09  Code must be unique on create
    // -------------------------------------------------------------------------
    public function test_rm003_tc09_code_must_be_unique_on_create(): void
    {
        Coupon::factory()->create(['code' => 'DUPE']);

        $response = $this->actingAs($this->makeAdmin())->post(route('admin.coupons.store'), [
            'code' => 'DUPE',
            'type' => 'fixed',
            'value' => 5,
        ]);

        $response->assertSessionHasErrors('code');
    }

    // -------------------------------------------------------------------------
    // TC-10  Type must be 'percent' or 'fixed'
    // -------------------------------------------------------------------------
    public function test_rm003_tc10_type_must_be_percent_or_fixed(): void
    {
        $response = $this->actingAs($this->makeAdmin())->post(route('admin.coupons.store'), [
            'code' => 'BADTYPE',
            'type' => 'discount',
            'value' => 5,
        ]);

        $response->assertSessionHasErrors('type');
    }

    // -------------------------------------------------------------------------
    // TC-11  Value must be positive (> 0)
    // -------------------------------------------------------------------------
    public function test_rm003_tc11_value_must_be_positive(): void
    {
        $response = $this->actingAs($this->makeAdmin())->post(route('admin.coupons.store'), [
            'code' => 'ZEROVAL',
            'type' => 'percent',
            'value' => 0,
        ]);

        $response->assertSessionHasErrors('value');
    }

    // -------------------------------------------------------------------------
    // TC-12  Usage limit must be a positive integer if provided
    // -------------------------------------------------------------------------
    public function test_rm003_tc12_usage_limit_must_be_positive_integer(): void
    {
        $response = $this->actingAs($this->makeAdmin())->post(route('admin.coupons.store'), [
            'code' => 'BADLIMIT',
            'type' => 'percent',
            'value' => 10,
            'usage_limit' => 0,
        ]);

        $response->assertSessionHasErrors('usage_limit');
    }

    // -------------------------------------------------------------------------
    // TC-13  Min order amount must be non-negative if provided
    // -------------------------------------------------------------------------
    public function test_rm003_tc13_min_order_amount_must_be_non_negative(): void
    {
        $response = $this->actingAs($this->makeAdmin())->post(route('admin.coupons.store'), [
            'code' => 'BADMIN',
            'type' => 'percent',
            'value' => 10,
            'min_order_amount' => -1,
        ]);

        $response->assertSessionHasErrors('min_order_amount');
    }

    // -------------------------------------------------------------------------
    // TC-14  Admin can view edit form
    // -------------------------------------------------------------------------
    public function test_rm003_tc14_admin_can_view_edit_form(): void
    {
        $coupon = Coupon::factory()->create(['code' => 'EDITME']);

        $this->actingAs($this->makeAdmin())
            ->get(route('admin.coupons.edit', $coupon))
            ->assertOk()
            ->assertSee('EDITME');
    }

    // -------------------------------------------------------------------------
    // TC-15  Admin can update a coupon
    // -------------------------------------------------------------------------
    public function test_rm003_tc15_admin_can_update_coupon(): void
    {
        $coupon = Coupon::factory()->create(['code' => 'OLD', 'value' => 5]);

        $response = $this->actingAs($this->makeAdmin())->patch(route('admin.coupons.update', $coupon), [
            'code' => 'OLD',
            'type' => 'fixed',
            'value' => 15,
        ]);

        $response->assertRedirect(route('admin.coupons.index'));
        $this->assertDatabaseHas('coupons', ['id' => $coupon->id, 'value' => 15]);
    }

    // -------------------------------------------------------------------------
    // TC-16  Code uniqueness ignores self on update
    // -------------------------------------------------------------------------
    public function test_rm003_tc16_update_allows_same_code_for_self(): void
    {
        $coupon = Coupon::factory()->create(['code' => 'SELFCODE']);

        $response = $this->actingAs($this->makeAdmin())->patch(route('admin.coupons.update', $coupon), [
            'code' => 'SELFCODE',
            'type' => 'fixed',
            'value' => 10,
        ]);

        $response->assertRedirect(route('admin.coupons.index'));
        $response->assertSessionHasNoErrors();
    }

    // -------------------------------------------------------------------------
    // TC-17  Admin can delete a coupon
    // -------------------------------------------------------------------------
    public function test_rm003_tc17_admin_can_delete_coupon(): void
    {
        $coupon = Coupon::factory()->create(['code' => 'DELETEME']);

        $response = $this->actingAs($this->makeAdmin())
            ->delete(route('admin.coupons.destroy', $coupon));

        $response->assertRedirect(route('admin.coupons.index'));
        $this->assertDatabaseMissing('coupons', ['id' => $coupon->id]);
    }

    // -------------------------------------------------------------------------
    // TC-18  Toggle active coupon becomes inactive
    // -------------------------------------------------------------------------
    public function test_rm003_tc18_toggle_active_coupon_becomes_inactive(): void
    {
        $coupon = Coupon::factory()->create(['is_active' => true]);

        $this->actingAs($this->makeAdmin())
            ->patch(route('admin.coupons.toggle', $coupon))
            ->assertRedirect(route('admin.coupons.index'));

        $this->assertDatabaseHas('coupons', ['id' => $coupon->id, 'is_active' => false]);
    }

    // -------------------------------------------------------------------------
    // TC-19  Toggle inactive coupon becomes active
    // -------------------------------------------------------------------------
    public function test_rm003_tc19_toggle_inactive_coupon_becomes_active(): void
    {
        $coupon = Coupon::factory()->inactive()->create();

        $this->actingAs($this->makeAdmin())
            ->patch(route('admin.coupons.toggle', $coupon))
            ->assertRedirect(route('admin.coupons.index'));

        $this->assertDatabaseHas('coupons', ['id' => $coupon->id, 'is_active' => true]);
    }

    // -------------------------------------------------------------------------
    // TC-20  Guest redirected on create/store/edit/update/delete/toggle
    // -------------------------------------------------------------------------
    public function test_rm003_tc20_guest_redirected_on_all_mutating_routes(): void
    {
        $coupon = Coupon::factory()->create();

        $this->get(route('admin.coupons.create'))->assertRedirect(route('login'));
        $this->post(route('admin.coupons.store'), [])->assertRedirect(route('login'));
        $this->get(route('admin.coupons.edit', $coupon))->assertRedirect(route('login'));
        $this->patch(route('admin.coupons.update', $coupon), [])->assertRedirect(route('login'));
        $this->delete(route('admin.coupons.destroy', $coupon))->assertRedirect(route('login'));
        $this->patch(route('admin.coupons.toggle', $coupon))->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // TC-21  Coupon with all optional fields stored and retrievable
    // -------------------------------------------------------------------------
    public function test_rm003_tc21_coupon_with_all_fields_stored_correctly(): void
    {
        $this->actingAs($this->makeAdmin())->post(route('admin.coupons.store'), [
            'code' => 'FULL',
            'type' => 'percent',
            'value' => 15,
            'expires_at' => '2031-06-30',
            'usage_limit' => 200,
            'min_order_amount' => 100,
            'is_active' => '1',
        ]);

        $this->assertDatabaseHas('coupons', [
            'code' => 'FULL',
            'type' => 'percent',
            'value' => 15,
            'usage_limit' => 200,
            'is_active' => true,
        ]);

        $coupon = Coupon::where('code', 'FULL')->first();
        $this->assertEquals(100.0, $coupon->min_order_amount);
        $this->assertEquals('2031-06-30', $coupon->expires_at->format('Y-m-d'));
    }
}
