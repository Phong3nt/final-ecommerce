<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
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
            'quantity'   => 'required|integer|min:1',
        ]);

        $product = Product::findOrFail($data['product_id']);

        if ($product->stock < 1) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Product is out of stock.'], 422);
            }

            return back()->withErrors(['quantity' => 'Product is out of stock.']);
        }

        $qty  = min((int) $data['quantity'], $product->stock);
        $cart = session()->get('cart', []);
        $id   = $product->id;

        if (isset($cart[$id])) {
            $cart[$id]['quantity'] = min($cart[$id]['quantity'] + $qty, $product->stock);
        } else {
            $cart[$id] = [
                'product_id' => $id,
                'name'       => $product->name,
                'price'      => (float) $product->price,
                'quantity'   => $qty,
                'slug'       => $product->slug,
            ];
        }

        session()->put('cart', $cart);

        $cartCount = array_sum(array_column($cart, 'quantity'));

        if ($request->expectsJson()) {
            return response()->json([
                'message'    => 'Product added to cart.',
                'cart_count' => $cartCount,
            ]);
        }

        return back()->with('success', 'Product added to cart.');
    }
}
