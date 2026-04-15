<?php

namespace App\Http\Controllers;

use App\Models\UserAddress;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
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
}
