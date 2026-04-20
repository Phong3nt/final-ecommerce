<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    // ---------------------------------------------------------------
    // IMP-015: DB cart persistence helpers for authenticated users
    // ---------------------------------------------------------------

    /**
     * IMP-015: Build a session-cart array from the user's DB cart rows.
     * Products that have been deleted are silently skipped.
     */
    private function loadDbCart(int $userId): array
    {
        return CartItem::where('user_id', $userId)
            ->with('product')
            ->get()
            ->filter(fn($item) => $item->product !== null)
            ->mapWithKeys(fn($item) => [
                $item->product_id => [
                    'product_id' => $item->product_id,
                    'name'       => $item->product->name,
                    'price'      => (float) $item->product->price,
                    'quantity'   => $item->quantity,
                    'slug'       => $item->product->slug,
                ],
            ])
            ->all();
    }

    /**
     * IMP-015: Merge the current session cart with the authenticated user's DB
     * cart, then persist the result to both the session and the DB.
     *
     * Merge rules:
     *  - Items in session but not DB  → inserted into DB.
     *  - Items in DB but not session  → added to session.
     *  - Items in both               → higher quantity wins; DB is updated if needed.
     *
     * This handles the "just-logged-in" case (session cart = guest items) as well
     * as the "other device" case (DB has items the current session doesn't).
     */
    private function mergeCartWithDb(): void
    {
        if (!auth()->check()) {
            return;
        }

        $userId      = auth()->id();
        $sessionCart = session()->get('cart', []);

        foreach ($sessionCart as $productId => $item) {
            $existing = CartItem::where('user_id', $userId)
                ->where('product_id', $productId)
                ->first();

            if ($existing) {
                // Keep the higher quantity across devices
                if ($item['quantity'] > $existing->quantity) {
                    $existing->update(['quantity' => $item['quantity']]);
                }
            } else {
                CartItem::create([
                    'user_id'    => $userId,
                    'product_id' => $productId,
                    'quantity'   => $item['quantity'],
                ]);
            }
        }

        // Reload full merged DB cart back into the session
        $merged = $this->loadDbCart($userId);
        session()->put('cart', $merged);
    }

    /**
     * SC-005: Compute the coupon discount for the given cart subtotal.
     * Returns 0.0 when no coupon is in session.
     */
    private static function computeDiscount(float $subtotal): float
    {
        $coupon = session('checkout.coupon');
        if (!$coupon) {
            return 0.0;
        }
        if ($coupon['type'] === 'percent') {
            return round($subtotal * $coupon['value'] / 100, 2);
        }
        // fixed — capped so discount never exceeds the subtotal
        return min((float) $coupon['value'], $subtotal);
    }

    /**
     * SC-002: Display the session cart contents.
     */
    public function index(): View
    {
        // IMP-015: merge session cart ↔ DB cart for authenticated users
        $this->mergeCartWithDb();

        $cart = session()->get('cart', []);
        $subtotal = array_sum(array_map(
            fn($item) => $item['price'] * $item['quantity'],
            $cart
        ));
        $discount = self::computeDiscount($subtotal);
        $total = $subtotal - $discount;
        $coupon = session('checkout.coupon');

        return view('cart.index', compact('cart', 'subtotal', 'discount', 'total', 'coupon'));
    }

    /**
     * SC-003: Update the quantity of a cart item.
     * Accepts AJAX/JSON (returns JSON) or regular form POST with _method=PATCH.
     * Quantity is bounded 1–stock; exceeding stock is silently capped.
     */
    public function update(Request $request, int $productId): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $cart = session()->get('cart', []);

        if (!isset($cart[$productId])) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Item not found in cart.'], 404);
            }
            return redirect()->route('cart.index')->withErrors(['cart' => 'Item not found in cart.']);
        }

        $product = Product::find($productId);
        $maxQty = $product ? $product->stock : PHP_INT_MAX;
        $qty = min((int) $data['quantity'], $maxQty);

        $cart[$productId]['quantity'] = $qty;
        session()->put('cart', $cart);

        // IMP-015: sync updated quantity to DB for authenticated users
        if (auth()->check()) {
            CartItem::where('user_id', auth()->id())
                ->where('product_id', $productId)
                ->update(['quantity' => $qty]);
        }

        $newSubtotal = $cart[$productId]['price'] * $qty;
        $newTotal = array_sum(array_map(
            fn($item) => $item['price'] * $item['quantity'],
            $cart
        ));

        $discount = self::computeDiscount($newTotal);
        $grandTotal = $newTotal - $discount;

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Cart updated.',
                'quantity' => $qty,
                'subtotal' => number_format($newSubtotal, 2),
                'order_total' => number_format($newTotal, 2),
                'discount_amount' => number_format($discount, 2),
                'grand_total' => number_format($grandTotal, 2),
            ]);
        }

        return redirect()->route('cart.index')->with('success', 'Cart updated.');
    }

    /**
     * SC-004: Remove a product from the session cart.
     * Accepts AJAX/JSON (returns JSON) or regular form POST with _method=DELETE.
     */
    public function destroy(Request $request, int $productId): JsonResponse|RedirectResponse
    {
        $cart = session()->get('cart', []);

        if (!isset($cart[$productId])) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Item not found in cart.'], 404);
            }
            return redirect()->route('cart.index')->withErrors(['cart' => 'Item not found in cart.']);
        }

        unset($cart[$productId]);
        session()->put('cart', $cart);

        // IMP-015: remove from DB for authenticated users
        if (auth()->check()) {
            CartItem::where('user_id', auth()->id())
                ->where('product_id', $productId)
                ->delete();
        }

        $cartCount = array_sum(array_column($cart, 'quantity'));
        $newTotal = array_sum(array_map(fn($item) => $item['price'] * $item['quantity'], $cart));

        $discount = self::computeDiscount($newTotal);
        $grandTotal = $newTotal - $discount;

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Item removed from cart.',
                'cart_count' => $cartCount,
                'order_total' => number_format($newTotal, 2),
                'discount_amount' => number_format($discount, 2),
                'grand_total' => number_format($grandTotal, 2),
            ]);
        }

        return redirect()->route('cart.index')->with('success', 'Item removed from cart.');
    }

    /**
     * SC-001: Add a product to the session cart.
     * Accepts regular form POST (redirects back) or AJAX/JSON (returns JSON).
     * Guest carts persist in session and survive login (session is regenerated,
     * not cleared, on auth — so cart data is automatically retained).
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $product = Product::findOrFail($data['product_id']);

        if ($product->stock < 1) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Product is out of stock.'], 422);
            }

            return back()->withErrors(['quantity' => 'Product is out of stock.']);
        }

        $qty = min((int) $data['quantity'], $product->stock);
        $cart = session()->get('cart', []);
        $id = $product->id;

        if (isset($cart[$id])) {
            $cart[$id]['quantity'] = min($cart[$id]['quantity'] + $qty, $product->stock);
        } else {
            $cart[$id] = [
                'product_id' => $id,
                'name' => $product->name,
                'price' => (float) $product->price,
                'quantity' => $qty,
                'slug' => $product->slug,
            ];
        }

        session()->put('cart', $cart);

        // IMP-015: persist to DB for authenticated users
        if (auth()->check()) {
            CartItem::updateOrCreate(
                ['user_id' => auth()->id(), 'product_id' => $id],
                ['quantity' => $cart[$id]['quantity']]
            );
        }

        $cartCount = array_sum(array_column($cart, 'quantity'));

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Product added to cart.',
                'cart_count' => $cartCount,
            ]);
        }

        return back()->with('success', 'Product added to cart.');
    }
}
