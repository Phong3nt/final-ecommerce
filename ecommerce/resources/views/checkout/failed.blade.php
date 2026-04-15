<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Failed — Order #{{ $order->id }}</title>
</head>
<body>
<div>
    <h1>Payment Failed</h1>

    <p>
        @if ($status === 'requires_payment_method')
            Your payment could not be processed. Please check your payment details and try again.
        @elseif ($status === 'processing')
            Your payment is still being processed. You will receive a confirmation email shortly.
        @else
            Your payment was unsuccessful. Please try again or use a different payment method.
        @endif
    </p>

    <p>Order reference: <strong>#{{ $order->id }}</strong></p>

    <p>Reason: <span data-status="{{ $status }}">{{ $status }}</span></p>

    <a href="{{ route('checkout.review') }}">Retry Payment</a>
    <a href="{{ route('products.index') }}">Continue Shopping</a>
</div>
</body>
</html>
