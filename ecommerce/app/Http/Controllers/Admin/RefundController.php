<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\PaymentServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RefundController extends Controller
{
    public function __construct(private readonly PaymentServiceInterface $paymentService)
    {
    }

    public function store(Request $request, Order $order): RedirectResponse
    {
        if ($order->status !== 'cancelled') {
            return back()->withErrors(['order' => 'Only cancelled orders can be refunded.']);
        }

        if (!$order->stripe_payment_intent_id) {
            return back()->withErrors(['order' => 'No payment intent found for this order.']);
        }

        $amountCents = (int) round($order->total * 100);

        try {
            $stripeRefundId = $this->paymentService->refund($order->stripe_payment_intent_id, $amountCents);
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            // IMP-036: Return clean error instead of raw Stripe exception string
            $code = $e->getStripeCode() ?? '';
            $isAlreadyRefunded = $code === 'charge_already_refunded'
                || str_contains(strtolower($e->getMessage()), 'already been refunded');
            $cleanMessage = $isAlreadyRefunded
                ? 'This order has already been fully refunded.'
                : 'Refund could not be processed. Please check the Stripe dashboard.';
            return back()->withErrors(['order' => $cleanMessage]);
        }

        $order->update([
            'status' => 'refunded',
            'refunded_at' => now(),
        ]);

        $order->refundTransactions()->create([
            'amount' => $order->total,
            'stripe_refund_id' => $stripeRefundId,
        ]);

        return redirect()->route('admin.orders.show', $order)
            ->with('success', 'Refund of $' . number_format($order->total, 2) . ' processed successfully.');
    }
}
