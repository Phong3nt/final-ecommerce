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

        $coupon = Coupon::where('code', $request->input('code'))->first();

        if (!$coupon || !$coupon->isValid()) {
            return redirect()->route('cart.index')
                ->withErrors(['coupon' => 'Invalid or expired coupon code.']);
        }

        session()->put('checkout.coupon', [
            'code' => $coupon->code,
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
