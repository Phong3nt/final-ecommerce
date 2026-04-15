<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Order Status Update</title>
</head>

<body style="font-family:sans-serif;max-width:600px;margin:40px auto;padding:0 1rem;color:#111">
    <h2>Your Order #{{ $order->id }} has been updated</h2>

    <p>Hi {{ $order->user->name }},</p>

    <p>Good news — your order status has changed to:</p>

    <p style="font-size:1.2rem;font-weight:700;color:#065f46">{{ $statusLabel }}</p>

    <table style="width:100%;border-collapse:collapse;margin:1rem 0">
        @foreach ($order->items as $item)
            <tr style="border-bottom:1px solid #e5e7eb">
                <td style="padding:.5rem">{{ $item->product_name }}</td>
                <td style="padding:.5rem;text-align:right">x{{ $item->quantity }}</td>
                <td style="padding:.5rem;text-align:right">${{ number_format($item->subtotal, 2) }}</td>
            </tr>
        @endforeach
    </table>

    <p><strong>Order Total:</strong> ${{ number_format($order->total, 2) }}</p>

    <p>Thank you for shopping with us.</p>
</body>

</html>