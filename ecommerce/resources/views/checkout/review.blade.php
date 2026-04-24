@extends('layouts.app')
{{-- @include('partials.toast') --}}

@section('title', 'Checkout — Order Review')

@section('content')
    <div x-data x-init="$el.classList.add('fade-in')">
        {{-- Breadcrumb --}}
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('cart.index') }}">Cart</a></li>
                <li class="breadcrumb-item"><a href="{{ route('checkout.address') }}">Address</a></li>
                <li class="breadcrumb-item"><a href="{{ route('checkout.shipping') }}">Shipping</a></li>
                <li class="breadcrumb-item active">Review</li>
            </ol>
        </nav>

        <div class="row g-4">
            {{-- Left column: address + shipping + items --}}
            <div class="col-lg-7">

                {{-- Address summary --}}
                <div class="card shadow-sm border-0 rounded-3 mb-3">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h2 class="h6 fw-semibold text-label mb-0">Shipping Address</h2>
                            <a href="{{ route('checkout.address') }}" class="btn btn-outline-secondary btn-sm">Edit</a>
                        </div>
                        <p class="mb-0 small">
                            <strong>{{ $address['name'] }}</strong><br>
                            {{ $address['address_line1'] }}@if($address['address_line2']),
                            {{ $address['address_line2'] }}@endif<br>
                            {{ $address['city'] }}, {{ $address['state'] }} {{ $address['postal_code'] }}<br>
                            {{ $address['country'] }}
                        </p>
                    </div>
                </div>

                {{-- Shipping method --}}
                <div class="card shadow-sm border-0 rounded-3 mb-3">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h2 class="h6 fw-semibold text-label mb-1">Shipping Method</h2>
                                <span class="small">{{ $shipping['label'] }}</span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="fw-bold">${{ number_format($shipping['cost'], 2) }}</span>
                                <a href="{{ route('checkout.shipping') }}" class="btn btn-outline-secondary btn-sm">Edit</a>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Order items --}}
                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <h2 class="h6 fw-semibold text-label mb-0">Order Items</h2>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">Product</th>
                                        <th class="text-center">Qty</th>
                                        <th class="text-end">Price</th>
                                        <th class="text-end pe-4">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($cart as $item)
                                        <tr>
                                            <td class="ps-4">{{ $item['name'] }}</td>
                                            <td class="text-center">{{ $item['quantity'] }}</td>
                                            <td class="text-end">${{ number_format($item['price'], 2) }}</td>
                                            <td class="text-end pe-4">
                                                ${{ number_format($item['price'] * $item['quantity'], 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>

            {{-- Right column: order summary + payment --}}
            <div class="col-lg-5">
                <div class="card shadow-sm border-0 rounded-3 sticky-top" style="top:80px;">
                    <div class="card-body p-4">
                        <h2 class="h6 fw-semibold text-label mb-3">Order Summary</h2>

                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Subtotal</span>
                            <span>${{ number_format($subtotal, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Shipping</span>
                            <span>${{ number_format($shipping['cost'], 2) }}</span>
                        </div>
                        @if($discount > 0)
                            <div class="d-flex justify-content-between mb-1 text-success">
                                <span>Discount ({{ $coupon['code'] }})</span>
                                <span>-${{ number_format($discount, 2) }}</span>
                            </div>
                        @endif
                        <hr>
                        <div class="d-flex justify-content-between fw-bold fs-5 mb-4">
                            <span>Total</span>
                            <span class="text-primary">${{ number_format($total, 2) }}</span>
                        </div>

                        <hr class="mb-3">

                        {{-- Stripe Payment Element --}}
                        <h2 class="h6 fw-semibold text-label mb-3">
                            <i class="bi bi-lock-fill me-1 text-success"></i>
                            Secure Payment
                        </h2>
                        <p class="text-muted small mb-3">Your card details are handled securely by Stripe. Your card number
                            never touches our server.</p>

                        <div id="payment-element" class="mb-3">
                            {{-- Stripe Elements injected here --}}
                        </div>

                        <div id="payment-message" class="alert alert-danger mb-3" style="display:none;"></div>

                        <button id="pay-button" type="button" class="btn btn-success w-100 py-2 fw-semibold">
                            <i class="bi bi-shield-check me-1"></i>
                            Pay ${{ number_format($total, 2) }}
                        </button>

                        <p class="text-center text-muted mt-2" style="font-size:0.75rem;">
                            <i class="bi bi-lock me-1"></i> SSL encrypted · Powered by Stripe
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://js.stripe.com/v3/"></script>
    <script>
        (function () {
            const placeOrderUrl = "{{ route('checkout.place-order') }}";
            const stripeKey = "{{ config('services.stripe.key') }}";
            const csrfToken = "{{ csrf_token() }}";

            if (!stripeKey) {
                const msg = document.getElementById('payment-message');
                msg.textContent = 'Payment is temporarily unavailable.';
                msg.style.display = 'block';
                return;
            }

            const stripe = Stripe(stripeKey);
            let elements;

            // Step 1: POST to place-order to create a PaymentIntent server-side
            fetch(placeOrderUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.error) { showMessage(data.error); return; }
                    // Step 2: Mount Stripe Payment Element (handles all card validation incl. digit limits)
                    elements = stripe.elements({ clientSecret: data.client_secret });
                    const paymentElement = elements.create('payment');
                    paymentElement.mount('#payment-element');
                })
                .catch(function () { showMessage('Could not initialise payment.'); });

            // Step 3: On button click, confirm the payment via Stripe.js
            document.getElementById('pay-button').addEventListener('click', async function () {
                if (!elements) return;
                const btn = this;
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Processing…';

                const { error } = await stripe.confirmPayment({
                    elements,
                    confirmParams: {
                        return_url: window.location.origin + '/checkout/success',
                    },
                });

                if (error) {
                    showMessage(error.message);
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-shield-check me-1"></i>Pay ${{ number_format($total, 2) }}';
                }
            });

            function showMessage(msg) {
                const el = document.getElementById('payment-message');
                el.textContent = msg;
                el.style.display = 'block';
            }
        })();
    </script>
@endpush