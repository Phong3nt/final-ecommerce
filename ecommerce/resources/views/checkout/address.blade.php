@extends('layouts.app')
{{-- @include('partials.toast') --}}

@section('title', 'Checkout — Shipping Address')

@section('content')
<div x-data x-init="$el.classList.add('fade-in')">
    {{-- Breadcrumb --}}
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('cart.index') }}">Cart</a></li>
            <li class="breadcrumb-item active">Shipping Address</li>
        </ol>
    </nav>

    <div class="row justify-content-center">
        <div class="col-lg-7">
            <h1 class="h4 fw-bold mb-4"><i class="bi bi-geo-alt me-2 text-primary"></i>Shipping Address</h1>

            {{-- Saved addresses --}}
            @if ($addresses->isNotEmpty())
                <div class="card shadow-sm border-0 rounded-3 mb-4">
                    <div class="card-body p-4">
                        <h2 class="h6 fw-semibold text-label mb-3">Saved Addresses</h2>
                        <form action="{{ route('checkout.address.store') }}" method="POST"
                              x-data="{ loading: false }" @submit="loading = true">
                            @csrf
                            <div class="d-flex flex-column gap-2 mb-3">
                                @foreach ($addresses as $address)
                                    <label class="d-flex align-items-start gap-3 p-3 border rounded-3 cursor-pointer
                                                  {{ $address->is_default ? 'border-primary bg-primary bg-opacity-5' : 'border-light' }}">
                                        <input class="form-check-input mt-1 flex-shrink-0"
                                               type="radio" name="address_id" value="{{ $address->id }}"
                                               {{ $address->is_default ? 'checked' : '' }}>
                                        <span class="small">
                                            <span class="fw-semibold">{{ $address->name }}</span><br>
                                            {{ $address->address_line1 }}
                                            @if ($address->address_line2), {{ $address->address_line2 }}@endif<br>
                                            {{ $address->city }}, {{ $address->state }} {{ $address->postal_code }}, {{ $address->country }}
                                            @if ($address->is_default)
                                                <span class="badge bg-primary ms-1">Default</span>
                                            @endif
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            <button type="submit" class="btn btn-primary px-4" :disabled="loading">
                                <span x-show="loading" class="spinner-border spinner-border-sm me-2" role="status"></span>
                                <span x-text="loading ? 'Please wait…' : 'Use Selected Address'">Use Selected Address</span>
                            </button>
                        </form>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-3 mb-4">
                    <hr class="flex-grow-1"><span class="text-muted small">or enter a new address</span><hr class="flex-grow-1">
                </div>
            @endif

            {{-- New address form --}}
            <div id="new-address-form" class="card shadow-sm border-0 rounded-3">
                <div class="card-body p-4">
                    @if ($addresses->isEmpty())
                        <h2 class="h6 fw-semibold text-label mb-3">Enter Shipping Address</h2>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0 ps-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('checkout.address.store') }}" method="POST"
                          x-data="{ loading: false }" @submit="loading = true">
                        @csrf
                        <div class="mb-3">
                            <label for="name" class="form-label fw-semibold">Recipient Name</label>
                            <input type="text" id="name" name="name"
                                   class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name') }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="address_line1" class="form-label fw-semibold">Address Line 1</label>
                            <input type="text" id="address_line1" name="address_line1"
                                   class="form-control @error('address_line1') is-invalid @enderror"
                                   value="{{ old('address_line1') }}" required>
                            @error('address_line1')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="address_line2" class="form-label fw-semibold">Address Line 2 <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="text" id="address_line2" name="address_line2"
                                   class="form-control"
                                   value="{{ old('address_line2') }}">
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="city" class="form-label fw-semibold">City</label>
                                <input type="text" id="city" name="city"
                                       class="form-control @error('city') is-invalid @enderror"
                                       value="{{ old('city') }}" required>
                                @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="state" class="form-label fw-semibold">State / Province</label>
                                <input type="text" id="state" name="state"
                                       class="form-control @error('state') is-invalid @enderror"
                                       value="{{ old('state') }}" required>
                                @error('state')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="postal_code" class="form-label fw-semibold">Postal Code</label>
                                <input type="text" id="postal_code" name="postal_code"
                                       class="form-control @error('postal_code') is-invalid @enderror"
                                       value="{{ old('postal_code') }}" required>
                                @error('postal_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="country" class="form-label fw-semibold">Country</label>
                                <input type="text" id="country" name="country"
                                       class="form-control @error('country') is-invalid @enderror"
                                       value="{{ old('country') }}" required>
                                @error('country')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('cart.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i> Back to Cart
                            </a>
                            <button type="submit" class="btn btn-primary px-4" :disabled="loading">
                                <span x-show="loading" class="spinner-border spinner-border-sm me-2" role="status"></span>
                                <span x-text="loading ? 'Please wait…' : 'Continue to Shipping'">Continue to Shipping</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
