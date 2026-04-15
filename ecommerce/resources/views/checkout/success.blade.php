<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed — #{{ $order->id }}</title>
</head>
<body>
<div>
    <h1>Payment Successful!</h1>
    <p>Thank you for your order. Your payment has been confirmed.</p>

    <h2>Order #{{ $order->id }}</h2>

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
            @foreach ($order->items as $item)
            <tr>
                <td>{{ $item->product_name }}</td>
                <td>{{ $item->quantity }}</td>
                <td>${{ number_format($item->unit_price, 2) }}</td>
                <td>${{ number_format($item->subtotal, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3">Subtotal</td>
                <td>${{ number_format($order->subtotal, 2) }}</td>
            </tr>
            <tr>
                <td colspan="3">{{ $order->shipping_label }}</td>
                <td>${{ number_format($order->shipping_cost, 2) }}</td>
            </tr>
            <tr>
                <td colspan="3"><strong>Total</strong></td>
                <td><strong>${{ number_format($order->total, 2) }}</strong></td>
            </tr>
        </tfoot>
    </table>

    <p>
        Shipping to:
        {{ $order->address['city'] ?? '' }}, {{ $order->address['country'] ?? '' }}
    </p>

    <a href="{{ route('products.index') }}">Continue Shopping</a>
</div>
</body>
</html>
