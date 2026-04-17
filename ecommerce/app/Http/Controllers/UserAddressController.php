<?php

namespace App\Http\Controllers;

use App\Models\UserAddress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserAddressController extends Controller
{
    public function index(): View
    {
        $addresses = auth()->user()->addresses;
        return view('user.addresses.index', compact('addresses'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'address_line1' => 'required|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|string|max:100',
            'is_default' => 'boolean',
        ]);

        $user = auth()->user();

        if (!empty($data['is_default'])) {
            $user->addresses()->update(['is_default' => false]);
        }

        $user->addresses()->create($data);

        return redirect()->route('addresses.index')->with('success', 'Address added.');
    }

    public function update(Request $request, UserAddress $address): RedirectResponse
    {
        abort_unless($address->user_id === auth()->id(), 403);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'address_line1' => 'required|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|string|max:100',
            'is_default' => 'boolean',
        ]);

        if (!empty($data['is_default'])) {
            auth()->user()->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
        }

        $address->update($data);

        return redirect()->route('addresses.index')->with('success', 'Address updated.');
    }

    public function destroy(UserAddress $address): RedirectResponse
    {
        abort_unless($address->user_id === auth()->id(), 403);

        $address->delete();

        return redirect()->route('addresses.index')->with('success', 'Address removed.');
    }

    public function setDefault(UserAddress $address): RedirectResponse
    {
        abort_unless($address->user_id === auth()->id(), 403);

        auth()->user()->addresses()->update(['is_default' => false]);
        $address->update(['is_default' => true]);

        return redirect()->route('addresses.index')->with('success', 'Default address updated.');
    }
}
