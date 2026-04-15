<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation #{{ $order->id }}</title>
    <style>
        body { font-family: Arial, sans-serif; color: #333; margin: 0; padding: 0; background: #f4f4f4; }
        .wrapper { max-width: 600px; margin: 30px auto; background: #fff; border-radius: 8px; overflow: hidden; }
        .header { background: #1a1a2e; color: #fff; padding: 24px 32px; }
        .header h1 { margin: 0; font-size: 22px; }
        .body { padding: 24px 32px; }
        .section-title { font-size: 16px; font-weight: bold; margin: 20px 0 8px; color: #1a1a2e; border-bottom: 1px solid #eee; padding-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        th { text-align: left; font-size: 13px; color: #888; padding: 6px 0; }
        td { padding: 8px 0; font-size: 14px; border-bottom: 1px solid #f0f0f0; }
        .total-row td { font-weight: bold; font-size: 15px; border-top: 2px solid #eee; border-bottom: none; padding-top: 12px; }
        .meta { font-size: 14px; margin-bottom: 6px; }
        .meta span { font-weight: bold; }
        .delivery-box { background: #f0f7ff; border-left: 4px solid #1a6bcc; padding: 12px 16px; border-radius: 4px; margin: 16px 0; font-size: 14px; }
        .footer { background: #f9f9f9; padding: 16px 32px; font-size: 12px; color: #aaa; text-align: center; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h1>Thank you for your order!</h1>
    </div>
    <div class="body">
        <p class="meta">Hello, <span>{{ $order->user->name }}</span></p>
        <p class="meta">Your order has been received and payment confirmed.</p>

        <div class="section-title">Order Details</div>
        <p class="meta">Order ID: <span>#{{ $order->id }}</span></p>
        <p class="meta">Order Date: <span>{{ $order->created_at->format('F j, Y') }}</span></p>

        <div class="section-title">Items Ordered</div>
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
                    <td colspan="3">Shipping ({{ $order->shipping_label }})</td>
                    <td>${{ number_format($order->shipping_cost, 2) }}</td>
                </tr>
                <tr class="total-row">
                    <td colspan="3">Total</td>
                    <td>${{ number_format($order->total, 2) }}</td>
                </tr>
            </tfoot>
        </table>

        <div class="delivery-box">
            Estimated delivery: <strong>{{ $estimatedDelivery }}</strong>
        </div>

        <div class="section-title">Shipping Address</div>
        <p class="meta">
            {{ $order->address['name'] ?? '' }}<br>
            {{ $order->address['address_line1'] ?? '' }}
            @if (!empty($order->address['address_line2']))
                , {{ $order->address['address_line2'] }}
            @endif
            <br>
            {{ $order->address['city'] ?? '' }}
            @if (!empty($order->address['state']))
                , {{ $order->address['state'] }}
            @endif
            {{ $order->address['postal_code'] ?? '' }}<br>
            {{ $order->address['country'] ?? '' }}
        </p>
    </div>
    <div class="footer">
        &copy; {{ date('Y') }} Our Store. This is an automated confirmation email.
    </div>
</div>
</body>
</html>
