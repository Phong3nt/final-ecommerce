<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    /**
     * SC-005: Apply a coupon code to the session.
     * Validates the code against the database and stores it in
     * session('checkout.coupon') if valid. Redirects back to the cart
     * with an error message for expired or invalid codes.
     */
    public function apply(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => 'required|string|max:100',
        ]);

        $code = strtoupper(trim((string) $request->input('code')));
        $couponQuery = Coupon::query()->where('code', $code);

        if (auth()->check()) {
            $userId = auth()->id();
            $couponQuery->where(function ($query) use ($userId) {
                $query->whereNull('user_id')->orWhere('user_id', $userId);
            });
        } else {
            $couponQuery->whereNull('user_id');
        }

        $coupon = $couponQuery->first();

        if (!$coupon || !$coupon->isValid()) {
            return redirect()->route('cart.index')
                ->withErrors(['coupon' => 'Invalid or expired coupon code.']);
        }

        $cart = session('cart', []);
        $subtotal = (float) collect($cart)->sum(fn($item) => $item['price'] * $item['quantity']);
        if ($coupon->min_order_amount !== null && $subtotal < $coupon->min_order_amount) {
            return redirect()->route('cart.index')
                ->withErrors(['coupon' => 'This coupon requires a higher minimum order amount.']);
        }

        session()->put('checkout.coupon', [
            'id' => $coupon->id,
            'code' => $coupon->code,
            'name' => $coupon->name,
            'type' => $coupon->type,
            'value' => $coupon->value,
        ]);

        return redirect()->route('cart.index')
            ->with('success', 'Coupon applied successfully!');
    }

    /**
     * SC-005: Remove the coupon from the session.
     */
    public function remove(): RedirectResponse
    {
        session()->forget('checkout.coupon');

        return redirect()->route('cart.index')
            ->with('success', 'Coupon removed.');
    }
}
