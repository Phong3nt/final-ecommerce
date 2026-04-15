<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout — Shipping Method</title>
</head>

<body>
    <h1>Shipping Method</h1>

    <a href="{{ route('checkout.address') }}">&larr; Back to Address</a>

    @if ($errors->any())
        <ul class="errors">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    @if (session('error'))
        <p class="error">{{ session('error') }}</p>
    @endif

    <form method="POST" action="{{ route('checkout.shipping.store') }}">
        @csrf

        <fieldset>
            <legend>Select a shipping option</legend>

            @foreach ($shippingOptions as $key => $option)
                <label>
                    <input
                        type="radio"
                        name="method"
                        value="{{ $key }}"
                        {{ $selected === $key ? 'checked' : '' }}
                    >
                    {{ $option['label'] }}
                    — ${{ number_format($option['cost'], 2) }}
                    ({{ $option['days'] }})
                </label>
                <br>
            @endforeach
        </fieldset>

        <hr>

        <p>Order Subtotal: <span id="order-subtotal">${{ number_format($orderTotal, 2) }}</span></p>
        <p>Shipping: <span id="shipping-cost">—</span></p>
        <p><strong>Grand Total: <span id="grand-total">—</span></strong></p>

        <button type="submit">Continue to Review</button>
    </form>

    <script>
        (function () {
            const costs = @json(array_column($shippingOptions, 'cost', null));
            const keys  = @json(array_keys($shippingOptions));
            const costMap = {};
            keys.forEach((k, i) => { costMap[k] = costs[i]; });

            const subtotal = {{ $orderTotal }};
            const shippingEl = document.getElementById('shipping-cost');
            const grandEl    = document.getElementById('grand-total');

            function updateTotals(cost) {
                shippingEl.textContent = '$' + cost.toFixed(2);
                grandEl.textContent    = '$' + (subtotal + cost).toFixed(2);
            }

            document.querySelectorAll('input[name="method"]').forEach(function (radio) {
                radio.addEventListener('change', function () {
                    updateTotals(costMap[this.value]);
                });

                if (radio.checked) {
                    updateTotals(costMap[radio.value]);
                }
            });
        })();
    </script>
</body>

</html>
