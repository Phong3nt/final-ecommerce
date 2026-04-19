<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <script src="https://js.stripe.com/v3/"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .checkout-step-heading {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0;
        }

        #payment-section {
            display: none;
        }

        #new-address-form {
            display: {{ $addresses->isNotEmpty() ? 'none' : 'block' }};
        }
    </style>
</head>

<body class="bg-light">
    <div class="container py-4">

        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('cart.index') }}">Cart</a></li>
                <li class="breadcrumb-item active" aria-current="page">Checkout</li>
            </ol>
        </nav>

        <h1 class="h3 mb-4">Checkout</h1>

        <div class="row g-4">

            {{-- ── LEFT column: Address + Shipping ─────────────────────────────── --}}
            <div class="col-lg-7" id="checkout-form-col">

                {{-- Step 1: Shipping Address --}}
                <div class="card mb-3">
                    <div class="card-header">
                        <span class="checkout-step-heading">1 &mdash; Shipping Address</span>
                    </div>
                    <div class="card-body">

                        @if ($addresses->isNotEmpty())
                            <div id="saved-addresses" class="mb-3">
                                <p class="small text-muted mb-2">Select a saved address or enter a new one below.</p>
                                @foreach ($addresses as $address)
                                    <div class="form-check mb-2">
                                        <input class="form-check-input saved-addr-radio"
                                               type="radio"
                                               name="address_id"
                                               id="addr-{{ $address->id }}"
                                               value="{{ $address->id }}"
                                               {{ $address->is_default ? 'checked' : '' }}>
                                        <label class="form-check-label" for="addr-{{ $address->id }}">
                                            <strong>{{ $address->name }}</strong>,
                                            {{ $address->address_line1 }}@if($address->address_line2), {{ $address->address_line2 }}@endif,
                                            {{ $address->city }}, {{ $address->state }} {{ $address->postal_code }},
                                            {{ $address->country }}
                                        </label>
                                    </div>
                                @endforeach
                                <div class="form-check mt-2">
                                    <input class="form-check-input saved-addr-radio"
                                           type="radio"
                                           name="address_id"
                                           id="addr-new"
                                           value="">
                                    <label class="form-check-label" for="addr-new">
                                        Enter a new address
                                    </label>
                                </div>
                            </div>
                            <hr>
                        @endif

                        {{-- New address fields --}}
                        <div id="new-address-form">
                            <div class="mb-2">
                                <label for="name" class="form-label small">Recipient Name</label>
                                <input type="text" class="form-control form-control-sm" id="name"
                                       name="name" autocomplete="name">
                            </div>
                            <div class="mb-2">
                                <label for="address_line1" class="form-label small">Address Line 1</label>
                                <input type="text" class="form-control form-control-sm" id="address_line1"
                                       name="address_line1" autocomplete="address-line1">
                            </div>
                            <div class="mb-2">
                                <label for="address_line2" class="form-label small">Address Line 2 <span class="text-muted">(optional)</span></label>
                                <input type="text" class="form-control form-control-sm" id="address_line2"
                                       name="address_line2" autocomplete="address-line2">
                            </div>
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label for="city" class="form-label small">City</label>
                                    <input type="text" class="form-control form-control-sm" id="city"
                                           name="city" autocomplete="address-level2">
                                </div>
                                <div class="col-6">
                                    <label for="state" class="form-label small">State / Province</label>
                                    <input type="text" class="form-control form-control-sm" id="state"
                                           name="state" autocomplete="address-level1">
                                </div>
                            </div>
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label for="postal_code" class="form-label small">Postal Code</label>
                                    <input type="text" class="form-control form-control-sm" id="postal_code"
                                           name="postal_code" autocomplete="postal-code">
                                </div>
                                <div class="col-6">
                                    <label for="country" class="form-label small">Country</label>
                                    <input type="text" class="form-control form-control-sm" id="country"
                                           name="country" autocomplete="country-name">
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                {{-- Step 2: Shipping Method --}}
                <div class="card mb-3">
                    <div class="card-header">
                        <span class="checkout-step-heading">2 &mdash; Shipping Method</span>
                    </div>
                    <div class="card-body">
                        @foreach ($shippingOptions as $key => $option)
                            <div class="form-check mb-2">
                                <input class="form-check-input shipping-radio"
                                       type="radio"
                                       name="method"
                                       id="method-{{ $key }}"
                                       value="{{ $key }}"
                                       {{ $loop->first ? 'checked' : '' }}>
                                <label class="form-check-label" for="method-{{ $key }}">
                                    <strong>{{ $option['label'] }}</strong>
                                    &mdash; ${{ number_format($option['cost'], 2) }}
                                    <span class="text-muted small">({{ $option['days'] }})</span>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- CTA: Review & Pay --}}
                <button id="review-pay-btn" class="btn btn-primary w-100 mb-2">
                    Review &amp; Pay
                </button>
                <div id="form-error" class="alert alert-danger" style="display:none;"></div>

            </div>

            {{-- ── RIGHT column: Order Summary + Payment ────────────────────────── --}}
            <div class="col-lg-5">

                {{-- Order Summary --}}
                <div class="card mb-3">
                    <div class="card-header">
                        <span class="checkout-step-heading">Order Summary</span>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Product</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-end pe-3">Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($cart as $item)
                                    <tr>
                                        <td class="ps-3">{{ $item['name'] }}</td>
                                        <td class="text-center">{{ $item['quantity'] }}</td>
                                        <td class="text-end pe-3">${{ number_format($item['price'] * $item['quantity'], 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="px-3 py-2 border-top">
                            <div class="d-flex justify-content-between small">
                                <span>Subtotal</span>
                                <span id="summary-subtotal">${{ number_format($subtotal, 2) }}</span>
                            </div>
                            <div class="d-flex justify-content-between small">
                                <span>Shipping</span>
                                <span id="summary-shipping">&mdash;</span>
                            </div>
                            <div class="d-flex justify-content-between fw-bold mt-1">
                                <span>Total</span>
                                <span id="summary-total">&mdash;</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Payment Section (revealed after "Review & Pay") --}}
                <div id="payment-section" class="card">
                    <div class="card-header">
                        <span class="checkout-step-heading">3 &mdash; Payment</span>
                    </div>
                    <div class="card-body">
                        <p class="small text-muted mb-3">
                            Your card details are handled securely by Stripe.
                            Your card number never touches our server.
                        </p>
                        <div id="payment-element">
                            {{-- Stripe Elements mounts here --}}
                        </div>
                        <div id="payment-message" class="alert alert-danger mt-2" style="display:none;"></div>
                        <button id="pay-button" class="btn btn-success w-100 mt-3" disabled>
                            Pay <span id="pay-amount">&mdash;</span>
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
        (function () {
            const sessionUrl    = "{{ route('checkout.session.store') }}";
            const placeOrderUrl = "{{ route('checkout.place-order') }}";
            const stripeKey     = "{{ config('services.stripe.key') }}";
            const csrfToken     = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            const stripe = stripeKey ? Stripe(stripeKey) : null;
            let elements;

            // ── Toggle new-address form on/off based on saved-address selection ──
            document.querySelectorAll('.saved-addr-radio').forEach(function (radio) {
                radio.addEventListener('change', function () {
                    document.getElementById('new-address-form').style.display =
                        (this.value === '') ? 'block' : 'none';
                });
            });

            // ── "Review & Pay" click ──────────────────────────────────────────────
            document.getElementById('review-pay-btn').addEventListener('click', async function () {
                const btn = this;
                btn.disabled = true;
                btn.textContent = 'Processing…';
                hideError();

                try {
                    // Step 1: Save address + shipping to session
                    const sessionRes = await fetch(sessionUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN':  csrfToken,
                            'Accept':        'application/json',
                        },
                        body: JSON.stringify(collectFormData()),
                    });

                    if (!sessionRes.ok) {
                        const err = await sessionRes.json().catch(function () { return {}; });
                        showError(err.message || 'Please check your address and shipping selection.');
                        btn.disabled = false;
                        btn.textContent = 'Review & Pay';
                        return;
                    }

                    const sessionData = await sessionRes.json();
                    updateSummary(sessionData);

                    // Step 2: Create PaymentIntent
                    const orderRes = await fetch(placeOrderUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN':  csrfToken,
                            'Accept':        'application/json',
                        },
                    });

                    if (!orderRes.ok) {
                        const err = await orderRes.json().catch(function () { return {}; });
                        showError(err.error || 'Could not initialise payment. Please try again.');
                        btn.disabled = false;
                        btn.textContent = 'Review & Pay';
                        return;
                    }

                    const orderData = await orderRes.json();

                    // Step 3: Mount Stripe Elements
                    if (!stripe) {
                        showError('Payment is temporarily unavailable.');
                        btn.disabled = false;
                        btn.textContent = 'Review & Pay';
                        return;
                    }

                    elements = stripe.elements({ clientSecret: orderData.client_secret });
                    const paymentElement = elements.create('payment');
                    paymentElement.mount('#payment-element');

                    // Reveal payment panel, lock form inputs
                    document.getElementById('payment-section').style.display = 'block';
                    document.getElementById('pay-amount').textContent = '$' + Number(sessionData.total).toFixed(2);
                    document.getElementById('pay-button').disabled = false;
                    btn.style.display = 'none';

                    document.querySelectorAll('#checkout-form-col input').forEach(function (el) {
                        el.disabled = true;
                    });

                } catch (e) {
                    showError('Something went wrong. Please try again.');
                    btn.disabled = false;
                    btn.textContent = 'Review & Pay';
                }
            });

            // ── "Pay" click ───────────────────────────────────────────────────────
            document.getElementById('pay-button').addEventListener('click', async function () {
                if (!elements || !stripe) return;
                this.disabled = true;
                this.textContent = 'Processing payment…';

                const { error } = await stripe.confirmPayment({
                    elements,
                    confirmParams: {
                        return_url: window.location.origin + '/checkout/success',
                    },
                });

                if (error) {
                    showPaymentMessage(error.message);
                    this.disabled = false;
                    this.innerHTML = 'Pay <span id="pay-amount">' +
                        document.getElementById('summary-total').textContent + '</span>';
                }
            });

            // ── Helpers ──────────────────────────────────────────────────────────

            function collectFormData() {
                const addrRadio = document.querySelector('input[name="address_id"]:checked');
                const data      = {};

                if (addrRadio && addrRadio.value !== '') {
                    data.address_id = addrRadio.value;
                } else {
                    ['name', 'address_line1', 'address_line2', 'city', 'state', 'postal_code', 'country']
                        .forEach(function (field) {
                            const el = document.getElementById(field);
                            if (el) data[field] = el.value;
                        });
                }

                const methodRadio = document.querySelector('input[name="method"]:checked');
                if (methodRadio) data.method = methodRadio.value;

                return data;
            }

            function updateSummary(data) {
                document.getElementById('summary-shipping').textContent =
                    '$' + Number(data.shipping_cost).toFixed(2);
                document.getElementById('summary-total').textContent =
                    '$' + Number(data.total).toFixed(2);
            }

            function showError(msg) {
                const el = document.getElementById('form-error');
                el.textContent = msg;
                el.style.display = 'block';
            }

            function hideError() {
                document.getElementById('form-error').style.display = 'none';
            }

            function showPaymentMessage(msg) {
                const el = document.getElementById('payment-message');
                el.textContent = msg;
                el.style.display = 'block';
            }
        })();
    </script>
</body>

</html>
