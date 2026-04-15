<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #{{ $order->id }}</title>
    <style>
        body {
            font-family: sans-serif;
            max-width: 900px;
            margin: 40px auto;
            padding: 0 1rem;
        }

        h1 {
            margin-bottom: .25rem;
        }

        .meta {
            color: #6b7280;
            margin-bottom: 2rem;
            font-size: .95rem;
        }

        h2 {
            margin: 1.5rem 0 .75rem;
            font-size: 1.1rem;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: .4rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1rem;
        }

        th,
        td {
            text-align: left;
            padding: .55rem .75rem;
            border-bottom: 1px solid #e5e7eb;
        }

        th {
            background: #f9fafb;
            font-weight: 600;
        }

        td.right,
        th.right {
            text-align: right;
        }

        .status-pending {
            color: #92400e;
            background: #fef3c7;
            padding: 2px 8px;
            border-radius: 9999px;
            font-size: .85rem;
        }

        .status-paid {
            color: #065f46;
            background: #d1fae5;
            padding: 2px 8px;
            border-radius: 9999px;
            font-size: .85rem;
        }

        .status-failed {
            color: #991b1b;
            background: #fee2e2;
            padding: 2px 8px;
            border-radius: 9999px;
            font-size: .85rem;
        }

        .status-cancelled {
            color: #374151;
            background: #f3f4f6;
            padding: 2px 8px;
            border-radius: 9999px;
            font-size: .85rem;
        }

        .summary {
            max-width: 320px;
            margin-left: auto;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: .3rem 0;
        }

        .summary-total {
            font-weight: 700;
            border-top: 2px solid #111;
            margin-top: .25rem;
            padding-top: .4rem;
        }

        address {
            font-style: normal;
            line-height: 1.6;
        }

        .payment-method {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: .75rem 1rem;
            display: inline-block;
        }
    </style>
</head>

<body>

    <h1>Order #{{ $order->id }}</h1>
    <p class="meta">
        Placed on {{ $order->created_at->format('d M Y, H:i') }} &mdash;
        <span class="status-{{ $order->status }}">{{ ucfirst($order->status) }}</span>
    </p>

    {{-- Items --}}
    <h2>Items</h2>
    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th class="right">Qty</th>
                <th class="right">Unit Price</th>
                <th class="right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
                <tr>
                    <td>{{ $item->product_name }}</td>
                    <td class="right">{{ $item->quantity }}</td>
                    <td class="right">${{ number_format($item->unit_price, 2) }}</td>
                    <td class="right">${{ number_format($item->subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Order summary --}}
    <div class="summary">
        <div class="summary-row"><span>Subtotal</span><span>${{ number_format($order->subtotal, 2) }}</span></div>
        <div class="summary-row"><span>Shipping
                ({{ $order->shipping_label }})</span><span>${{ number_format($order->shipping_cost, 2) }}</span></div>
        <div class="summary-row summary-total"><span>Total</span><span>${{ number_format($order->total, 2) }}</span>
        </div>
    </div>

    {{-- Shipping address --}}
    <h2>Shipping Address</h2>
    <address>
        {{ $order->address['name'] }}<br>
        {{ $order->address['address_line1'] }}
        @if (!empty($order->address['address_line2']))
            <br>{{ $order->address['address_line2'] }}
        @endif
        <br>{{ $order->address['city'] }}, {{ $order->address['state'] }} {{ $order->address['postal_code'] }}<br>
        {{ $order->address['country'] }}
    </address>

    {{-- Payment method --}}
    <h2>Payment Method</h2>
    <div class="payment-method">
        Stripe &mdash; PaymentIntent
        @if ($order->stripe_payment_intent_id)
            <br><small style="color:#6b7280">{{ $order->stripe_payment_intent_id }}</small>
        @endif
    </div>

    {{-- Status timeline --}}
    <h2>Status</h2>
    <p>
        <span class="status-{{ $order->status }}">{{ ucfirst($order->status) }}</span>
        &mdash; last updated {{ $order->updated_at->format('d M Y, H:i') }}
    </p>

    <p style="margin-top:2rem"><a href="{{ route('orders.index') }}">&larr; Back to Order History</a></p>

</body>

</html>