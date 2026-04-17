<?php

namespace Tests\Feature;

use App\Mail\OrderStatusChanged;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * OM-003 — As an admin, I want to update an order's status so customers are kept informed.
 *
 * Acceptance criteria:
 *   - Status transitions: Pending → Processing → Shipped → Delivered / Cancelled
 *   - Customer email sent on each change
 */
class AdminOrderStatusUpdateTest extends TestCase
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

    private function makeOrder(array $overrides = []): Order
    {
        $customer = $this->makeUser();
        return Order::factory()->create(array_merge([
            'user_id' => $customer->id,
            'status' => 'paid',
        ], $overrides));
    }

    // TC-01: Guest cannot update order status → redirect to login
    public function test_om003_guest_is_redirected_from_status_update(): void
    {
        $order = $this->makeOrder();
        $this->patch(route('admin.orders.status', $order), ['status' => 'processing'])
            ->assertRedirect(route('login'));
    }

    // TC-02: Non-admin gets 403 on status update
    public function test_om003_non_admin_gets_403_on_status_update(): void
    {
        $order = $this->makeOrder();
        $this->actingAs($this->makeUser())
            ->patch(route('admin.orders.status', $order), ['status' => 'processing'])
            ->assertForbidden();
    }

    // TC-03: Admin can set status to processing; processing_at is recorded
    public function test_om003_admin_can_set_status_to_processing(): void
    {
        $order = $this->makeOrder(['status' => 'paid']);
        $this->actingAs($this->makeAdmin())
            ->patch(route('admin.orders.status', $order), ['status' => 'processing'])
            ->assertRedirect();

        $fresh = $order->fresh();
        $this->assertSame('processing', $fresh->status);
        $this->assertNotNull($fresh->processing_at);
    }

    // TC-04: Admin can set status to shipped; shipped_at is recorded
    public function test_om003_admin_can_set_status_to_shipped(): void
    {
        $order = $this->makeOrder(['status' => 'processing', 'processing_at' => now()]);
        $this->actingAs($this->makeAdmin())
            ->patch(route('admin.orders.status', $order), ['status' => 'shipped'])
            ->assertRedirect();

        $fresh = $order->fresh();
        $this->assertSame('shipped', $fresh->status);
        $this->assertNotNull($fresh->shipped_at);
    }

    // TC-05: Admin can set status to delivered; delivered_at is recorded
    public function test_om003_admin_can_set_status_to_delivered(): void
    {
        $order = $this->makeOrder([
            'status' => 'shipped',
            'processing_at' => now()->subDay(),
            'shipped_at' => now(),
        ]);
        $this->actingAs($this->makeAdmin())
            ->patch(route('admin.orders.status', $order), ['status' => 'delivered'])
            ->assertRedirect();

        $fresh = $order->fresh();
        $this->assertSame('delivered', $fresh->status);
        $this->assertNotNull($fresh->delivered_at);
    }

    // TC-06: Admin can cancel an order; cancelled_at is recorded
    public function test_om003_admin_can_cancel_order(): void
    {
        $order = $this->makeOrder(['status' => 'paid']);
        $this->actingAs($this->makeAdmin())
            ->patch(route('admin.orders.status', $order), ['status' => 'cancelled'])
            ->assertRedirect();

        $fresh = $order->fresh();
        $this->assertSame('cancelled', $fresh->status);
        $this->assertNotNull($fresh->cancelled_at);
    }

    // TC-07: Customer email is sent when status is changed to processing
    public function test_om003_email_sent_on_status_change_to_processing(): void
    {
        Mail::fake();

        $customer = $this->makeUser();
        $order = Order::factory()->create(['user_id' => $customer->id, 'status' => 'paid']);

        $this->actingAs($this->makeAdmin())
            ->patch(route('admin.orders.status', $order), ['status' => 'processing']);

        Mail::assertSent(OrderStatusChanged::class, fn($mail) =>
            $mail->order->id === $order->id && $mail->hasTo($customer->email)
        );
    }

    // TC-08: Customer email is sent when order is cancelled
    public function test_om003_email_sent_on_cancellation(): void
    {
        Mail::fake();

        $customer = $this->makeUser();
        $order = Order::factory()->create(['user_id' => $customer->id, 'status' => 'paid']);

        $this->actingAs($this->makeAdmin())
            ->patch(route('admin.orders.status', $order), ['status' => 'cancelled']);

        Mail::assertSent(OrderStatusChanged::class, fn($mail) =>
            $mail->order->id === $order->id && $mail->hasTo($customer->email)
        );
    }

    // TC-09: Invalid status value is rejected with 422
    public function test_om003_invalid_status_value_is_rejected(): void
    {
        $order = $this->makeOrder();
        $this->actingAs($this->makeAdmin())
            ->patchJson(route('admin.orders.status', $order), ['status' => 'refunded'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    // TC-10: Missing status value is rejected with 422
    public function test_om003_missing_status_value_is_rejected(): void
    {
        $order = $this->makeOrder();
        $this->actingAs($this->makeAdmin())
            ->patchJson(route('admin.orders.status', $order), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    // TC-11: Successful update redirects back with success flash message
    public function test_om003_status_update_redirects_with_success_message(): void
    {
        Mail::fake();

        $order = $this->makeOrder(['status' => 'paid']);
        $this->actingAs($this->makeAdmin())
            ->patch(route('admin.orders.status', $order), ['status' => 'processing'])
            ->assertRedirect()
            ->assertSessionHas('success');
    }

    // TC-12: Non-existent order returns 404
    public function test_om003_non_existent_order_returns_404(): void
    {
        $this->actingAs($this->makeAdmin())
            ->patch(route('admin.orders.status', 99999), ['status' => 'processing'])
            ->assertNotFound();
    }
}
