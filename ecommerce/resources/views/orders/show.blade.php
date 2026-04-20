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

        /* IMP-011: Visual progress stepper */
        .imp011-stepper {
            display: flex;
            align-items: flex-start;
            gap: 0;
            margin: 1.5rem 0 2rem;
            overflow-x: auto;
            padding-bottom: .5rem;
        }

        .imp011-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
            min-width: 90px;
            position: relative;
        }

        /* Connector line between steps */
        .imp011-step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 18px;
            left: calc(50% + 18px);
            right: calc(-50% + 18px);
            height: 3px;
            background: #e5e7eb;
            z-index: 0;
            transition: background .3s;
        }

        .imp011-step.imp011-done:not(:last-child)::after,
        .imp011-step.imp011-active:not(:last-child)::after {
            background: #10b981;
        }

        /* Circle icon */
        .imp011-circle {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: 3px solid #e5e7eb;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            position: relative;
            z-index: 1;
            transition: border-color .3s, background .3s;
        }

        .imp011-step.imp011-done .imp011-circle {
            border-color: #10b981;
            background: #10b981;
            color: #fff;
        }

        .imp011-step.imp011-active .imp011-circle {
            border-color: #3b82f6;
            background: #3b82f6;
            color: #fff;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, .2);
        }

        .imp011-step.imp011-cancelled .imp011-circle {
            border-color: #ef4444;
            background: #ef4444;
            color: #fff;
        }

        /* Step label */
        .imp011-label {
            margin-top: .5rem;
            font-size: .8rem;
            font-weight: 600;
            color: #9ca3af;
            text-align: center;
            line-height: 1.2;
        }

        .imp011-step.imp011-done .imp011-label,
        .imp011-step.imp011-active .imp011-label {
            color: #111827;
        }

        .imp011-step.imp011-cancelled .imp011-label {
            color: #ef4444;
        }

        /* Timestamp below label */
        .imp011-ts {
            margin-top: .2rem;
            font-size: .72rem;
            color: #6b7280;
            text-align: center;
            line-height: 1.3;
        }

        /* Cancelled / refunded banner */
        .imp011-alert {
            display: flex;
            align-items: center;
            gap: .6rem;
            padding: .75rem 1rem;
            border-radius: 8px;
            margin: 1rem 0;
            font-size: .9rem;
        }

        .imp011-alert--cancelled {
            background: #fef2f2;
            border: 1px solid #fca5a5;
            color: #991b1b;
        }

        .imp011-alert--refunded {
            background: #eff6ff;
            border: 1px solid #93c5fd;
            color: #1d4ed8;
        }
    </style>
</head>

<body>
    @include('partials.toast')

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

    {{-- IMP-011: Visual progress stepper --}}
    <h2>Order Status</h2>

    @php
        $isCancelled = in_array($order->status, ['cancelled']);
        $isRefunded = in_array($order->status, ['refunded']);

        // Determine which normal step is active (furthest reached, not cancelled/refunded)
        $activeStep = 'placed';
        if ($order->delivered_at)
            $activeStep = 'delivered';
        elseif ($order->shipped_at)
            $activeStep = 'shipped';
        elseif ($order->processing_at || in_array($order->status, ['paid', 'processing']))
            $activeStep = 'processing';

        $steps = [
            ['key' => 'placed', 'label' => 'Placed', 'icon' => '📋', 'ts' => $order->created_at],
            ['key' => 'processing', 'label' => 'Processing', 'icon' => '⚙️', 'ts' => $order->processing_at ?? ($order->status === 'paid' ? $order->updated_at : null)],
            ['key' => 'shipped', 'label' => 'Shipped', 'icon' => '🚚', 'ts' => $order->shipped_at],
            ['key' => 'delivered', 'label' => 'Delivered', 'icon' => '✅', 'ts' => $order->delivered_at],
        ];

        $stepOrder = ['placed', 'processing', 'shipped', 'delivered'];
        $activeIdx = array_search($activeStep, $stepOrder);
    @endphp

    @if ($isCancelled)
        <div class="imp011-alert imp011-alert--cancelled" data-imp011="cancelled-banner">
            <span aria-hidden="true">✖</span>
            <span>This order was cancelled
                @if ($order->cancelled_at)
                    on {{ $order->cancelled_at->format('d M Y, H:i') }}
                @endif
            </span>
        </div>
    @elseif ($isRefunded)
        <div class="imp011-alert imp011-alert--refunded" data-imp011="refunded-banner">
            <span aria-hidden="true">↩</span>
            <span>This order has been refunded
                @if ($order->refunded_at)
                    on {{ $order->refunded_at->format('d M Y, H:i') }}
                @endif
            </span>
        </div>
    @endif

    <div class="imp011-stepper" data-imp011="stepper" role="list" aria-label="Order progress">
        @foreach ($steps as $idx => $step)
            @php
                $stepIdx = array_search($step['key'], $stepOrder);
                if ($isCancelled || $isRefunded) {
                    $stateClass = 'imp011-cancelled';
                } elseif ($stepIdx < $activeIdx) {
                    $stateClass = 'imp011-done';
                } elseif ($stepIdx === $activeIdx) {
                    $stateClass = 'imp011-active';
                } else {
                    $stateClass = '';
                }
            @endphp
            <div class="imp011-step {{ $stateClass }}" data-imp011="step" data-imp011-key="{{ $step['key'] }}"
                role="listitem"
                aria-label="{{ $step['label'] }}{{ $stateClass === 'imp011-done' ? ' (completed)' : ($stateClass === 'imp011-active' ? ' (current)' : '') }}">
                <div class="imp011-circle" data-imp011="circle" aria-hidden="true">
                    {{ $step['icon'] }}
                </div>
                <span class="imp011-label" data-imp011="label">{{ $step['label'] }}</span>
                @if ($step['ts'])
                    <span class="imp011-ts"
                        data-imp011="timestamp">{{ $step['ts']->format('d M Y') }}<br>{{ $step['ts']->format('H:i') }}</span>
                @endif
            </div>
        @endforeach
    </div>

    <p style="margin-top:2rem"><a href="{{ route('orders.index') }}">&larr; Back to Order History</a></p>

    @if ($order->status === 'pending')
        <form method="POST" action="{{ route('orders.cancel', $order) }}" style="margin-top:1rem"
            onsubmit="return confirm('Are you sure you want to cancel this order? This cannot be undone.')">
            @csrf
            <button type="submit"
                style="background:#dc2626;color:#fff;padding:.5rem 1.25rem;border:none;border-radius:6px;cursor:pointer;font-size:1rem">
                Cancel Order
            </button>
        </form>
    @endif

</body>

</html>