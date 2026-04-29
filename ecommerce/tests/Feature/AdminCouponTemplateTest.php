<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\CouponTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminCouponTemplateTest extends TestCase
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

    public function test_admin_can_generate_new_user_templates(): void
    {
        $response = $this->actingAs($this->makeAdmin())
            ->post(route('admin.coupons.templates.generate'), [
                'generation_case' => 'new_user',
                'expiry_preset' => 'year',
                'uses_per_user' => 2,
                'new_user_percent_name_template' => 'Welcome {discount}',
                'new_user_percent_value' => 5,
                'new_user_fixed_name_template' => 'Welcome fixed {discount}',
                'new_user_fixed_value' => 5,
                'activate_now' => 0,
            ]);

        $response->assertRedirect(route('admin.coupons.index'));
        $this->assertEquals(2, CouponTemplate::count());
    }

    public function test_assign_template_does_not_duplicate_user_coupon(): void
    {
        $template = CouponTemplate::create([
            'name_template' => 'Welcome {discount}',
            'description_template' => 'Test',
            'scope' => 'new_user',
            'type' => 'fixed',
            'value' => 5,
            'uses_per_user' => 2,
            'expiry_mode' => 'duration_days',
            'expiry_days' => 365,
            'is_active' => true,
            'code_prefix' => 'WELCOME',
        ]);

        User::factory()->create(['is_active' => true]);

        $admin = $this->makeAdmin();

        $this->actingAs($admin)->post(route('admin.coupons.templates.assign', $template))
            ->assertRedirect(route('admin.coupons.index'));

        $firstAssignedCount = Coupon::where('coupon_template_id', $template->id)->count();

        $this->actingAs($admin)->post(route('admin.coupons.templates.assign', $template))
            ->assertRedirect(route('admin.coupons.index'));

        $this->assertEquals($firstAssignedCount, Coupon::where('coupon_template_id', $template->id)->count());
    }
}
