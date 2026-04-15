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

        <p class="order-total">Order Total: $<span id="order-total">{{ number_format($total, 2) }}</span></p>
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
                        })
                        .catch(function () {});
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
                        })
                        .catch(function () {});
                });
            });
        });
    </script>
</body>

</html>