<?php

namespace App\Http\Controllers;

use App\Models\UserAddress;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    /**
     * Available shipping methods with cost and estimated delivery.
     */
    private const SHIPPING_OPTIONS = [
        'standard' => ['label' => 'Standard Shipping', 'cost' => 5.00,  'days' => '5–7 business days'],
        'express'  => ['label' => 'Express Shipping',  'cost' => 15.00, 'days' => '1–2 business days'],
    ];

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

        return redirect()->route('checkout.shipping');
    }

    /**
     * CP-002: Show the checkout shipping method step.
     * Requires checkout.address in session; otherwise redirect back.
     */
    public function showShipping(): View|RedirectResponse
    {
        if (! session()->has('checkout.address')) {
            return redirect()->route('checkout.address')
                ->with('error', 'Please provide a shipping address first.');
        }

        $cart = session('cart', []);
        $orderTotal = collect($cart)->sum(fn ($item) => $item['price'] * $item['quantity']);

        return view('checkout.shipping', [
            'shippingOptions' => self::SHIPPING_OPTIONS,
            'orderTotal'      => $orderTotal,
            'selected'        => session('checkout.shipping.method'),
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

        $method  = $request->input('method');
        $option  = self::SHIPPING_OPTIONS[$method];

        session()->put('checkout.shipping', [
            'method' => $method,
            'label'  => $option['label'],
            'cost'   => $option['cost'],
        ]);

        return redirect()->route('checkout.review');
    }
}
