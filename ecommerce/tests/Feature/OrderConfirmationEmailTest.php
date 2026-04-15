<?php

namespace Tests\Feature;

use App\Jobs\SendOrderConfirmationEmail;
use App\Mail\OrderConfirmation;
use App\Models\Order;
use App\Models\User;
use App\Services\PaymentServiceInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OrderConfirmationEmailTest extends TestCase
{
    use RefreshDatabase;

    // ─── helpers ──────────────────────────────────────────────────────────────

    private function makeFakeWebhookEvent(string $type, string $intentId): object
    {
        return json_decode(json_encode([
            'type' => $type,
            'data' => ['object' => ['id' => $intentId]],
        ]));
    }

    private function createOrder(User $user, string $method = 'standard'): Order
    {
        $order = Order::create([
            'user_id'                   => $user->id,
            'status'                    => 'pending',
            'subtotal'                  => 50.00,
            'shipping_cost'             => 5.00,
            'total'                     => 55.00,
            'shipping_method'           => $method,
            'shipping_label'            => $method === 'express' ? 'Express Shipping' : 'Standard Shipping',
            'address'                   => ['name' => 'Test User', 'city' => 'New York'],
            'stripe_payment_intent_id'  => 'pi_test_cp004',
            'stripe_client_secret'      => 'pi_test_secret',
        ]);

        $order->items()->create([
            'product_name' => 'Widget A',
            'quantity'     => 2,
            'unit_price'   => 25.00,
            'subtotal'     => 50.00,
        ]);

        return $order;
    }

    // ─── TC-01: succeeded webhook dispatches job ───────────────────────────────

    /** @test */
    public function cp004_webhook_payment_succeeded_dispatches_confirmation_job(): void
    {
        Queue::fake();

        $user  = User::factory()->create();
        $order = $this->createOrder($user);

        $fakeEvent = $this->makeFakeWebhookEvent('payment_intent.succeeded', 'pi_test_cp004');
        $this->mock(PaymentServiceInterface::class, fn ($m) =>
            $m->shouldReceive('constructWebhookEvent')->andReturn($fakeEvent)
        );

        $this->postJson(route('webhook.stripe'), [], ['Stripe-Signature' => 'fake'])
            ->assertStatus(200);

        Queue::assertPushed(SendOrderConfirmationEmail::class, fn ($job) =>
            $job->order->id === $order->id
        );
    }

    // ─── TC-02: payment_failed webhook does NOT dispatch job ──────────────────

    /** @test */
    public function cp004_webhook_payment_failed_does_not_dispatch_confirmation_job(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $this->createOrder($user);

        $fakeEvent = $this->makeFakeWebhookEvent('payment_intent.payment_failed', 'pi_test_cp004');
        $this->mock(PaymentServiceInterface::class, fn ($m) =>
            $m->shouldReceive('constructWebhookEvent')->andReturn($fakeEvent)
        );

        $this->postJson(route('webhook.stripe'), [], ['Stripe-Signature' => 'fake'])
            ->assertStatus(200);

        Queue::assertNotPushed(SendOrderConfirmationEmail::class);
    }

    // ─── TC-03: succeeded webhook with unknown intent does NOT dispatch ────────

    /** @test */
    public function cp004_webhook_unknown_intent_does_not_dispatch_job(): void
    {
        Queue::fake();

        User::factory()->create();

        $fakeEvent = $this->makeFakeWebhookEvent('payment_intent.succeeded', 'pi_unknown_id');
        $this->mock(PaymentServiceInterface::class, fn ($m) =>
            $m->shouldReceive('constructWebhookEvent')->andReturn($fakeEvent)
        );

        $this->postJson(route('webhook.stripe'), [], ['Stripe-Signature' => 'fake'])
            ->assertStatus(200);

        Queue::assertNotPushed(SendOrderConfirmationEmail::class);
    }

    // ─── TC-04: mailable has correct subject ──────────────────────────────────

    /** @test */
    public function cp004_mailable_has_correct_subject(): void
    {
        $user  = User::factory()->create();
        $order = $this->createOrder($user);
        $order->load(['user', 'items']);

        $mailable = new OrderConfirmation($order);

        $mailable->assertHasSubject('Order Confirmation #' . $order->id);
    }

    // ─── TC-05: mailable is addressed to the order user ───────────────────────

    /** @test */
    public function cp004_mailable_is_addressed_to_order_user(): void
    {
        Mail::fake();

        $user  = User::factory()->create(['email' => 'buyer@cp004.test']);
        $order = $this->createOrder($user);
        $order->load(['user', 'items']);

        Mail::to($user->email)->send(new OrderConfirmation($order));

        Mail::assertSent(OrderConfirmation::class, fn ($mail) =>
            $mail->hasTo('buyer@cp004.test')
        );
    }

    // ─── TC-06: email contains the order ID ───────────────────────────────────

    /** @test */
    public function cp004_email_contains_order_id(): void
    {
        $user  = User::factory()->create();
        $order = $this->createOrder($user);
        $order->load(['user', 'items']);

        (new OrderConfirmation($order))->assertSeeInHtml((string) $order->id);
    }

    // ─── TC-07: email contains item product name ──────────────────────────────

    /** @test */
    public function cp004_email_contains_item_product_name(): void
    {
        $user  = User::factory()->create();
        $order = $this->createOrder($user);
        $order->load(['user', 'items']);

        (new OrderConfirmation($order))->assertSeeInHtml('Widget A');
    }

    // ─── TC-08: email contains order total ────────────────────────────────────

    /** @test */
    public function cp004_email_contains_order_total(): void
    {
        $user  = User::factory()->create();
        $order = $this->createOrder($user);
        $order->load(['user', 'items']);

        (new OrderConfirmation($order))->assertSeeInHtml('55.00');
    }

    // ─── TC-09: email shows standard estimated delivery ───────────────────────

    /** @test */
    public function cp004_email_shows_standard_estimated_delivery(): void
    {
        $user  = User::factory()->create();
        $order = $this->createOrder($user, 'standard');
        $order->load(['user', 'items']);

        (new OrderConfirmation($order))->assertSeeInHtml('5');
        (new OrderConfirmation($order))->assertSeeInHtml('business days');
    }

    // ─── TC-10: email shows express estimated delivery ────────────────────────

    /** @test */
    public function cp004_email_shows_express_estimated_delivery(): void
    {
        $user  = User::factory()->create();
        $order = $this->createOrder($user, 'express');
        $order->load(['user', 'items']);

        (new OrderConfirmation($order))->assertSeeInHtml('1');
        (new OrderConfirmation($order))->assertSeeInHtml('business days');
    }

    // ─── TC-11: job implements ShouldQueue ────────────────────────────────────

    /** @test */
    public function cp004_job_implements_should_queue(): void
    {
        $user  = User::factory()->create();
        $order = $this->createOrder($user);

        $job = new SendOrderConfirmationEmail($order);

        $this->assertInstanceOf(ShouldQueue::class, $job);
    }

    // ─── TC-12: job handle() sends the mailable ───────────────────────────────

    /** @test */
    public function cp004_job_handle_sends_order_confirmation_mail(): void
    {
        Mail::fake();

        $user  = User::factory()->create(['email' => 'handle@cp004.test']);
        $order = $this->createOrder($user);

        $job = new SendOrderConfirmationEmail($order);
        $job->handle();

        Mail::assertSent(OrderConfirmation::class, fn ($mail) =>
            $mail->hasTo('handle@cp004.test')
        );
    }
}
