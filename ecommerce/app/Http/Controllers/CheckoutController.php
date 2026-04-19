<?php

namespace App\Http\Controllers;

use App\Jobs\NotifyAdminOfNewOrder;
use App\Jobs\SendOrderConfirmationEmail;
use App\Models\Order;
use App\Models\UserAddress;
use App\Services\PaymentServiceInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CheckoutController extends Controller
{
    /**
     * Available shipping methods with cost and estimated delivery.
     */
    private const SHIPPING_OPTIONS = [
        'standard' => ['label' => 'Standard Shipping', 'cost' => 5.00, 'days' => '5–7 business days'],
        'express' => ['label' => 'Express Shipping', 'cost' => 15.00, 'days' => '1–2 business days'],
    ];

    public function __construct(private PaymentServiceInterface $paymentService)
    {
    }

    /**
     * SC-005: Compute coupon discount for the given subtotal.
     * Returns 0.0 when no coupon is provided.
     */
    private static function computeCouponDiscount(float $subtotal, ?array $coupon): float
    {
        if (!$coupon) {
            return 0.0;
        }
        if ($coupon['type'] === 'percent') {
            return round($subtotal * $coupon['value'] / 100, 2);
        }
        return min((float) $coupon['value'], $subtotal);
    }

    /**
     * IMP-003: Show the one-page checkout view.
     * Combines address, shipping, and payment steps in a single page.
     */
    public function showCheckout(): View|RedirectResponse
    {
        $cart = session('cart', []);
        $addresses = auth()->user()->addresses;
        $subtotal = collect($cart)->sum(fn($item) => $item['price'] * $item['quantity']);

        return view('checkout.index', [
            'cart'            => $cart,
            'addresses'       => $addresses,
            'shippingOptions' => self::SHIPPING_OPTIONS,
            'subtotal'        => $subtotal,
        ]);
    }

    /**
     * IMP-003: Save address + shipping selection to session in a single AJAX call.
     * Returns JSON with updated totals so the frontend can refresh the summary.
     * Accepts either an existing address_id or a new address payload.
     */
    public function storeSession(Request $request): JsonResponse
    {
        $user = auth()->user();

        if ($request->filled('address_id')) {
            // Use existing saved address
            $address = UserAddress::where('id', $request->input('address_id'))
                ->where('user_id', $user->id)
                ->firstOrFail();
        } else {
            // Validate and save new address
            $data = $request->validate([
                'name'          => 'required|string|max:255',
                'address_line1' => 'required|string|max:255',
                'address_line2' => 'nullable|string|max:255',
                'city'          => 'required|string|max:100',
                'state'         => 'required|string|max:100',
                'postal_code'   => 'required|string|max:20',
                'country'       => 'required|string|max:100',
            ]);

            $address = $user->addresses()->create($data);
        }

        // Validate shipping method
        $request->validate([
            'method' => ['required', 'in:' . implode(',', array_keys(self::SHIPPING_OPTIONS))],
        ]);

        $method = $request->input('method');
        $option = self::SHIPPING_OPTIONS[$method];

        session()->put('checkout.address', [
            'id'            => $address->id,
            'name'          => $address->name,
            'address_line1' => $address->address_line1,
            'address_line2' => $address->address_line2,
            'city'          => $address->city,
            'state'         => $address->state,
            'postal_code'   => $address->postal_code,
            'country'       => $address->country,
        ]);

        session()->put('checkout.shipping', [
            'method' => $method,
            'label'  => $option['label'],
            'cost'   => $option['cost'],
        ]);

        $cart     = session('cart', []);
        $coupon   = session('checkout.coupon');
        $subtotal = collect($cart)->sum(fn($item) => $item['price'] * $item['quantity']);
        $discount = self::computeCouponDiscount($subtotal, $coupon);
        $total    = $subtotal + $option['cost'] - $discount;

        return response()->json([
            'ok'            => true,
            'subtotal'      => $subtotal,
            'shipping_cost' => $option['cost'],
            'discount'      => $discount,
            'total'         => $total,
        ]);
    }

    /**
     * CP-001: Show the checkout address step.
     * Auth users see their saved addresses + a new address form.
     */
    public function showAddress(): View
    {
        $addresses = auth()->user()->addresses;

        return view('checkout.address', compact('addresses'));
    }

    /**
     * CP-001: Store the chosen/entered shipping address in session.
     * If an existing address_id is selected, use that address.
     * Otherwise validate the new address fields, persist it for the user,
     * and store it in the checkout session.
     */
    public function storeAddress(Request $request): RedirectResponse
    {
        $user = auth()->user();

        if ($request->filled('address_id')) {
            // Using an existing saved address
            $address = UserAddress::where('id', $request->input('address_id'))
                ->where('user_id', $user->id)
                ->firstOrFail();
        } else {
            // Validate and create a new address
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'address_line1' => 'required|string|max:255',
                'address_line2' => 'nullable|string|max:255',
                'city' => 'required|string|max:100',
                'state' => 'required|string|max:100',
                'postal_code' => 'required|string|max:20',
                'country' => 'required|string|max:100',
            ]);

            $address = $user->addresses()->create($data);
        }

        session()->put('checkout.address', [
            'id' => $address->id,
            'name' => $address->name,
            'address_line1' => $address->address_line1,
            'address_line2' => $address->address_line2,
            'city' => $address->city,
            'state' => $address->state,
            'postal_code' => $address->postal_code,
            'country' => $address->country,
        ]);

        return redirect()->route('checkout.shipping');
    }

    /**
     * CP-002: Show the checkout shipping method step.
     * Requires checkout.address in session; otherwise redirect back.
     */
    public function showShipping(): View|RedirectResponse
    {
        if (!session()->has('checkout.address')) {
            return redirect()->route('checkout.address')
                ->with('error', 'Please provide a shipping address first.');
        }

        $cart = session('cart', []);
        $orderTotal = collect($cart)->sum(fn($item) => $item['price'] * $item['quantity']);

        return view('checkout.shipping', [
            'shippingOptions' => self::SHIPPING_OPTIONS,
            'orderTotal' => $orderTotal,
            'selected' => session('checkout.shipping.method'),
        ]);
    }

    /**
     * CP-002: Store the chosen shipping method in session.
     * Stores method, label, and cost, then proceeds to checkout review.
     */
    public function storeShipping(Request $request): RedirectResponse
    {
        $request->validate([
            'method' => ['required', 'in:' . implode(',', array_keys(self::SHIPPING_OPTIONS))],
        ]);

        $method = $request->input('method');
        $option = self::SHIPPING_OPTIONS[$method];

        session()->put('checkout.shipping', [
            'method' => $method,
            'label' => $option['label'],
            'cost' => $option['cost'],
        ]);

        return redirect()->route('checkout.review');
    }

    /**
     * CP-003: Show the order review page.
     * Requires both checkout.address and checkout.shipping in session.
     */
    public function showReview(): View|RedirectResponse
    {
        if (!session()->has('checkout.address')) {
            return redirect()->route('checkout.address')
                ->with('error', 'Please provide a shipping address first.');
        }

        if (!session()->has('checkout.shipping')) {
            return redirect()->route('checkout.shipping')
                ->with('error', 'Please choose a shipping method first.');
        }

        $cart = session('cart', []);
        $address = session('checkout.address');
        $shipping = session('checkout.shipping');
        $coupon = session('checkout.coupon');

        $subtotal = collect($cart)->sum(fn($item) => $item['price'] * $item['quantity']);
        $discount = self::computeCouponDiscount($subtotal, $coupon);
        $total = $subtotal + $shipping['cost'] - $discount;

        return view('checkout.review', compact('cart', 'address', 'shipping', 'subtotal', 'discount', 'coupon', 'total'));
    }

    /**
     * CP-003: Place the order and create a Stripe PaymentIntent.
     * Creates Order + OrderItems (status=pending), returns JSON {client_secret, order_id}.
     * Card data is never sent to this server — tokenization happens via Stripe.js on the frontend.
     */
    public function placeOrder(Request $request): JsonResponse|RedirectResponse
    {
        if (!session()->has('checkout.address') || !session()->has('checkout.shipping')) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Checkout session expired.'], 422);
            }
            return redirect()->route('checkout.address');
        }

        $cart = session('cart', []);
        $address = session('checkout.address');
        $shipping = session('checkout.shipping');
        $coupon = session('checkout.coupon');
        $user = auth()->user();

        $subtotal = collect($cart)->sum(fn($item) => $item['price'] * $item['quantity']);
        $discount = self::computeCouponDiscount($subtotal, $coupon);
        $total = $subtotal + $shipping['cost'] - $discount;

        // Create the order record (pending — awaiting payment confirmation)
        $order = Order::create([
            'user_id' => $user->id,
            'status' => 'pending',
            'subtotal' => $subtotal,
            'shipping_cost' => $shipping['cost'],
            'total' => $total,
            'shipping_method' => $shipping['method'],
            'shipping_label' => $shipping['label'],
            'coupon_code' => $coupon ? $coupon['code'] : null,
            'discount_amount' => $discount,
            'address' => $address,
        ]);

        // Persist order line items (snapshot of cart at time of order)
        foreach ($cart as $productId => $item) {
            $order->items()->create([
                'product_id' => $productId,
                'product_name' => $item['name'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['price'],
                'subtotal' => $item['price'] * $item['quantity'],
            ]);
        }

        // Create Stripe PaymentIntent server-side (card never touches our server)
        $amountCents = (int) round($total * 100);
        $intent = $this->paymentService->createPaymentIntent($amountCents, 'usd', [
            'order_id' => (string) $order->id,
            'user_id' => (string) $user->id,
        ]);

        // Persist the intent ID + client_secret (client_secret is needed by Stripe.js)
        $order->update([
            'stripe_payment_intent_id' => $intent['id'],
            'stripe_client_secret' => $intent['client_secret'],
        ]);

        return response()->json([
            'client_secret' => $intent['client_secret'],
            'order_id' => $order->id,
        ]);
    }

    /**
     * CP-003: Handle Stripe webhook events.
     * Verifies the webhook signature, then processes payment_intent.succeeded
     * and payment_intent.payment_failed to update order status.
     * Card data is never stored here — only event metadata.
     */
    public function handleWebhook(Request $request): Response
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature', '');
        $secret = config('services.stripe.webhook_secret');

        try {
            $event = $this->paymentService->constructWebhookEvent($payload, $sigHeader, $secret);
        } catch (\UnexpectedValueException | \Stripe\Exception\SignatureVerificationException $e) {
            return response('Invalid payload or signature.', 400);
        }

        $intentId = $event->data->object->id ?? null;

        if ($intentId) {
            $order = Order::where('stripe_payment_intent_id', $intentId)->first();

            if ($order) {
                if ($event->type === 'payment_intent.succeeded') {
                    $order->update(['status' => 'paid']);
                    SendOrderConfirmationEmail::dispatch($order);
                    NotifyAdminOfNewOrder::dispatch($order);
                } elseif ($event->type === 'payment_intent.payment_failed') {
                    $order->update(['status' => 'failed']);
                }
            }
        }

        return response('OK', 200);
    }

    /**
     * CP-005: Show the payment success or failure page.
     * Stripe redirects here with ?payment_intent=pi_...&redirect_status=succeeded|failed|...
     * We look up the order by intent ID (scoped to the authenticated user) and render
     * the appropriate view. The checkout session is cleared on success.
     */
    public function showSuccess(Request $request): View|RedirectResponse
    {
        $intentId = $request->query('payment_intent');
        $redirectStatus = $request->query('redirect_status', '');

        if (!$intentId) {
            return redirect()->route('checkout.address');
        }

        $order = Order::where('stripe_payment_intent_id', $intentId)
            ->where('user_id', auth()->id())
            ->with('items')
            ->first();

        if (!$order) {
            return redirect()->route('checkout.address');
        }

        if ($redirectStatus === 'succeeded') {
            session()->forget(['checkout.address', 'checkout.shipping', 'checkout.coupon', 'cart']);
            return view('checkout.success', compact('order'));
        }

        return view('checkout.failed', ['order' => $order, 'status' => $redirectStatus]);
    }
}
