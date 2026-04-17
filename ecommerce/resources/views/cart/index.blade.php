<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>

<body>
    <h1>Your Cart</h1>

    <a href="{{ route('products.index') }}">&larr; Continue Shopping</a>

    @if (session('success'))
        <p class="alert-success">{{ session('success') }}</p>
    @endif

    @if ($errors->has('coupon'))
        <p class="alert-error">{{ $errors->first('coupon') }}</p>
    @endif

    @if (empty($cart))
        <p class="cart-empty">Your cart is empty.</p>
    @else
        <table class="cart-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Unit Price</th>
                    <th>Qty</th>
                    <th>Subtotal</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($cart as $item)
                    <tr class="cart-item" data-product-id="{{ $item['product_id'] }}">
                        <td>
                            @if ($item['slug'])
                                <a href="{{ route('products.show', $item['slug']) }}">{{ $item['name'] }}</a>
                            @else
                                {{ $item['name'] }}
                            @endif
                        </td>
                        <td class="unit-price">${{ number_format($item['price'], 2) }}</td>
                        <td class="item-qty">
                            {{-- SC-003: quantity update form (AJAX or regular submit) --}}
                            <form class="qty-update-form" action="{{ route('cart.update', $item['product_id']) }}"
                                method="POST">
                                @csrf
                                @method('PATCH')
                                <input type="number" name="quantity" value="{{ $item['quantity'] }}" min="1" class="qty-input"
                                    data-product-id="{{ $item['product_id'] }}">
                                <button type="submit" class="qty-update-btn">Update</button>
                            </form>
                        </td>
                        <td class="item-subtotal" id="subtotal-{{ $item['product_id'] }}">
                            ${{ number_format($item['price'] * $item['quantity'], 2) }}</td>
                        <td class="item-actions">
                            {{-- SC-004: remove item form (AJAX or regular submit) --}}
                            <form class="remove-form" action="{{ route('cart.destroy', $item['product_id']) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="remove-btn">Remove</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <p class="order-total">Order Total: $<span id="order-total">{{ number_format($subtotal, 2) }}</span></p>

        {{-- SC-005: coupon discount --}}
        @if($discount > 0)
            <p class="order-discount" id="discount-line">
                Discount ({{ $coupon['code'] }}): -$<span id="discount-amount">{{ number_format($discount, 2) }}</span>
            </p>
            <p class="grand-total"><strong>Grand Total: $<span id="grand-total">{{ number_format($total, 2) }}</span></strong>
            </p>
        @else
            <p class="grand-total" id="grand-total-line" style="display:none;">
                Discount (<span id="coupon-code-label"></span>): -$<span id="discount-amount">0.00</span><br>
                <strong>Grand Total: $<span id="grand-total">{{ number_format($total, 2) }}</span></strong>
            </p>
        @endif

        {{-- SC-005: coupon apply form --}}
        <div class="coupon-section">
            @if($coupon)
                <p>Coupon <strong>{{ $coupon['code'] }}</strong> applied.</p>
                <form class="coupon-remove-form" action="{{ route('cart.coupon.remove') }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit">Remove Coupon</button>
                </form>
            @else
                <form class="coupon-apply-form" action="{{ route('cart.coupon.apply') }}" method="POST">
                    @csrf
                    <input type="text" name="code" placeholder="Coupon code" value="{{ old('code') }}">
                    <button type="submit">Apply Coupon</button>
                </form>
            @endif
        </div>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            document.querySelectorAll('.remove-form').forEach(function (form) {
                form.addEventListener('submit', function (e) {
                    e.preventDefault();
                    var row = form.closest('tr.cart-item');
                    fetch(form.action, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': token,
                        },
                    })
                        .then(function (res) { return res.json(); })
                        .then(function (json) {
                            if (row) row.remove();
                            if (json.order_total !== undefined) {
                                var tot = document.getElementById('order-total');
                                if (tot) tot.textContent = json.order_total;
                            }
                            if (json.discount_amount !== undefined && parseFloat(json.discount_amount) > 0) {
                                var disc = document.getElementById('discount-amount');
                                if (disc) disc.textContent = json.discount_amount;
                                var gt = document.getElementById('grand-total');
                                if (gt) gt.textContent = json.grand_total;
                                var gtLine = document.getElementById('grand-total-line');
                                if (gtLine) gtLine.style.display = '';
                            }
                        })
                        .catch(function () { });
                });
            });

            document.querySelectorAll('.qty-update-form').forEach(function (form) {
                form.addEventListener('submit', function (e) {
                    e.preventDefault();
                    var input = form.querySelector('[name="quantity"]');
                    var productId = parseInt(input.getAttribute('data-product-id'));
                    var quantity = parseInt(inpu t.value);
                    fetch(form.action, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': token,
                        },
                        body: JSON.stringify({ quantity: quantity }),
                    })
                        .then(function (res) { return res.json(); })
                        .then(function (json) {
                            if (json.subtotal !== undefined) {
                                var sub = document.getElementById('subtotal-' + productId);
                                if (sub) sub.textContent = '$' + json.subtotal;
                            }
                            if (json.order_total !== undefined) {
                                var tot = document.getElementById('order-total');
                                if (tot) tot.textContent = json.order_total;
                            }
                            if (json.discount_amount !== undefined && parseFloat(json.discount_amount) > 0) {
                                var disc = document.getElementById('discount-amount');
                                if (disc) disc.textContent = json.discount_amount;
                                var gt = document.getElementById('grand-total');
                                if (gt) gt.textContent = json.grand_total;
                                var gtLine = document.getElementById('grand-total-line');
                                if (gtLine) gtLine.style.display = '';
                            }
                        })
                        .catch(function () { });
                });
            });
        });
    </script>
</body>

</html>