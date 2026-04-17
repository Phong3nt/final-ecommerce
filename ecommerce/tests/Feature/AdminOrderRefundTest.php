<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\RefundTransaction;
use App\Models\User;
use App\Services\PaymentServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * OM-005 — Admin processes refund on a cancelled order.
 *
 * Acceptance criteria:
 *   - Calls Payment Gateway refund API
 *   - Order status set to 'refunded'
 *   - Refund amount recorded in transaction log (refund_transactions)
 */
class AdminOrderRefundTest extends TestCase
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

    private function makeCancelledOrder(User $user, array $overrides = []): Order
    {
        return Order::factory()->for($user)->create(array_merge([
            'status' => 'cancelled',
            'subtotal' => 80.00,
            'shipping_cost' => 10.00,
            'total' => 90.00,
            'shipping_method' => 'standard',
            'shipping_label' => 'Standard Shipping',
            'stripe_payment_intent_id' => 'pi_test_refund_001',
            'address' => [
                'name' => 'Refund Tester',
                'address_line1' => '1 Refund Ave',
                'address_line2' => null,
                'city' => 'Refundville',
                'state' => 'CA',
                'postal_code' => '90001',
                'country' => 'US',
            ],
        ], $overrides));
    }

    private function mockPaymentRefund(string $returnRefundId = 're_test_refund_001'): void
    {
        $this->mock(PaymentServiceInterface::class, function ($mock) use ($returnRefundId) {
            $mock->shouldReceive('refund')
                ->once()
                ->andReturn($returnRefundId);
        });
    }

    private function mockPaymentNoCall(): void
    {
        $this->mock(PaymentServiceInterface::class, function ($mock) {
            $mock->shouldReceive('refund')->never();
        });
    }

    // TC-01: Guest is redirected to login when attempting to process a refund
    public function test_om005_tc01_guest_is_redirected_to_login(): void
    {
        $owner = $this->makeUser();
        $order = $this->makeCancelledOrder($owner);

        $this->post(route('admin.orders.refund', $order))
            ->assertRedirect(route('login'));
    }

    // TC-02: Regular user receives 403 when attempting to process a refund
    public function test_om005_tc02_non_admin_receives_403(): void
    {
        $user = $this->makeUser();
        $order = $this->makeCancelledOrder($user);

        $this->actingAs($user)
            ->post(route('admin.orders.refund', $order))
            ->assertForbidden();
    }

    // TC-03: Admin cannot refund a non-cancelled order (e.g. paid)
    public function test_om005_tc03_cannot_refund_non_cancelled_order(): void
    {
        $this->mockPaymentNoCall();

        $admin = $this->makeAdmin();
        $owner = $this->makeUser();
        $order = $this->makeCancelledOrder($owner, ['status' => 'paid']);

        $this->actingAs($admin)
            ->post(route('admin.orders.refund', $order))
            ->assertSessionHasErrors('order');

        $this->assertDatabaseMissing('refund_transactions', ['order_id' => $order->id]);
    }

    // TC-04: Admin cannot refund an order with no payment intent
    public function test_om005_tc04_cannot_refund_order_without_payment_intent(): void
    {
        $this->mockPaymentNoCall();

        $admin = $this->makeAdmin();
        $owner = $this->makeUser();
        $order = $this->makeCancelledOrder($owner, ['stripe_payment_intent_id' => null]);

        $this->actingAs($admin)
            ->post(route('admin.orders.refund', $order))
            ->assertSessionHasErrors('order');

        $this->assertDatabaseMissing('refund_transactions', ['order_id' => $order->id]);
    }

    // TC-05: Admin can process a refund — order status becomes 'refunded'
    public function test_om005_tc05_admin_can_process_refund_status_becomes_refunded(): void
    {
        $this->mockPaymentRefund();

        $admin = $this->makeAdmin();
        $owner = $this->makeUser();
        $order = $this->makeCancelledOrder($owner);

        $this->actingAs($admin)
            ->post(route('admin.orders.refund', $order))
            ->assertRedirect(route('admin.orders.show', $order));

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'refunded',
        ]);
    }

    // TC-06: Refund creates a RefundTransaction record with the correct amount
    public function test_om005_tc06_refund_creates_transaction_record_with_correct_amount(): void
    {
        $this->mockPaymentRefund();

        $admin = $this->makeAdmin();
        $owner = $this->makeUser();
        $order = $this->makeCancelledOrder($owner, ['total' => 90.00]);

        $this->actingAs($admin)
            ->post(route('admin.orders.refund', $order));

        $this->assertDatabaseHas('refund_transactions', [
            'order_id' => $order->id,
            'amount' => 90.00,
        ]);
    }

    // TC-07: RefundTransaction stores the Stripe refund ID
    public function test_om005_tc07_refund_transaction_stores_stripe_refund_id(): void
    {
        $this->mockPaymentRefund('re_stripe_id_xyz');

        $admin = $this->makeAdmin();
        $owner = $this->makeUser();
        $order = $this->makeCancelledOrder($owner);

        $this->actingAs($admin)
            ->post(route('admin.orders.refund', $order));

        $this->assertDatabaseHas('refund_transactions', [
            'order_id' => $order->id,
            'stripe_refund_id' => 're_stripe_id_xyz',
        ]);
    }

    // TC-08: Admin order show page displays Process Refund button for cancelled orders with payment intent
    public function test_om005_tc08_show_page_displays_refund_button_for_cancelled_order(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeUser();
        $order = $this->makeCancelledOrder($owner);

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Process Refund');
    }

    // TC-09: Show page does NOT show refund button for non-cancelled orders
    public function test_om005_tc09_show_page_hides_refund_button_for_non_cancelled_order(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeUser();
        $order = $this->makeCancelledOrder($owner, ['status' => 'paid']);

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertDontSee('Process Refund');
    }

    // TC-10: Successful refund redirects to order show page with success flash message
    public function test_om005_tc10_refund_redirects_with_success_message(): void
    {
        $this->mockPaymentRefund();

        $admin = $this->makeAdmin();
        $owner = $this->makeUser();
        $order = $this->makeCancelledOrder($owner);

        $this->actingAs($admin)
            ->post(route('admin.orders.refund', $order))
            ->assertRedirect(route('admin.orders.show', $order))
            ->assertSessionHas('success');
    }

    // TC-11: An already-refunded order cannot be refunded again
    public function test_om005_tc11_cannot_refund_already_refunded_order(): void
    {
        $this->mockPaymentNoCall();

        $admin = $this->makeAdmin();
        $owner = $this->makeUser();
        $order = $this->makeCancelledOrder($owner, ['status' => 'refunded']);

        $this->actingAs($admin)
            ->post(route('admin.orders.refund', $order))
            ->assertSessionHasErrors('order');

        $this->assertDatabaseMissing('refund_transactions', ['order_id' => $order->id]);
    }

    // TC-12: refunded_at timestamp is recorded on the order after refund
    public function test_om005_tc12_refunded_at_timestamp_is_set_on_order(): void
    {
        $this->mockPaymentRefund();

        $admin = $this->makeAdmin();
        $owner = $this->makeUser();
        $order = $this->makeCancelledOrder($owner);

        $this->actingAs($admin)
            ->post(route('admin.orders.refund', $order));

        $order->refresh();
        $this->assertNotNull($order->refunded_at);
    }

    // TC-13: Exactly one refund transaction is created per refund
    public function test_om005_tc13_exactly_one_transaction_record_created(): void
    {
        $this->mockPaymentRefund();

        $admin = $this->makeAdmin();
        $owner = $this->makeUser();
        $order = $this->makeCancelledOrder($owner);

        $this->actingAs($admin)
            ->post(route('admin.orders.refund', $order));

        $this->assertSame(1, RefundTransaction::where('order_id', $order->id)->count());
    }

    // TC-14: Show page displays refund transaction details after a refund
    public function test_om005_tc14_show_page_displays_refund_transaction_after_refund(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeUser();
        $order = $this->makeCancelledOrder($owner, ['status' => 'refunded']);

        RefundTransaction::create([
            'order_id' => $order->id,
            'amount' => 90.00,
            'stripe_refund_id' => 're_show_test_001',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Refund Transactions')
            ->assertSee('re_show_test_001');
    }

    // TC-15: Refund payment service is called with correct intent ID and amount in cents
    public function test_om005_tc15_payment_service_called_with_correct_intent_and_amount(): void
    {
        $this->mock(PaymentServiceInterface::class, function ($mock) {
            $mock->shouldReceive('refund')
                ->once()
                ->with('pi_test_refund_001', 9000) // 90.00 * 100
                ->andReturn('re_verify_001');
        });

        $admin = $this->makeAdmin();
        $owner = $this->makeUser();
        $order = $this->makeCancelledOrder($owner, [
            'total' => 90.00,
            'stripe_payment_intent_id' => 'pi_test_refund_001',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.orders.refund', $order));
    }
}
