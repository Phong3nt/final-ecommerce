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

                        {{-- IMP-035: Saved card selection --}}
                        @php $defaultCard = $savedPaymentMethods->firstWhere('is_default', true); @endphp

                        <h2 class="h6 fw-semibold text-label mb-3">
                            <i class="bi bi-lock-fill me-1 text-success"></i>
                            Secure Payment
                        </h2>
                        <p class="text-muted small mb-3">Your card details are handled securely by Stripe. Your card number
                            never touches our server.</p>

                        @if ($savedPaymentMethods->isNotEmpty())
                            {{-- Saved card radio buttons --}}
                            <div class="mb-3" id="saved-cards-section">
                                @foreach ($savedPaymentMethods as $card)
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="payment_choice"
                                            id="card-{{ $card->id }}"
                                            value="{{ $card->stripe_payment_method_id }}"
                                            {{ $card->is_default ? 'checked' : '' }}>
                                        <label class="form-check-label" for="card-{{ $card->id }}">
                                            <i class="bi bi-credit-card me-1"></i>
                                            {{ $card->display_label }}
                                            <span class="text-muted small">exp {{ $card->expiry }}</span>
                                            @if ($card->is_default)
                                                <span class="badge bg-primary ms-1 small">Default</span>
                                            @endif
                                        </label>
                                    </div>
                                @endforeach
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="payment_choice"
                                        id="card-new" value="">
                                    <label class="form-check-label" for="card-new">
                                        <i class="bi bi-plus-lg me-1"></i> Use a new card
                                    </label>
                                </div>
                            </div>
                        @endif

                        <div id="payment-element" class="mb-3"
                            style="{{ $savedPaymentMethods->isNotEmpty() && $defaultCard ? 'display:none;' : '' }}">
                            {{-- Stripe Elements injected here --}}
                        </div>

                        {{-- IMP-035: Save card prompt (only for new card flow) --}}
                        @if ($savedPaymentMethods->isNotEmpty())
                            <div id="save-card-wrap" class="mb-3"
                                style="display:{{ $defaultCard ? 'none' : 'block' }};">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="save-card">
                                    <label class="form-check-label small" for="save-card">
                                        Save this card for future purchases
                                    </label>
                                </div>
                            </div>
                        @else
                            {{-- No saved cards: always show save prompt --}}
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="save-card">
                                    <label class="form-check-label small" for="save-card">
                                        Save this card for future purchases
                                    </label>
                                </div>
                            </div>
                        @endif

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
            const placeOrderUrl   = "{{ route('checkout.place-order') }}";
            const saveFlagUrl     = "{{ route('checkout.save-card-flag') }}";
            const stripeKey       = "{{ config('services.stripe.key') }}";
            const csrfToken       = "{{ csrf_token() }}";
            const successBase     = window.location.origin + '/checkout/success';
            const totalFormatted  = "${{ number_format($total, 2) }}";
            @php $defaultCard = $savedPaymentMethods->firstWhere('is_default', true); @endphp
            const hasSavedCards   = {{ $savedPaymentMethods->isNotEmpty() ? 'true' : 'false' }};

            // Currently selected PM (empty string = new card)
            let selectedPmId = '{{ $defaultCard?->stripe_payment_method_id ?? '' }}';

            if (!stripeKey) {
                showMessage('Payment is temporarily unavailable.');
                return;
            }

            const stripe = Stripe(stripeKey);
            let elements;
            let clientSecret;

            // ---------------------------------------------------------------
            // Step 1: POST place-order to create PaymentIntent (and Order)
            // We do this on page load. For saved-card flow we still create
            // the PI here; confirmCardPayment works with automatic_payment_methods.
            // ---------------------------------------------------------------
            fetch(placeOrderUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
            })
            .then(r => r.json())
            .then(data => {
                if (data.error) { showMessage(data.error); return; }
                clientSecret = data.client_secret;

                // Mount Elements only when new-card mode is active
                if (!selectedPmId) {
                    mountPaymentElement();
                }
            })
            .catch(() => showMessage('Could not initialise payment.'));

            function mountPaymentElement() {
                if (elements) return; // already mounted
                elements = stripe.elements({ clientSecret });
                const paymentElement = elements.create('payment');
                paymentElement.mount('#payment-element');
                document.getElementById('payment-element').style.display = 'block';
            }

            // ---------------------------------------------------------------
            // Radio button changes (only present when hasSavedCards)
            // ---------------------------------------------------------------
            if (hasSavedCards) {
                document.querySelectorAll('[name="payment_choice"]').forEach(function (radio) {
                    radio.addEventListener('change', function () {
                        selectedPmId = this.value;
                        const pmEl     = document.getElementById('payment-element');
                        const saveWrap = document.getElementById('save-card-wrap');

                        if (selectedPmId === '') {
                            // New card
                            if (saveWrap) saveWrap.style.display = 'block';
                            pmEl.style.display = 'block';
                            if (clientSecret) mountPaymentElement();
                        } else {
                            // Saved card
                            if (saveWrap) saveWrap.style.display = 'none';
                            pmEl.style.display = 'none';
                        }
                    });
                });
            }

            // ---------------------------------------------------------------
            // Pay button
            // ---------------------------------------------------------------
            document.getElementById('pay-button').addEventListener('click', async function () {
                if (!clientSecret) return;

                const btn = this;
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Processing…';

                if (selectedPmId) {
                    // ---- Using saved card ----
                    const { paymentIntent, error } = await stripe.confirmCardPayment(clientSecret, {
                        payment_method: selectedPmId,
                        return_url: successBase, // fallback for 3DS redirect
                    });

                    if (error) {
                        showMessage(error.message);
                        btn.disabled = false;
                        btn.innerHTML = '<i class="bi bi-shield-check me-1"></i>Pay ' + totalFormatted;
                        return;
                    }

                    // Redirect manually on success (no 3DS redirect needed for most saved cards)
                    if (paymentIntent && paymentIntent.status === 'succeeded') {
                        const params = new URLSearchParams({
                            payment_intent: paymentIntent.id,
                            redirect_status: 'succeeded',
                        });
                        window.location.href = successBase + '?' + params.toString();
                    }
                    // If 3DS redirect happened, confirmCardPayment won't return here

                } else {
                    // ---- Using new card ----
                    if (!elements) {
                        showMessage('Please wait while the payment form loads.');
                        btn.disabled = false;
                        btn.innerHTML = '<i class="bi bi-shield-check me-1"></i>Pay ' + totalFormatted;
                        return;
                    }

                    // Store "save card" flag in session before Stripe redirect
                    const saveCard = document.getElementById('save-card')?.checked;
                    if (saveCard) {
                        try {
                            await fetch(saveFlagUrl, {
                                method: 'POST',
                                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                                credentials: 'same-origin',
                            });
                        } catch (e) { /* non-fatal */ }
                    }

                    const { error } = await stripe.confirmPayment({
                        elements,
                        confirmParams: { return_url: successBase },
                    });

                    if (error) {
                        showMessage(error.message);
                        btn.disabled = false;
                        btn.innerHTML = '<i class="bi bi-shield-check me-1"></i>Pay ' + totalFormatted;
                    }
                    // On success, Stripe redirects to successBase
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