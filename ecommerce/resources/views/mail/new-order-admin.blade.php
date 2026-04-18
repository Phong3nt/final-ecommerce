<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
</head>

<body style="font-family: sans-serif; color: #333;">
    <h2>New Order Received</h2>
    <p>A new order <strong>#{{ $order->id }}</strong> has been placed and requires your attention.</p>
    <ul>
        <li><strong>Total:</strong> ${{ number_format($order->total, 2) }}</li>
        <li><strong>Status:</strong> {{ ucfirst($order->status) }}</li>
    </ul>
    <p>Log in to the admin panel to review and process this order.</p>
</body>

</html>