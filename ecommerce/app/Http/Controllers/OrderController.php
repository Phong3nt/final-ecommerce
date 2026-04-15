<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Services\PaymentServiceInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class OrderController extends Controller
{
    public function __construct(private PaymentServiceInterface $paymentService)
    {
    }

    /**
     * OH-001: Show the authenticated user's order history.
     * Orders listed newest-first, paginated at 10 per page.
     */
    public function index(): View
    {
        $orders = auth()->user()
            ->orders()
            ->latest()
            ->paginate(10);

        return view('orders.index', compact('orders'));
    }

    /**
     * OH-002: Show a single past order's detail.
     * Scoped to the authenticated user — returns 403 for orders belonging to others.
     */
    public function show(Order $order): View|RedirectResponse
    {
        if ($order->user_id !== auth()->id()) {
            abort(403);
        }

        $order->load('items');

        return view('orders.show', compact('order'));
    }

    /**
     * OH-004: Cancel a pending order.
     * Only allowed when status is 'pending'. Cancels the Stripe PaymentIntent,
     * restores product stock, and marks the order as 'cancelled'.
     */
    public function cancel(Order $order): RedirectResponse
    {
        if ($order->user_id !== auth()->id()) {
            abort(403);
        }

        if ($order->status !== 'pending') {
            return redirect()->route('orders.show', $order)
                ->with('error', 'Only pending orders can be cancelled.');
        }

        // Cancel the Stripe PaymentIntent if present
        if ($order->stripe_payment_intent_id) {
            $this->paymentService->cancelPaymentIntent($order->stripe_payment_intent_id);
        }

        // Restore stock for each item that has a known product_id
        $order->load('items');
        foreach ($order->items as $item) {
            if ($item->product_id) {
                Product::where('id', $item->product_id)->increment('stock', $item->quantity);
            }
        }

        $order->update(['status' => 'cancelled']);

        return redirect()->route('orders.index')
            ->with('success', 'Order #' . $order->id . ' has been cancelled.');
    }
}

