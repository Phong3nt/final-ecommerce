<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout — Order Review</title>
    <script src="https://js.stripe.com/v3/"></script>
</head>

<body>
    @include('partials.toast')
    <h1>Order Review</h1>

    <a href="{{ route('checkout.shipping') }}">&larr; Back to Shipping</a>

    <section id="address-summary">
        <h2>Shipping Address</h2>
        <p>{{ $address['name'] }}</p>
        <p>{{ $address['address_line1'] }}@if($address['address_line2']), {{ $address['address_line2'] }}@endif</p>
        <p>{{ $address['city'] }}, {{ $address['state'] }} {{ $address['postal_code'] }}</p>
        <p>{{ $address['country'] }}</p>
    </section>

    <section id="shipping-summary">
        <h2>Shipping Method</h2>
        <p>{{ $shipping['label'] }} — ${{ number_format($shipping['cost'], 2) }}</p>
    </section>

    <section id="cart-summary">
        <h2>Order Items</h2>
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Qty</th>
                    <th>Unit Price</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($cart as $item)
                    <tr>
                        <td>{{ $item['name'] }}</td>
                        <td>{{ $item['quantity'] }}</td>
                        <td>${{ number_format($item['price'], 2) }}</td>
                        <td>${{ number_format($item['price'] * $item['quantity'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <p>Subtotal: <span id="subtotal">${{ number_format($subtotal, 2) }}</span></p>
        <p>Shipping: <span id="shipping-cost">${{ number_format($shipping['cost'], 2) }}</span></p>
        @if($discount > 0)
            <p id="discount-line">Discount ({{ $coupon['code'] }}): -${{ number_format($discount, 2) }}</p>
        @endif
        <p><strong>Total: <span id="grand-total">${{ number_format($total, 2) }}</span></strong></p>
    </section>

    <section id="payment-section">
        <h2>Payment</h2>
        <p>Your card details are handled securely by Stripe. Your card number never touches our server.</p>

        <div id="payment-element">
            <!-- Stripe Elements will inject card fields here -->
        </div>

        <div id="payment-message" style="display:none;"></div>

        <button id="pay-button" type="button">Pay ${{ number_format($total, 2) }}</button>
    </section>

    <script>
        (function () {
            const placeOrderUrl = "{{ route('checkout.place-order') }}";
            const stripeKey = "{{ config('services.stripe.key') }}";
            const csrfToken = "{{ csrf_token() }}";

            if (!stripeKey) {
                document.getElementById('payment-message').textContent =
                    'Payment is temporarily unavailable.';
                document.getElementById('payment-message').style.display = 'block';
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
                    if (data.error) {
                        showMessage(data.error);
                        return;
                    }

                    // Step 2: Mount Stripe Payment Element using the client_secret
                    elements = stripe.elements({ clientSecret: data.client_secret });
                    const paymentElement = elements.create('payment');
                    paymentElement.mount('#payment-element');
                })
                .catch(function () { showMessage('Could not initialise payment.'); });

            // Step 3: On button click, confirm the payment via Stripe.js (card data stays in Stripe)
            document.getElementById('pay-button').addEventListener('click', async function () {
                if (!elements) return;

                const { error } = await stripe.confirmPayment({
                    elements,
                    confirmParams: {
                        return_url: window.location.origin + '/checkout/success',
                    },
                });

                if (error) {
                    showMessage(error.message);
                }
            });

            function showMessage(msg) {
                const el = document.getElementById('payment-message');
                el.textContent = msg;
                el.style.display = 'block';
            }
        })();
    </script>
</body>

</html>