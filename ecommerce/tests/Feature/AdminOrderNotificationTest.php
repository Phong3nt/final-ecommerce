<?php

namespace Tests\Feature;

use App\Jobs\NotifyAdminOfNewOrder;
use App\Mail\NewOrderAdminMail;
use App\Models\AdminNotification;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\PaymentServiceInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * NT-002 — As an admin, I want to be notified of new orders so I can act quickly.
 *
 * Acceptance criteria:
 *   - In-app notification bell + optional email
 *   - Mark as read
 */
class AdminOrderNotificationTest extends TestCase
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

    private function makeOrder(User $user): Order
    {
        $order = Order::factory()->for($user)->create([
            'status' => 'paid',
            'subtotal' => 50.00,
            'shipping_cost' => 5.00,
            'total' => 55.00,
            'shipping_method' => 'standard',
            'shipping_label' => 'Standard Shipping',
            'address' => ['name' => 'Test', 'city' => 'NY'],
        ]);

        OrderItem::factory()->for($order)->create([
            'product_name' => 'Widget',
            'quantity' => 1,
            'unit_price' => 50.00,
            'subtotal' => 50.00,
        ]);

        return $order;
    }

    // -------------------------------------------------------------------------
    // TC-01  NotifyAdminOfNewOrder implements ShouldQueue
    // -------------------------------------------------------------------------
    public function test_nt002_tc01_notify_admin_job_implements_should_queue(): void
    {
        $user = $this->makeUser();
        $order = $this->makeOrder($user);
        $job = new NotifyAdminOfNewOrder($order);

        $this->assertInstanceOf(ShouldQueue::class, $job);
    }

    // -------------------------------------------------------------------------
    // TC-02  Webhook payment_intent.succeeded dispatches NotifyAdminOfNewOrder
    // -------------------------------------------------------------------------
    public function test_nt002_tc02_webhook_dispatches_notify_admin_job(): void
    {
        Queue::fake();

        $user = $this->makeUser();
        $order = Order::factory()->for($user)->create([
            'status' => 'pending',
            'subtotal' => 50.00,
            'shipping_cost' => 5.00,
            'total' => 55.00,
            'shipping_method' => 'standard',
            'shipping_label' => 'Standard Shipping',
            'address' => ['name' => 'Test', 'city' => 'NY'],
            'stripe_payment_intent_id' => 'pi_nt002_test',
            'stripe_client_secret' => 'pi_nt002_secret',
        ]);

        $fakeEvent = json_decode(json_encode([
            'type' => 'payment_intent.succeeded',
            'data' => ['object' => ['id' => 'pi_nt002_test']],
        ]));

        $this->mock(
            PaymentServiceInterface::class,
            fn($m) =>
            $m->shouldReceive('constructWebhookEvent')->andReturn($fakeEvent)
        );

        $this->postJson(route('webhook.stripe'), [], ['Stripe-Signature' => 'fake'])
            ->assertStatus(200);

        Queue::assertPushed(
            NotifyAdminOfNewOrder::class,
            fn($job) =>
            $job->order->id === $order->id
        );
    }

    // -------------------------------------------------------------------------
    // TC-03  NotifyAdminOfNewOrder::handle() creates one AdminNotification record
    // -------------------------------------------------------------------------
    public function test_nt002_tc03_job_creates_admin_notification(): void
    {
        Mail::fake();

        $user = $this->makeUser();
        $order = $this->makeOrder($user);

        (new NotifyAdminOfNewOrder($order))->handle();

        $this->assertDatabaseCount('admin_notifications', 1);
    }

    // -------------------------------------------------------------------------
    // TC-04  Newly created notification has read_at = null (unread)
    // -------------------------------------------------------------------------
    public function test_nt002_tc04_notification_is_unread_by_default(): void
    {
        Mail::fake();

        $user = $this->makeUser();
        $order = $this->makeOrder($user);

        (new NotifyAdminOfNewOrder($order))->handle();

        $this->assertDatabaseHas('admin_notifications', [
            'order_id' => $order->id,
            'read_at' => null,
        ]);
    }

    // -------------------------------------------------------------------------
    // TC-05  Notification stores correct order_id
    // -------------------------------------------------------------------------
    public function test_nt002_tc05_notification_stores_correct_order_id(): void
    {
        Mail::fake();

        $user = $this->makeUser();
        $order = $this->makeOrder($user);

        (new NotifyAdminOfNewOrder($order))->handle();

        $notification = AdminNotification::first();
        $this->assertEquals($order->id, $notification->order_id);
    }

    // -------------------------------------------------------------------------
    // TC-06  Notification message contains order id
    // -------------------------------------------------------------------------
    public function test_nt002_tc06_notification_message_contains_order_id(): void
    {
        Mail::fake();

        $user = $this->makeUser();
        $order = $this->makeOrder($user);

        (new NotifyAdminOfNewOrder($order))->handle();

        $notification = AdminNotification::first();
        $this->assertStringContainsString((string) $order->id, $notification->message);
    }

    // -------------------------------------------------------------------------
    // TC-07  Job sends NewOrderAdminMail to admin users
    // -------------------------------------------------------------------------
    public function test_nt002_tc07_job_sends_mail_to_admin_users(): void
    {
        Mail::fake();

        $admin = $this->makeAdmin();
        $user = $this->makeUser();
        $order = $this->makeOrder($user);

        (new NotifyAdminOfNewOrder($order))->handle();

        Mail::assertSent(
            NewOrderAdminMail::class,
            fn($mail) =>
            $mail->hasTo($admin->email)
        );
    }

    // -------------------------------------------------------------------------
    // TC-08  GET /admin/notifications returns 200 JSON for admin
    // -------------------------------------------------------------------------
    public function test_nt002_tc08_notifications_endpoint_returns_json_for_admin(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->getJson(route('admin.notifications.index'))
            ->assertStatus(200)
            ->assertJsonStructure(['unread_count', 'notifications']);
    }

    // -------------------------------------------------------------------------
    // TC-09  JSON response includes notifications array
    // -------------------------------------------------------------------------
    public function test_nt002_tc09_json_has_notifications_array(): void
    {
        $admin = $this->makeAdmin();
        $user = $this->makeUser();
        $order = $this->makeOrder($user);

        AdminNotification::create([
            'order_id' => $order->id,
            'message' => "New order #{$order->id} received.",
        ]);

        $this->actingAs($admin)
            ->getJson(route('admin.notifications.index'))
            ->assertJsonCount(1, 'notifications');
    }

    // -------------------------------------------------------------------------
    // TC-10  unread_count reflects only unread notifications
    // -------------------------------------------------------------------------
    public function test_nt002_tc10_unread_count_reflects_only_unread(): void
    {
        $admin = $this->makeAdmin();
        $user = $this->makeUser();
        $order = $this->makeOrder($user);

        AdminNotification::create(['order_id' => $order->id, 'message' => 'Notif 1', 'read_at' => null]);
        AdminNotification::create(['order_id' => $order->id, 'message' => 'Notif 2', 'read_at' => now()]);

        $this->actingAs($admin)
            ->getJson(route('admin.notifications.index'))
            ->assertJson(['unread_count' => 1]);
    }

    // -------------------------------------------------------------------------
    // TC-11  Non-admin gets 403 on notifications endpoint
    // -------------------------------------------------------------------------
    public function test_nt002_tc11_non_admin_gets_403(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->getJson(route('admin.notifications.index'))
            ->assertStatus(403);
    }

    // -------------------------------------------------------------------------
    // TC-12  Guest is redirected from notifications endpoint
    // -------------------------------------------------------------------------
    public function test_nt002_tc12_guest_redirected_from_notifications(): void
    {
        $this->get(route('admin.notifications.index'))
            ->assertRedirect();
    }

    // -------------------------------------------------------------------------
    // TC-13  Admin can mark a single notification as read
    // -------------------------------------------------------------------------
    public function test_nt002_tc13_admin_can_mark_notification_as_read(): void
    {
        $admin = $this->makeAdmin();
        $user = $this->makeUser();
        $order = $this->makeOrder($user);

        $notification = AdminNotification::create([
            'order_id' => $order->id,
            'message' => "New order #{$order->id} received.",
        ]);

        $this->actingAs($admin)
            ->patchJson(route('admin.notifications.read', $notification))
            ->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    // -------------------------------------------------------------------------
    // TC-14  After marking read, read_at is set
    // -------------------------------------------------------------------------
    public function test_nt002_tc14_after_mark_read_read_at_is_set(): void
    {
        $admin = $this->makeAdmin();
        $user = $this->makeUser();
        $order = $this->makeOrder($user);

        $notification = AdminNotification::create([
            'order_id' => $order->id,
            'message' => "New order #{$order->id} received.",
        ]);

        $this->actingAs($admin)
            ->patchJson(route('admin.notifications.read', $notification));

        $this->assertNotNull($notification->fresh()->read_at);
    }

    // -------------------------------------------------------------------------
    // TC-15  Admin can mark all notifications as read at once
    // -------------------------------------------------------------------------
    public function test_nt002_tc15_admin_can_mark_all_notifications_as_read(): void
    {
        $admin = $this->makeAdmin();
        $user = $this->makeUser();
        $order = $this->makeOrder($user);

        AdminNotification::create(['order_id' => $order->id, 'message' => 'Notif 1']);
        AdminNotification::create(['order_id' => $order->id, 'message' => 'Notif 2']);

        $this->actingAs($admin)
            ->patchJson(route('admin.notifications.read-all'))
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertEquals(0, AdminNotification::whereNull('read_at')->count());
    }
}
