<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout — Shipping Address</title>
</head>

<body>
    <h1>Shipping Address</h1>

    <a href="{{ route('cart.index') }}">&larr; Back to Cart</a>

    @if ($errors->any())
        <ul class="errors">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    {{-- Saved addresses (auth users who have them) --}}
    @if ($addresses->isNotEmpty())
        <section class="saved-addresses">
            <h2>Saved Addresses</h2>
            <form action="{{ route('checkout.address.store') }}" method="POST">
                @csrf
                @foreach ($addresses as $address)
                    <label class="saved-address-option">
                        <input type="radio"
                               name="address_id"
                               value="{{ $address->id }}"
                               {{ $address->is_default ? 'checked' : '' }}>
                        <span>
                            {{ $address->name }},
                            {{ $address->address_line1 }}
                            @if ($address->address_line2), {{ $address->address_line2 }}@endif,
                            {{ $address->city }}, {{ $address->state }} {{ $address->postal_code }},
                            {{ $address->country }}
                        </span>
                    </label>
                @endforeach
                <button type="submit" class="btn-use-saved">Use Selected Address</button>
            </form>
        </section>

        <hr>
        <h2>Or Enter a New Address</h2>
    @else
        <h2>Enter Shipping Address</h2>
    @endif

    {{-- New address form --}}
    <form action="{{ route('checkout.address.store') }}" method="POST" class="new-address-form">
        @csrf

        <div>
            <label for="name">Recipient Name</label>
            <input type="text" id="name" name="name" value="{{ old('name') }}" required>
        </div>

        <div>
            <label for="address_line1">Address Line 1</label>
            <input type="text" id="address_line1" name="address_line1" value="{{ old('address_line1') }}" required>
        </div>

        <div>
            <label for="address_line2">Address Line 2 (optional)</label>
            <input type="text" id="address_line2" name="address_line2" value="{{ old('address_line2') }}">
        </div>

        <div>
            <label for="city">City</label>
            <input type="text" id="city" name="city" value="{{ old('city') }}" required>
        </div>

        <div>
            <label for="state">State / Province</label>
            <input type="text" id="state" name="state" value="{{ old('state') }}" required>
        </div>

        <div>
            <label for="postal_code">Postal Code</label>
            <input type="text" id="postal_code" name="postal_code" value="{{ old('postal_code') }}" required>
        </div>

        <div>
            <label for="country">Country</label>
            <input type="text" id="country" name="country" value="{{ old('country') }}" required>
        </div>

        <button type="submit" class="btn-continue">Continue to Shipping</button>
    </form>
</body>

</html>
