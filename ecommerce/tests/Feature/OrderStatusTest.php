<?php

namespace Tests\Feature;

use App\Mail\OrderStatusChanged;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * OH-003 — As a user, I want to track my order status so I know when it will arrive.
 *
 * Acceptance criteria:
 *   - Status steps: Pending → Processing → Shipped → Delivered
 *   - Timestamps for each step shown on detail page
 *   - Email notification on status change
 *   - Admin can advance order status via PATCH /admin/orders/{order}/status
 *   - Non-admin cannot change order status (403)
 *   - Invalid status values are rejected (422)
 */
class OrderStatusTest extends TestCase
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

    private function makeOrder(User $user, array $state = []): Order
    {
        return Order::factory()->for($user)->create(array_merge([
            'status' => 'paid',
            'subtotal' => 50.00,
            'shipping_cost' => 5.00,
            'total' => 55.00,
            'shipping_method' => 'standard',
            'shipping_label' => 'Standard Shipping',
            'address' => [
                'name' => 'Test User',
                'address_line1' => '1 Main St',
                'address_line2' => null,
                'city' => 'Springfield',
                'state' => 'IL',
                'postal_code' => '62701',
                'country' => 'US',
            ],
        ], $state));
    }

    // TC-01: Status timeline section is visible on the order detail page
    public function test_oh003_status_timeline_section_visible_on_detail_page(): void
    {
        $user = $this->makeUser();
        $order = $this->makeOrder($user);

        $response = $this->actingAs($user)->get(route('orders.show', $order));

        $response->assertStatus(200);
        $response->assertSee('Status');
        $response->assertSee('Placed');
        $response->assertSee('Processing');
        $response->assertSee('Shipped');
        $response->assertSee('Delivered');
    }

    // TC-02: Placed step always shows order creation timestamp
    public function test_oh003_placed_step_shows_created_at_timestamp(): void
    {
        $user = $this->makeUser();
        $order = $this->makeOrder($user);

        $response = $this->actingAs($user)->get(route('orders.show', $order));

        $response->assertStatus(200);
        $response->assertSee($order->created_at->format('d M Y'));
    }

    // TC-03: Processing timestamp shown when order status is processing
    public function test_oh003_processing_timestamp_shown_when_status_is_processing(): void
    {
        $user = $this->makeUser();
        $processedAt = Carbon::parse('2026-04-16 10:00:00');
        $order = $this->makeOrder($user, [
            'status' => 'processing',
            'processing_at' => $processedAt,
        ]);

        $response = $this->actingAs($user)->get(route('orders.show', $order));

        $response->assertStatus(200);
        $response->assertSee($processedAt->format('d M Y'));
    }

    // TC-04: Shipped timestamp shown when order status is shipped
    public function test_oh003_shipped_timestamp_shown_when_status_is_shipped(): void
    {
        $user = $this->makeUser();
        $shippedAt = Carbon::parse('2026-04-17 09:30:00');
        $order = $this->makeOrder($user, [
            'status' => 'shipped',
            'processing_at' => $shippedAt->copy()->subDay(),
            'shipped_at' => $shippedAt,
        ]);

        $response = $this->actingAs($user)->get(route('orders.show', $order));

        $response->assertStatus(200);
        $response->assertSee($shippedAt->format('d M Y'));
    }

    // TC-05: Delivered timestamp shown when order status is delivered
    public function test_oh003_delivered_timestamp_shown_when_status_is_delivered(): void
    {
        $user = $this->makeUser();
        $deliveredAt = Carbon::parse('2026-04-19 14:00:00');
        $order = $this->makeOrder($user, [
            'status' => 'delivered',
            'processing_at' => $deliveredAt->copy()->subDays(3),
            'shipped_at' => $deliveredAt->copy()->subDays(2),
            'delivered_at' => $deliveredAt,
        ]);

        $response = $this->actingAs($user)->get(route('orders.show', $order));

        $response->assertStatus(200);
        $response->assertSee($deliveredAt->format('d M Y'));
    }

    // TC-06: Admin can advance order status to processing
    public function test_oh003_admin_can_advance_order_to_processing(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeUser();
        $order = $this->makeOrder($owner, ['status' => 'paid']);

        $this->actingAs($admin)
            ->patch(route('admin.orders.status', $order), ['status' => 'processing'])
            ->assertRedirect();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'processing',
        ]);
        $this->assertNotNull($order->fresh()->processing_at);
    }

    // TC-07: Admin can advance order status to shipped
    public function test_oh003_admin_can_advance_order_to_shipped(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeUser();
        $order = $this->makeOrder($owner, ['status' => 'processing', 'processing_at' => now()]);

        $this->actingAs($admin)
            ->patch(route('admin.orders.status', $order), ['status' => 'shipped'])
            ->assertRedirect();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'shipped',
        ]);
        $this->assertNotNull($order->fresh()->shipped_at);
    }

    // TC-08: Admin can advance order status to delivered
    public function test_oh003_admin_can_advance_order_to_delivered(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeUser();
        $order = $this->makeOrder($owner, [
            'status' => 'shipped',
            'processing_at' => now()->subDay(),
            'shipped_at' => now(),
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.orders.status', $order), ['status' => 'delivered'])
            ->assertRedirect();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'delivered',
        ]);
        $this->assertNotNull($order->fresh()->delivered_at);
    }

    // TC-09: Non-admin user cannot update order status (403)
    public function test_oh003_non_admin_cannot_update_order_status(): void
    {
        $regularUser = $this->makeUser();
        $owner = $this->makeUser();
        $order = $this->makeOrder($owner);

        $this->actingAs($regularUser)
            ->patch(route('admin.orders.status', $order), ['status' => 'processing'])
            ->assertForbidden();
    }

    // TC-10: Invalid status value is rejected with validation error
    public function test_oh003_invalid_status_value_is_rejected(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeUser();
        $order = $this->makeOrder($owner);

        $this->actingAs($admin)
            ->patchJson(route('admin.orders.status', $order), ['status' => 'unknown'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    // TC-11: Status change dispatches OrderStatusChanged email to the order owner
    public function test_oh003_status_change_dispatches_notification_email(): void
    {
        Mail::fake();

        $admin = $this->makeAdmin();
        $owner = $this->makeUser();
        $order = $this->makeOrder($owner, ['status' => 'paid']);

        $this->actingAs($admin)
            ->patch(route('admin.orders.status', $order), ['status' => 'processing'])
            ->assertRedirect();

        Mail::assertSent(OrderStatusChanged::class, function (OrderStatusChanged $mail) use ($owner, $order) {
            return $mail->order->id === $order->id
                && $mail->hasTo($owner->email);
        });
    }

    // TC-12: Status update endpoint responds within two seconds
    public function test_oh003_status_update_responds_within_two_seconds(): void
    {
        Mail::fake();

        $admin = $this->makeAdmin();
        $owner = $this->makeUser();
        $order = $this->makeOrder($owner, ['status' => 'paid']);

        $start = microtime(true);
        $this->actingAs($admin)
            ->patch(route('admin.orders.status', $order), ['status' => 'processing'])
            ->assertRedirect();
        $elapsed = microtime(true) - $start;

        $this->assertLessThan(2.0, $elapsed, 'Status update took longer than 2 seconds.');
    }
}
