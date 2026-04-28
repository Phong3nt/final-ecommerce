<?php

namespace App\Http\Controllers;

use App\Models\UserAddress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserAddressController extends Controller
{
    public function index(): View
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $addresses = $user->addresses;
        $countries = $this->countries();

        // IMP-042: normalize legacy free-text values (e.g. vietnam/VN/Vietnam)
        // into ISO alpha-2 codes whenever we can map them safely.
        foreach ($addresses as $address) {
            $normalized = $this->normalizeCountryCode($address->country, $countries);
            if ($normalized !== null && $normalized !== $address->country) {
                $address->update(['country' => $normalized]);
            }
        }

        return view('user.addresses.index', compact('addresses', 'countries'));
    }

    public function store(Request $request): RedirectResponse
    {
        $countries = $this->countries();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'address_line1' => 'required|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'country' => ['required', 'string', Rule::in(array_keys($countries))],
            'is_default' => 'boolean',
        ]);

        $data['country'] = $this->normalizeCountryCode($data['country'], $countries) ?? $data['country'];

        /** @var \App\Models\User $user */
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

        $countries = $this->countries();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'address_line1' => 'required|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'country' => ['required', 'string', Rule::in(array_keys($countries))],
            'is_default' => 'boolean',
        ]);

        $data['country'] = $this->normalizeCountryCode($data['country'], $countries) ?? $data['country'];

        if (!empty($data['is_default'])) {
            /** @var \App\Models\User $authUser */
            $authUser = auth()->user();
            $authUser->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
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

        /** @var \App\Models\User $authUser */
        $authUser = auth()->user();
        $authUser->addresses()->update(['is_default' => false]);
        $address->update(['is_default' => true]);

        return redirect()->route('addresses.index')->with('success', 'Default address updated.');
    }

    private function countries(): array
    {
        return [
            'AU' => ['name' => 'Australia', 'flag' => '🇦🇺'],
            'CA' => ['name' => 'Canada', 'flag' => '🇨🇦'],
            'CN' => ['name' => 'China', 'flag' => '🇨🇳'],
            'DE' => ['name' => 'Germany', 'flag' => '🇩🇪'],
            'ES' => ['name' => 'Spain', 'flag' => '🇪🇸'],
            'FR' => ['name' => 'France', 'flag' => '🇫🇷'],
            'GB' => ['name' => 'United Kingdom', 'flag' => '🇬🇧'],
            'IN' => ['name' => 'India', 'flag' => '🇮🇳'],
            'IT' => ['name' => 'Italy', 'flag' => '🇮🇹'],
            'JP' => ['name' => 'Japan', 'flag' => '🇯🇵'],
            'KR' => ['name' => 'South Korea', 'flag' => '🇰🇷'],
            'MY' => ['name' => 'Malaysia', 'flag' => '🇲🇾'],
            'NL' => ['name' => 'Netherlands', 'flag' => '🇳🇱'],
            'PH' => ['name' => 'Philippines', 'flag' => '🇵🇭'],
            'SG' => ['name' => 'Singapore', 'flag' => '🇸🇬'],
            'TH' => ['name' => 'Thailand', 'flag' => '🇹🇭'],
            'US' => ['name' => 'United States', 'flag' => '🇺🇸'],
            'VN' => ['name' => 'Vietnam', 'flag' => '🇻🇳'],
        ];
    }

    private function normalizeCountryCode(?string $rawCountry, array $countries): ?string
    {
        $rawCountry = trim((string) $rawCountry);
        if ($rawCountry === '') {
            return null;
        }

        $upper = strtoupper($rawCountry);
        if (array_key_exists($upper, $countries)) {
            return $upper;
        }

        $normalizedKey = strtolower(preg_replace('/[^a-z]/i', '', $rawCountry) ?? '');
        $aliases = [
            'vn' => 'VN',
            'vietnam' => 'VN',
            'us' => 'US',
            'usa' => 'US',
            'unitedstates' => 'US',
            'uk' => 'GB',
            'unitedkingdom' => 'GB',
            'england' => 'GB',
        ];

        return $aliases[$normalizedKey] ?? null;
    }
}
