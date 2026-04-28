@extends('layouts.app')
{{-- @include('partials.toast') --}}

@section('title', 'Checkout — Shipping Method')

@section('content')
    <div x-data x-init="$el.classList.add('fade-in')">
        {{-- Breadcrumb --}}
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('cart.index') }}">Cart</a></li>
                <li class="breadcrumb-item"><a href="{{ route('checkout.address') }}">Address</a></li>
                <li class="breadcrumb-item active">Shipping</li>
            </ol>
        </nav>

        <div class="row justify-content-center">
            <div class="col-lg-7">
                <h1 class="h4 fw-bold mb-4"><i class="bi bi-truck me-2 text-primary"></i>Shipping Method</h1>

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="card shadow-sm border-0 rounded-3 mb-4">
                    <div class="card-body p-4">
                        <form method="POST" action="{{ route('checkout.shipping.store') }}" x-data="{ loading: false }"
                            @submit="loading = true">
                            @csrf

                            <h2 class="h6 fw-semibold text-label mb-3">Select a Shipping Option</h2>

                            <div class="d-flex flex-column gap-2 mb-4">
                                @foreach ($shippingOptions as $key => $option)
                                    <label
                                        class="d-flex align-items-center gap-3 p-3 border rounded-3
                                                              {{ $selected === $key ? 'border-primary bg-primary bg-opacity-5' : 'border-light' }}"
                                        style="cursor:pointer;">
                                        <input class="form-check-input flex-shrink-0" type="radio" name="method"
                                            value="{{ $key }}" {{ $selected === $key ? 'checked' : '' }}
                                            onchange="updateTotals({{ $option['cost'] }})">
                                        <span class="flex-grow-1">
                                            <span class="fw-semibold">{{ $option['label'] }}</span>
                                            <span class="text-muted ms-1 small">({{ $option['days'] }})</span>
                                        </span>
                                        <span class="fw-bold">${{ number_format($option['cost'], 2) }}</span>
                                    </label>
                                @endforeach
                            </div>

                            <hr class="my-3">

                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Subtotal</span>
                                <span>${{ number_format($orderTotal, 2) }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Shipping</span>
                                <span id="shipping-cost">—</span>
                            </div>
                            <div class="d-flex justify-content-between fw-bold fs-5 mt-2">
                                <span>Grand Total</span>
                                <span id="grand-total">—</span>
                            </div>

                            <div class="d-flex gap-2 mt-4">
                                <a href="{{ route('checkout.address') }}" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-left me-1"></i> Back
                                </a>
                                <button type="submit" class="btn btn-primary px-4" :disabled="loading">
                                    <span x-show="loading" class="spinner-border spinner-border-sm me-2"
                                        role="status"></span>
                                    <span x-text="loading ? 'Please wait…' : 'Continue to Review'">Continue to Review</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const costs = @json(array_column($shippingOptions, 'cost', null));
            const keys = @json(array_keys($shippingOptions));
            const costMap = {};
            keys.forEach((k, i) => { costMap[k] = costs[i]; });

            const subtotal = {{ $orderTotal }};

            function updateTotals(cost) {
                document.getElementById('shipping-cost').textContent = '$' + cost.toFixed(2);
                document.getElementById('grand-total').textContent = '$' + (subtotal + cost).toFixed(2);
            }

            window.updateTotals = updateTotals;

            document.querySelectorAll('input[name="method"]').forEach(function (radio) {
                if (radio.checked) { updateTotals(costMap[radio.value]); }
            });
        })();
    </script>
@endpush