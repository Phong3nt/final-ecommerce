<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    /**
     * SC-002: Display the session cart contents.
     */
    public function index(): View
    {
        $cart  = session()->get('cart', []);
        $total = array_sum(array_map(
            fn($item) => $item['price'] * $item['quantity'],
            $cart
        ));

        return view('cart.index', compact('cart', 'total'));
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
        $maxQty  = $product ? $product->stock : PHP_INT_MAX;
        $qty     = min((int) $data['quantity'], $maxQty);

        $cart[$productId]['quantity'] = $qty;
        session()->put('cart', $cart);

        $newSubtotal = $cart[$productId]['price'] * $qty;
        $newTotal    = array_sum(array_map(
            fn($item) => $item['price'] * $item['quantity'],
            $cart
        ));

        if ($request->expectsJson()) {
            return response()->json([
                'message'     => 'Cart updated.',
                'quantity'    => $qty,
                'subtotal'    => number_format($newSubtotal, 2),
                'order_total' => number_format($newTotal, 2),
            ]);
        }

        return redirect()->route('cart.index')->with('success', 'Cart updated.');
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
