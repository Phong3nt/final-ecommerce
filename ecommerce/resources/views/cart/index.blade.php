<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart</title>
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
                        <td class="item-qty">{{ $item['quantity'] }}</td>
                        <td class="item-subtotal">${{ number_format($item['price'] * $item['quantity'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <p class="order-total">Order Total: $<span id="order-total">{{ number_format($total, 2) }}</span></p>
    @endif
</body>

</html>
