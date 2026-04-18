<?php

namespace Tests\Feature;

use App\Jobs\SendOrderConfirmationEmail;
use App\Jobs\SendOrderStatusChangedEmail;
use App\Mail\OrderConfirmation;
use App\Mail\OrderStatusChanged;
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
 * NT-001 — As a user, I want email notifications for order events so I stay informed.
 *
 * Acceptance criteria:
 *   - Triggers: order placed, status change, delivery
 *   - Uses Laravel Mailable + Queue
 */
class OrderNotificationTest extends TestCase
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

    private function makeOrder(User $user, array $state = []): Order
    {
        $order = Order::factory()->for($user)->create(array_merge([
            'status' => 'paid',
            'subtotal' => 50.00,
            'shipping_cost' => 5.00,
            'total' => 55.00,
            'shipping_method' => 'standard',
            'shipping_label' => 'Standard Shipping',
            'address' => [
                'name' => 'Test User',
                'address_line1' => '1 Main St',
                'city' => 'Springfield',
                'state' => 'IL',
                'postal_code' => '62701',
                'country' => 'US',
            ],
        ], $state));

        OrderItem::factory()->for($order)->create([
            'product_name' => 'Widget A',
            'quantity' => 1,
            'unit_price' => 50.00,
            'subtotal' => 50.00,
        ]);

        return $order;
    }

    // -------------------------------------------------------------------------
    // TC-01  SendOrderStatusChangedEmail implements ShouldQueue
    // -------------------------------------------------------------------------
    public function test_nt001_tc01_status_changed_job_implements_should_queue(): void
    {
        $user = $this->makeUser();
        $order = $this->makeOrder($user);
        $job = new SendOrderStatusChangedEmail($order);

        $this->assertInstanceOf(ShouldQueue::class, $job);
    }

    // -------------------------------------------------------------------------
    // TC-02  Status change to 'processing' dispatches SendOrderStatusChangedEmail
    // -------------------------------------------------------------------------
    public function test_nt001_tc02_processing_status_dispatches_job(): void
    {
        Queue::fake();

        $admin = $this->makeAdmin();
        $owner = $this->makeUser();
        $order = $this->makeOrder($owner, ['status' => 'paid']);

        $this->actingAs($admin)
            ->patch(route('admin.orders.status', $order), ['status' => 'processing'])
            ->assertRedirect();

        Queue::assertPushed(
            SendOrderStatusChangedEmail::class,
            fn($job) =>
            $job->order->id === $order->id
        );
    }

    // -------------------------------------------------------------------------
    // TC-03  Status change to 'shipped' dispatches SendOrderStatusChangedEmail
    // -------------------------------------------------------------------------
    public function test_nt001_tc03_shipped_status_dispatches_job(): void
    {
        Queue::fake();

        $admin = $this->makeAdmin();
        $owner = $this->makeUser();
        $order = $this->makeOrder($owner, ['status' => 'processing', 'processing_at' => now()]);

        $this->actingAs($admin)
            ->patch(route('admin.orders.status', $order), ['status' => 'shipped'])
            ->assertRedirect();

        Queue::assertPushed(
            SendOrderStatusChangedEmail::class,
            fn($job) =>
            $job->order->id === $order->id
        );
    }

    // -------------------------------------------------------------------------
    // TC-04  Status change to 'delivered' dispatches SendOrderStatusChangedEmail
    // -------------------------------------------------------------------------
    public function test_nt001_tc04_delivered_status_dispatches_job(): void
    {
        Queue::fake();

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

        Queue::assertPushed(
            SendOrderStatusChangedEmail::class,
            fn($job) =>
            $job->order->id === $order->id
        );
    }

    // -------------------------------------------------------------------------
    // TC-05  Status change to 'cancelled' dispatches SendOrderStatusChangedEmail
    // -------------------------------------------------------------------------
    public function test_nt001_tc05_cancelled_status_dispatches_job(): void
    {
        Queue::fake();

        $admin = $this->makeAdmin();
        $owner = $this->makeUser();
        $order = $this->makeOrder($owner, ['status' => 'paid']);

        $this->actingAs($admin)
            ->patch(route('admin.orders.status', $order), ['status' => 'cancelled'])
            ->assertRedirect();

        Queue::assertPushed(
            SendOrderStatusChangedEmail::class,
            fn($job) =>
            $job->order->id === $order->id
        );
    }

    // -------------------------------------------------------------------------
    // TC-06  SendOrderStatusChangedEmail job sends OrderStatusChanged mail to owner
    // -------------------------------------------------------------------------
    public function test_nt001_tc06_job_sends_status_changed_mail_to_order_owner(): void
    {
        Mail::fake();

        $owner = $this->makeUser();
        $order = $this->makeOrder($owner, ['status' => 'shipped']);

        (new SendOrderStatusChangedEmail($order))->handle();

        Mail::assertSent(
            OrderStatusChanged::class,
            fn($mail) =>
            $mail->hasTo($owner->email)
        );
    }

    // -------------------------------------------------------------------------
    // TC-07  OrderStatusChanged mailable subject contains order id
    // -------------------------------------------------------------------------
    public function test_nt001_tc07_status_changed_mailable_subject_contains_order_id(): void
    {
        $owner = $this->makeUser();
        $order = $this->makeOrder($owner, ['status' => 'shipped']);
        $mail = new OrderStatusChanged($order);
        $envelope = $mail->envelope();

        $this->assertStringContainsString((string) $order->id, $envelope->subject);
    }

    // -------------------------------------------------------------------------
    // TC-08  OrderStatusChanged mailable is addressed to order owner
    // -------------------------------------------------------------------------
    public function test_nt001_tc08_status_changed_mailable_addressed_to_owner(): void
    {
        Mail::fake();

        $owner = $this->makeUser();
        $order = $this->makeOrder($owner, ['status' => 'processing']);

        (new SendOrderStatusChangedEmail($order))->handle();

        Mail::assertSent(
            OrderStatusChanged::class,
            fn($mail) =>
            $mail->hasTo($owner->email)
        );
    }

    // -------------------------------------------------------------------------
    // TC-09  Status changed mail contains 'processing' status label
    // -------------------------------------------------------------------------
    public function test_nt001_tc09_mail_contains_processing_status_label(): void
    {
        Mail::fake();

        $owner = $this->makeUser();
        $order = $this->makeOrder($owner, ['status' => 'processing']);

        Mail::to($owner->email)->send(new OrderStatusChanged($order));

        Mail::assertSent(
            OrderStatusChanged::class,
            fn($mail) =>
            $mail->order->status === 'processing'
        );
    }

    // -------------------------------------------------------------------------
    // TC-10  Status changed mail for 'delivered' covers delivery trigger
    // -------------------------------------------------------------------------
    public function test_nt001_tc10_delivery_trigger_mail_is_sent(): void
    {
        Mail::fake();

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

        Mail::assertSent(
            OrderStatusChanged::class,
            fn($mail) =>
            $mail->order->id === $order->id && $mail->hasTo($owner->email)
        );
    }

    // -------------------------------------------------------------------------
    // TC-11  Delivery notification mail content references delivered status
    // -------------------------------------------------------------------------
    public function test_nt001_tc11_delivery_mail_references_delivered_status(): void
    {
        $owner = $this->makeUser();
        $order = $this->makeOrder($owner, ['status' => 'delivered']);
        $mail = new OrderStatusChanged($order);

        $content = $mail->content();
        $this->assertEquals('mail.order-status-changed', $content->view);
        $with = $content->with;
        $this->assertArrayHasKey('statusLabel', $with);
        $this->assertEquals('Delivered', $with['statusLabel']);
    }

    // -------------------------------------------------------------------------
    // TC-12  Cancelled status notification mail has correct label
    // -------------------------------------------------------------------------
    public function test_nt001_tc12_cancelled_mail_has_correct_label(): void
    {
        $owner = $this->makeUser();
        $order = $this->makeOrder($owner, ['status' => 'cancelled']);
        $mail = new OrderStatusChanged($order);

        $with = $mail->content()->with;
        $this->assertArrayHasKey('statusLabel', $with);
        $this->assertEquals('Cancelled', $with['statusLabel']);
    }

    // -------------------------------------------------------------------------
    // TC-13  SendOrderConfirmationEmail (order placed) implements ShouldQueue
    // -------------------------------------------------------------------------
    public function test_nt001_tc13_order_confirmation_job_implements_should_queue(): void
    {
        $user = $this->makeUser();
        $order = $this->makeOrder($user);
        $job = new SendOrderConfirmationEmail($order);

        $this->assertInstanceOf(ShouldQueue::class, $job);
    }

    // -------------------------------------------------------------------------
    // TC-14  Order placed webhook dispatches SendOrderConfirmationEmail job
    // -------------------------------------------------------------------------
    public function test_nt001_tc14_order_placed_webhook_dispatches_confirmation_job(): void
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
            'stripe_payment_intent_id' => 'pi_nt001_test',
            'stripe_client_secret' => 'pi_nt001_secret',
        ]);

        $fakeEvent = json_decode(json_encode([
            'type' => 'payment_intent.succeeded',
            'data' => ['object' => ['id' => 'pi_nt001_test']],
        ]));

        $this->mock(
            PaymentServiceInterface::class,
            fn($m) =>
            $m->shouldReceive('constructWebhookEvent')->andReturn($fakeEvent)
        );

        $this->postJson(route('webhook.stripe'), [], ['Stripe-Signature' => 'fake'])
            ->assertStatus(200);

        Queue::assertPushed(
            SendOrderConfirmationEmail::class,
            fn($job) =>
            $job->order->id === $order->id
        );
    }

    // -------------------------------------------------------------------------
    // TC-15  SendOrderConfirmationEmail job sends OrderConfirmation mail to owner
    // -------------------------------------------------------------------------
    public function test_nt001_tc15_confirmation_job_sends_mail_to_order_owner(): void
    {
        Mail::fake();

        $owner = $this->makeUser();
        $order = $this->makeOrder($owner);

        (new SendOrderConfirmationEmail($order))->handle();

        Mail::assertSent(
            OrderConfirmation::class,
            fn($mail) =>
            $mail->hasTo($owner->email)
        );
    }
}
