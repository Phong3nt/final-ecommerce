<?php

namespace Tests\Feature;

use App\Models\AdminNotification;
use App\Models\Order;
use App\Models\RefundTransaction;
use App\Models\User;
use App\Services\PaymentServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * IMP-036 — Stripe refund webhook sync + double-refund guard.
 *
 * Covers:
 *   - charge.refunded webhook: updates order status, refunded_at, RefundTransaction, AdminNotification
 *   - charge.refunded for already-refunded order: no-op (no duplicate records)
 *   - charge.refunded with no payment_intent: graceful 200
 *   - Bad Stripe signature: 400
 *   - Admin double-refund (Stripe InvalidRequestException): clean error message, no propagation
 */
class StripeRefundWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'user',  'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function makeAdmin(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('admin');
        return $user;
    }

    private function makeUser(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('user');
        return $user;
    }

    private function makePaidOrder(User $user, string $piId = 'pi_imp036_test'): Order
    {
        return Order::factory()->for($user)->create([
            'status'                    => 'paid',
            'subtotal'                  => 50.00,
            'shipping_cost'             => 5.00,
            'total'                     => 55.00,
            'shipping_method'           => 'standard',
            'shipping_label'            => 'Standard Shipping',
            'address'                   => ['name' => 'Test User', 'city' => 'NY'],
            'stripe_payment_intent_id'  => $piId,
            'stripe_client_secret'      => $piId . '_secret_ci',
        ]);
    }

    private function makeCancelledOrder(User $user, string $piId = 'pi_imp036_cancel'): Order
    {
        return Order::factory()->for($user)->create([
            'status'                    => 'cancelled',
            'subtotal'                  => 40.00,
            'shipping_cost'             => 5.00,
            'total'                     => 45.00,
            'shipping_method'           => 'standard',
            'shipping_label'            => 'Standard Shipping',
            'address'                   => ['name' => 'Test User', 'city' => 'NY'],
            'stripe_payment_intent_id'  => $piId,
            'stripe_client_secret'      => $piId . '_secret_ci',
        ]);
    }

    /** Build a fake charge.refunded Stripe event object. */
    private function fakeChargeRefundedEvent(
        string $piId,
        int $amountRefundedCents = 5500,
        string $refundId = 're_imp036_test'
    ): object {
        return json_decode(json_encode([
            'type' => 'charge.refunded',
            'data' => [
                'object' => [
                    'id'              => 'ch_imp036_test',
                    'payment_intent'  => $piId,
                    'amount_refunded' => $amountRefundedCents,
                    'refunds'         => ['data' => [['id' => $refundId]]],
                ],
            ],
        ]));
    }

    /** Mock PaymentServiceInterface so constructWebhookEvent returns a preset event. */
    private function mockWebhook(object $fakeEvent): void
    {
        $this->mock(
            PaymentServiceInterface::class,
            fn ($m) => $m->shouldReceive('constructWebhookEvent')->andReturn($fakeEvent)
        );
    }

    // ── TC-01 ─────────────────────────────────────────────────────────────────

    /**
     * TC-01: charge.refunded webhook sets order status to 'refunded' and records refunded_at.
     */
    public function test_imp036_tc01_charge_refunded_updates_order_status_and_refunded_at(): void
    {
        $user  = $this->makeUser();
        $order = $this->makePaidOrder($user, 'pi_tc01');

        $this->mockWebhook($this->fakeChargeRefundedEvent('pi_tc01', 5500));

        $this->postJson(route('webhook.stripe'), [], ['Stripe-Signature' => 'fake'])
            ->assertStatus(200);

        $order->refresh();
        $this->assertSame('refunded', $order->status);
        $this->assertNotNull($order->refunded_at);
    }

    // ── TC-02 ─────────────────────────────────────────────────────────────────

    /**
     * TC-02: charge.refunded webhook creates a RefundTransaction record with correct amount.
     */
    public function test_imp036_tc02_charge_refunded_creates_refund_transaction(): void
    {
        $user  = $this->makeUser();
        $order = $this->makePaidOrder($user, 'pi_tc02');

        $this->mockWebhook($this->fakeChargeRefundedEvent('pi_tc02', 5500, 're_tc02'));

        $this->postJson(route('webhook.stripe'), [], ['Stripe-Signature' => 'fake'])
            ->assertStatus(200);

        $this->assertDatabaseHas('refund_transactions', [
            'order_id'        => $order->id,
            'stripe_refund_id' => 're_tc02',
        ]);

        $tx = RefundTransaction::where('order_id', $order->id)->first();
        $this->assertNotNull($tx);
        $this->assertEqualsWithDelta(55.00, $tx->amount, 0.01);
    }

    // ── TC-03 ─────────────────────────────────────────────────────────────────

    /**
     * TC-03: charge.refunded webhook creates an AdminNotification for external refund.
     */
    public function test_imp036_tc03_charge_refunded_creates_admin_notification(): void
    {
        $user  = $this->makeUser();
        $order = $this->makePaidOrder($user, 'pi_tc03');

        $this->mockWebhook($this->fakeChargeRefundedEvent('pi_tc03'));

        $this->postJson(route('webhook.stripe'), [], ['Stripe-Signature' => 'fake'])
            ->assertStatus(200);

        $this->assertDatabaseHas('admin_notifications', [
            'order_id' => $order->id,
        ]);

        $notification = AdminNotification::where('order_id', $order->id)->first();
        $this->assertNotNull($notification);
        $this->assertStringContainsString("Order #{$order->id}", $notification->message);
    }

    // ── TC-04 ─────────────────────────────────────────────────────────────────

    /**
     * TC-04: charge.refunded for an already-refunded order is a no-op
     * (no duplicate AdminNotification, no new RefundTransaction).
     */
    public function test_imp036_tc04_already_refunded_order_is_no_op(): void
    {
        $user  = $this->makeUser();
        $order = Order::factory()->for($user)->create([
            'status'                   => 'refunded',
            'refunded_at'              => now()->subMinutes(10),
            'subtotal'                 => 50.00,
            'shipping_cost'            => 5.00,
            'total'                    => 55.00,
            'shipping_method'          => 'standard',
            'shipping_label'           => 'Standard Shipping',
            'address'                  => ['name' => 'Test', 'city' => 'NY'],
            'stripe_payment_intent_id' => 'pi_tc04',
            'stripe_client_secret'     => 'pi_tc04_secret',
        ]);

        $this->mockWebhook($this->fakeChargeRefundedEvent('pi_tc04'));

        $this->postJson(route('webhook.stripe'), [], ['Stripe-Signature' => 'fake'])
            ->assertStatus(200);

        $this->assertDatabaseCount('admin_notifications', 0);
        $this->assertDatabaseCount('refund_transactions', 0);
        // Status must remain 'refunded', not be re-written
        $this->assertSame('refunded', $order->fresh()->status);
    }

    // ── TC-05 ─────────────────────────────────────────────────────────────────

    /**
     * TC-05: charge.refunded with no payment_intent field returns 200 gracefully
     * (Stripe charges that are not PI-based should not cause errors).
     */
    public function test_imp036_tc05_charge_with_no_payment_intent_returns_200(): void
    {
        $fakeEvent = json_decode(json_encode([
            'type' => 'charge.refunded',
            'data' => [
                'object' => [
                    'id'              => 'ch_no_pi',
                    'payment_intent'  => null,
                    'amount_refunded' => 1000,
                    'refunds'         => ['data' => [['id' => 're_no_pi']]],
                ],
            ],
        ]));

        $this->mock(
            PaymentServiceInterface::class,
            fn ($m) => $m->shouldReceive('constructWebhookEvent')->andReturn($fakeEvent)
        );

        $this->postJson(route('webhook.stripe'), [], ['Stripe-Signature' => 'fake'])
            ->assertStatus(200);

        $this->assertDatabaseCount('admin_notifications', 0);
        $this->assertDatabaseCount('refund_transactions', 0);
    }

    // ── TC-06 ─────────────────────────────────────────────────────────────────

    /**
     * TC-06: Invalid Stripe signature returns 400.
     */
    public function test_imp036_tc06_invalid_signature_returns_400(): void
    {
        $this->mock(
            PaymentServiceInterface::class,
            fn ($m) => $m->shouldReceive('constructWebhookEvent')
                ->andThrow(new \Stripe\Exception\SignatureVerificationException('bad', 0))
        );

        $this->postJson(route('webhook.stripe'), [], ['Stripe-Signature' => 'bad_sig'])
            ->assertStatus(400);
    }

    // ── TC-07 ─────────────────────────────────────────────────────────────────

    /**
     * TC-07: Admin double-refund (charge_already_refunded Stripe code) returns
     * a clean human-readable error message, not a raw Stripe exception string.
     */
    public function test_imp036_tc07_admin_double_refund_returns_clean_error_message(): void
    {
        $admin = $this->makeAdmin();
        $user  = $this->makeUser();
        $order = $this->makeCancelledOrder($user, 'pi_tc07');

        $stripeException = \Stripe\Exception\InvalidRequestException::factory(
            'This charge has already been refunded.',
            400,
            null,
            ['error' => [
                'type'    => 'invalid_request_error',
                'code'    => 'charge_already_refunded',
                'message' => 'This charge has already been refunded.',
            ]],
            null,
            'charge_already_refunded'
        );

        $this->mock(
            PaymentServiceInterface::class,
            fn ($m) => $m->shouldReceive('refund')->andThrow($stripeException)
        );

        $response = $this->actingAs($admin)
            ->post(route('admin.orders.refund', $order));

        $response->assertRedirect();
        $response->assertSessionHasErrors(['order']);

        $errors = session('errors');
        $msg = $errors->first('order');
        $this->assertStringContainsString('already been fully refunded', $msg);
        // Must NOT contain raw Stripe exception class name
        $this->assertStringNotContainsString('Stripe\\Exception', $msg);
    }

    // ── TC-08 ─────────────────────────────────────────────────────────────────

    /**
     * TC-08: Admin double-refund — exception is caught and no exception propagates
     * (response is a redirect, not a 500).
     */
    public function test_imp036_tc08_admin_double_refund_no_exception_propagated(): void
    {
        $admin = $this->makeAdmin();
        $user  = $this->makeUser();
        $order = $this->makeCancelledOrder($user, 'pi_tc08');

        $stripeException = \Stripe\Exception\InvalidRequestException::factory(
            'This charge has already been refunded.',
            400,
            null,
            ['error' => [
                'type'    => 'invalid_request_error',
                'code'    => 'charge_already_refunded',
                'message' => 'This charge has already been refunded.',
            ]],
            null,
            'charge_already_refunded'
        );

        $this->mock(
            PaymentServiceInterface::class,
            fn ($m) => $m->shouldReceive('refund')->andThrow($stripeException)
        );

        $response = $this->actingAs($admin)
            ->post(route('admin.orders.refund', $order));

        // Must be a redirect (3xx), never a 500
        $this->assertLessThan(500, $response->getStatusCode());
        $this->assertGreaterThanOrEqual(300, $response->getStatusCode());
    }

    // ── TC-09 ─────────────────────────────────────────────────────────────────

    /**
     * TC-09: Admin-initiated refund (via our UI) already marks order as 'refunded'.
     * When Stripe subsequently fires charge.refunded, the webhook is a no-op
     * (no second AdminNotification created, no duplicate RefundTransaction).
     */
    public function test_imp036_tc09_webhook_after_admin_refund_is_no_op(): void
    {
        $user  = $this->makeUser();
        $order = Order::factory()->for($user)->create([
            'status'                   => 'refunded',
            'refunded_at'              => now()->subSeconds(5),
            'subtotal'                 => 50.00,
            'shipping_cost'            => 5.00,
            'total'                    => 55.00,
            'shipping_method'          => 'standard',
            'shipping_label'           => 'Standard Shipping',
            'address'                  => ['name' => 'Test', 'city' => 'NY'],
            'stripe_payment_intent_id' => 'pi_tc09',
            'stripe_client_secret'     => 'pi_tc09_secret',
        ]);

        // Simulate the admin-triggered RefundTransaction already existing
        $order->refundTransactions()->create([
            'amount'           => 55.00,
            'stripe_refund_id' => 're_admin_tc09',
        ]);

        // Webhook fires (Stripe sends charge.refunded after admin-initiated refund)
        $this->mockWebhook($this->fakeChargeRefundedEvent('pi_tc09', 5500, 're_admin_tc09'));

        $this->postJson(route('webhook.stripe'), [], ['Stripe-Signature' => 'fake'])
            ->assertStatus(200);

        // Still only the one admin-triggered transaction — no duplicate
        $this->assertDatabaseCount('refund_transactions', 1);
        // No AdminNotification created (admin did it, not external)
        $this->assertDatabaseCount('admin_notifications', 0);
    }

    // ── TC-10 ─────────────────────────────────────────────────────────────────

    /**
     * TC-10: charge.refunded admin notification message contains the order ID
     * and a dollar amount.
     */
    public function test_imp036_tc10_notification_message_contains_order_id_and_amount(): void
    {
        $user  = $this->makeUser();
        $order = $this->makePaidOrder($user, 'pi_tc10');

        // 4250 cents = $42.50
        $this->mockWebhook($this->fakeChargeRefundedEvent('pi_tc10', 4250));

        $this->postJson(route('webhook.stripe'), [], ['Stripe-Signature' => 'fake'])
            ->assertStatus(200);

        $notification = AdminNotification::where('order_id', $order->id)->first();
        $this->assertNotNull($notification);
        $this->assertStringContainsString("Order #{$order->id}", $notification->message);
        $this->assertStringContainsString('42.50', $notification->message);
    }
}
