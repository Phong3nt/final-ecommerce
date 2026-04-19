<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>My Saved Addresses</title>
    <style>
        body {
            font-family: sans-serif;
            margin: 2rem;
            background: #f5f5f5;
        }

        h1 {
            margin-bottom: 1rem;
        }

        .card {
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 1rem 1.25rem;
            margin-bottom: 1rem;
        }

        .card h2 {
            font-size: 1rem;
            margin: 0 0 .5rem;
        }

        .badge-default {
            background: #d1e7dd;
            color: #0f5132;
            padding: .15rem .5rem;
            border-radius: 10px;
            font-size: .78rem;
            font-weight: 600;
        }

        address {
            font-style: normal;
            line-height: 1.7;
            font-size: .9rem;
        }

        .actions {
            margin-top: .6rem;
            display: flex;
            gap: .5rem;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-block;
            padding: .35rem .8rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: .85rem;
            text-decoration: none;
        }

        .btn-primary {
            background: #0d6efd;
            color: #fff;
        }

        .btn-secondary {
            background: #6c757d;
            color: #fff;
        }

        .btn-danger {
            background: #dc3545;
            color: #fff;
        }

        .btn-success {
            background: #198754;
            color: #fff;
        }

        .alert-success {
            background: #d1e7dd;
            border: 1px solid #badbcc;
            color: #0f5132;
            padding: .75rem 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }

        .alert-error {
            background: #f8d7da;
            border: 1px solid #f5c2c7;
            color: #842029;
            padding: .75rem 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }

        .errors ul {
            color: #842029;
            padding-left: 1.2rem;
        }

        fieldset {
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 1rem;
        }

        legend {
            font-weight: 600;
            padding: 0 .5rem;
        }

        label {
            display: block;
            font-size: .875rem;
            margin-bottom: .2rem;
            margin-top: .6rem;
        }

        input[type=text] {
            width: 100%;
            padding: .4rem .6rem;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: .875rem;
            box-sizing: border-box;
        }

        input[type=checkbox] {
            margin-right: .4rem;
        }

        .edit-form {
            display: none;
            margin-top: 1rem;
        }

        .edit-form.open {
            display: block;
        }
    </style>
</head>

<body>
    @include('partials.toast')

    <a href="{{ route('profile.show') }}" class="btn btn-secondary" style="margin-bottom:1rem;">&larr; Back to
        Profile</a>

    <h1>Saved Addresses</h1>

    @if($addresses->isEmpty())
        <p>You have no saved addresses yet.</p>
    @else
        @foreach($addresses as $address)
            <div class="card">
                <h2>
                    {{ $address->name }}
                    @if($address->is_default)
                        <span class="badge-default">Default</span>
                    @endif
                </h2>
                <address>
                    {{ $address->address_line1 }}
                    @if($address->address_line2)<br>{{ $address->address_line2 }}@endif
                    <br>{{ $address->city }}, {{ $address->state }} {{ $address->postal_code }}<br>
                    {{ $address->country }}
                </address>

                <div class="actions">
                    {{-- Set as default --}}
                    @unless($address->is_default)
                        <form method="POST" action="{{ route('addresses.setDefault', $address) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-success">Set as Default</button>
                        </form>
                    @endunless

                    {{-- Toggle edit form --}}
                    <button type="button" class="btn btn-primary"
                        onclick="document.getElementById('edit-{{ $address->id }}').classList.toggle('open')">
                        Edit
                    </button>

                    {{-- Delete --}}
                    <form method="POST" action="{{ route('addresses.destroy', $address) }}"
                        onsubmit="return confirm('Delete this address?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>

                {{-- Inline edit form --}}
                <div id="edit-{{ $address->id }}" class="edit-form">
                    <form method="POST" action="{{ route('addresses.update', $address) }}">
                        @csrf
                        @method('PUT')
                        <fieldset>
                            <legend>Edit Address</legend>
                            <label>Recipient Name</label>
                            <input type="text" name="name" value="{{ old('name', $address->name) }}" required>
                            <label>Address Line 1</label>
                            <input type="text" name="address_line1" value="{{ old('address_line1', $address->address_line1) }}"
                                required>
                            <label>Address Line 2 (optional)</label>
                            <input type="text" name="address_line2" value="{{ old('address_line2', $address->address_line2) }}">
                            <label>City</label>
                            <input type="text" name="city" value="{{ old('city', $address->city) }}" required>
                            <label>State</label>
                            <input type="text" name="state" value="{{ old('state', $address->state) }}" required>
                            <label>Postal Code</label>
                            <input type="text" name="postal_code" value="{{ old('postal_code', $address->postal_code) }}"
                                required>
                            <label>Country</label>
                            <input type="text" name="country" value="{{ old('country', $address->country) }}" required>
                            <label>
                                <input type="checkbox" name="is_default" value="1" {{ $address->is_default ? 'checked' : '' }}>
                                Set as default
                            </label>
                            <div style="margin-top:.75rem;">
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                            </div>
                        </fieldset>
                    </form>
                </div>
            </div>
        @endforeach
    @endif

    {{-- Add new address --}}
    <div class="card" style="margin-top:1.5rem;">
        <h2>Add New Address</h2>
        @if($errors->any())
            <div class="errors">
                <ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif
        <form method="POST" action="{{ route('addresses.store') }}">
            @csrf
            <fieldset>
                <legend>New Address</legend>
                <label>Recipient Name</label>
                <input type="text" name="name" value="{{ old('name') }}" required>
                <label>Address Line 1</label>
                <input type="text" name="address_line1" value="{{ old('address_line1') }}" required>
                <label>Address Line 2 (optional)</label>
                <input type="text" name="address_line2" value="{{ old('address_line2') }}">
                <label>City</label>
                <input type="text" name="city" value="{{ old('city') }}" required>
                <label>State</label>
                <input type="text" name="state" value="{{ old('state') }}" required>
                <label>Postal Code</label>
                <input type="text" name="postal_code" value="{{ old('postal_code') }}" required>
                <label>Country</label>
                <input type="text" name="country" value="{{ old('country') }}" required>
                <label>
                    <input type="checkbox" name="is_default" value="1" {{ old('is_default') ? 'checked' : '' }}>
                    Set as default
                </label>
                <div style="margin-top:.75rem;">
                    <button type="submit" class="btn btn-primary">Add Address</button>
                </div>
            </fieldset>
        </form>
    </div>

</body>

</html>