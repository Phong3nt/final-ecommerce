<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ShipmentSimulatorJob;
use App\Models\AdminNotification;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * IMP-048: Shipping Simulator [DEMO]
 *
 * Admin-only sandbox — demo orders are flagged with is_demo=true and are
 * excluded from all revenue / analytics queries.  No stock is decremented,
 * no review is allowed, and real revenue figures are never affected.
 */
class DemoController extends Controller
{
    // ── Sandbox page ────────────────────────────────────────────────────────

    public function index(): View
    {
        $products = Product::where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'price', 'image', 'sku']);

        $recentDemoOrders = Order::where('is_demo', true)
            ->with('user')
            ->latest()
            ->limit(20)
            ->get();

        return view('admin.demo.index', compact('products', 'recentDemoOrders'));
    }

    // ── Simulate an order ────────────────────────────────────────────────────

    public function simulate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity'   => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        $product  = Product::findOrFail($validated['product_id']);
        $qty      = (int) $validated['quantity'];
        $subtotal = round($product->price * $qty, 2);
        $shipping = 5.00;
        $total    = round($subtotal + $shipping, 2);

        // Create demo order — no stock change, no revenue impact
        $order = Order::create([
            'user_id'        => $request->user()->id,
            'status'         => 'paid',
            'subtotal'       => $subtotal,
            'shipping_cost'  => $shipping,
            'total'          => $total,
            'shipping_method' => 'standard',
            'shipping_label' => 'Standard Shipping',
            'address'        => [
                'name'    => $request->user()->name,
                'line1'   => '[DEMO] 123 Sandbox Street',
                'city'    => '[DEMO] Demo City',
                'country' => 'VN',
            ],
            'processing_at' => now(),
            'is_demo'        => true,
            'ship_sim_status'    => 'payment_confirmed',
            'ship_sim_started_at' => now(),
            'ship_sim_updated_at' => now(),
        ]);

        // Create order item (no stock decrement)
        $order->items()->create([
            'product_id'   => $product->id,
            'product_name' => '[DEMO] ' . $product->name,
            'sku'          => $product->sku,
            'quantity'     => $qty,
            'unit_price'   => $product->price,
            'subtotal'     => $subtotal,
        ]);

        // Admin notification: payment confirmed
        AdminNotification::create([
            'order_id' => $order->id,
            'message'  => '[DEMO] Order #' . $order->id . ': Payment confirmed',
        ]);

        // User-facing toast notification via cache
        Cache::put(
            "demo_notify_{$order->user_id}_{$order->id}",
            '[DEMO] Your order #' . $order->id . ' payment has been confirmed',
            now()->addMinutes(10)
        );

        // Kick off the simulator — first hop: 5–10 s
        ShipmentSimulatorJob::dispatch($order->id, 'payment_confirmed')
            ->delay(now()->addSeconds(random_int(5, 10)));

        return redirect()->route('admin.demo.index')
            ->with('success', '[DEMO] Order #' . $order->id . ' created. Shipping simulation started!');
    }

    // ── Status polling endpoint (Alpine.js every 3 s) ───────────────────────

    public function status(Order $order): JsonResponse
    {
        abort_unless($order->is_demo, 404);

        $userId = request()->user()?->id;

        // Consume any pending user notification from cache
        $toastMsg = null;
        if ($userId && $order->user_id === $userId) {
            $cacheKey = "demo_notify_{$userId}_{$order->id}";
            $toastMsg = Cache::pull($cacheKey);
        }

        return response()->json([
            'ship_sim_status'    => $order->ship_sim_status,
            'ship_sim_updated_at' => $order->ship_sim_updated_at?->toISOString(),
            'order_status'       => $order->status,
            'toast'              => $toastMsg,
        ]);
    }
}
