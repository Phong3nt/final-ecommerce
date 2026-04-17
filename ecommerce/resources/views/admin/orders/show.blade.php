<!DOCTYPE html>
<html>

<head>
    <title>Admin — Order #{{ $order->id }}</title>
    <style>
        body {
            font-family: sans-serif;
            margin: 2rem;
            background: #f5f5f5;
        }

        h1 {
            margin-bottom: .25rem;
        }

        .meta {
            color: #6b7280;
            font-size: .95rem;
            margin-bottom: 1.5rem;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.25rem;
            margin-bottom: 1.5rem;
        }

        .card {
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 1rem 1.25rem;
        }

        .card h2 {
            font-size: 1rem;
            margin: 0 0 .75rem;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: .4rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            text-align: left;
            padding: .55rem .75rem;
            border-bottom: 1px solid #e5e7eb;
            font-size: .9rem;
        }

        th {
            background: #f8f9fa;
            font-weight: 700;
        }

        td.right,
        th.right {
            text-align: right;
        }

        tr:last-child td {
            border-bottom: none;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: .3rem 0;
            font-size: .9rem;
        }

        .summary-total {
            font-weight: 700;
            border-top: 2px solid #111;
            margin-top: .25rem;
            padding-top: .4rem;
        }

        address {
            font-style: normal;
            line-height: 1.7;
            font-size: .9rem;
        }

        .badge {
            display: inline-block;
            padding: .2rem .55rem;
            border-radius: 20px;
            font-size: .8rem;
            font-weight: 600;
            text-transform: capitalize;
        }

        .badge-pending {
            background: #fff3cd;
            color: #856404;
        }

        .badge-paid {
            background: #d1e7dd;
            color: #0f5132;
        }

        .badge-processing {
            background: #cfe2ff;
            color: #084298;
        }

        .badge-shipped {
            background: #e0cffc;
            color: #432874;
        }

        .badge-delivered {
            background: #d1e7dd;
            color: #0f5132;
        }

        .badge-cancelled {
            background: #f8d7da;
            color: #842029;
        }

        .badge-failed {
            background: #f8d7da;
            color: #842029;
        }

        .timeline {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .timeline-step {
            padding: .45rem 0 .45rem 1.4rem;
            border-left: 3px solid #e5e7eb;
            margin-bottom: .2rem;
            font-size: .9rem;
        }

        .timeline-step--done {
            border-left-color: #10b981;
        }

        .timeline-step--pending {
            color: #9ca3af;
        }

        .timeline-ts {
            margin-left: .6rem;
            font-size: .82rem;
            color: #6b7280;
        }

        .btn {
            display: inline-block;
            padding: .4rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: .875rem;
            text-decoration: none;
        }

        .btn-primary {
            background: #0d6efd;
            color: #fff;
        }

        .btn-secondary {
            background: #6c757d;
            color: #fff;
        }

        .btn-sm {
            padding: .3rem .75rem;
            font-size: .82rem;
        }

        .alert-success {
            background: #d1e7dd;
            border: 1px solid #badbcc;
            color: #0f5132;
            padding: .75rem 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }

        .alert-error {
            background: #f8d7da;
            border: 1px solid #f5c2c7;
            color: #842029;
            padding: .75rem 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }

        .status-form {
            margin-top: .75rem;
            display: flex;
            gap: .5rem;
            align-items: center;
        }

        .status-form select {
            padding: .35rem .6rem;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: .875rem;
        }
    </style>
</head>

<body>

    <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary btn-sm" style="margin-bottom:1rem;">&larr; All
        Orders</a>

    <h1>Order #{{ $order->id }}</h1>
    <p class="meta">
        Placed {{ $order->created_at->format('d M Y, H:i') }}
        &mdash; <span class="badge badge-{{ $order->status }}">{{ $order->status }}</span>
    </p>

    @if(session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert-error">{{ session('error') }}</div>
    @endif

    <div class="grid">
        {{-- Customer --}}
        <div class="card">
            <h2>Customer</h2>
            <p style="margin:0;font-size:.9rem;">
                <strong>{{ $order->user?->name ?? '—' }}</strong><br>
                {{ $order->user?->email ?? '—' }}
            </p>
        </div>

        {{-- Shipping Address --}}
        <div class="card">
            <h2>Shipping Address</h2>
            <address>
                {{ $order->address['name'] }}<br>
                {{ $order->address['address_line1'] }}
                @if(!empty($order->address['address_line2']))
                    <br>{{ $order->address['address_line2'] }}
                @endif
                <br>{{ $order->address['city'] }}, {{ $order->address['state'] ?? '' }}
                {{ $order->address['postal_code'] }}<br>
                {{ $order->address['country'] }}
            </address>
        </div>

        {{-- Payment --}}
        <div class="card">
            <h2>Payment</h2>
            <p style="margin:0;font-size:.9rem;">
                <strong>Method:</strong> Stripe PaymentIntent<br>
                <strong>Status:</strong> <span class="badge badge-{{ $order->status }}">{{ $order->status }}</span><br>
                @if($order->stripe_payment_intent_id)
                    <strong>Intent:</strong> <small style="color:#6b7280">{{ $order->stripe_payment_intent_id }}</small>
                @endif
            </p>
        </div>

        {{-- Status Timeline --}}
        <div class="card">
            <h2>Status History</h2>
            <ol class="timeline">
                <li class="timeline-step timeline-step--done">
                    <strong>Placed</strong>
                    <span class="timeline-ts">{{ $order->created_at->format('d M Y, H:i') }}</span>
                </li>
                <li
                    class="timeline-step {{ $order->processing_at ? 'timeline-step--done' : 'timeline-step--pending' }}">
                    <strong>Processing</strong>
                    @if($order->processing_at)
                        <span class="timeline-ts">{{ $order->processing_at->format('d M Y, H:i') }}</span>
                    @endif
                </li>
                <li class="timeline-step {{ $order->shipped_at ? 'timeline-step--done' : 'timeline-step--pending' }}">
                    <strong>Shipped</strong>
                    @if($order->shipped_at)
                        <span class="timeline-ts">{{ $order->shipped_at->format('d M Y, H:i') }}</span>
                    @endif
                </li>
                <li class="timeline-step {{ $order->delivered_at ? 'timeline-step--done' : 'timeline-step--pending' }}">
                    <strong>Delivered</strong>
                    @if($order->delivered_at)
                        <span class="timeline-ts">{{ $order->delivered_at->format('d M Y, H:i') }}</span>
                    @endif
                </li>
            </ol>

            {{-- Status update form (OH-003 reuse) --}}
            <form method="POST" action="{{ route('admin.orders.status', $order) }}" class="status-form">
                @csrf
                @method('PATCH')
                <select name="status">
                    @foreach($updatableStatuses as $s)
                        <option value="{{ $s }}" {{ $order->status === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-primary btn-sm">Update Status</button>
            </form>
        </div>
    </div>

    {{-- Items --}}
    <div class="card" style="margin-bottom:1.25rem;">
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
                @foreach($order->items as $item)
                    <tr>
                        <td>{{ $item->product_name }}</td>
                        <td class="right">{{ $item->quantity }}</td>
                        <td class="right">${{ number_format($item->unit_price, 2) }}</td>
                        <td class="right">${{ number_format($item->subtotal, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Totals --}}
        <div style="max-width:280px;margin-left:auto;margin-top:.75rem;">
            <div class="summary-row"><span>Subtotal</span><span>${{ number_format($order->subtotal, 2) }}</span></div>
            <div class="summary-row"><span>Shipping
                    ({{ $order->shipping_label }})</span><span>${{ number_format($order->shipping_cost, 2) }}</span>
            </div>
            <div class="summary-row summary-total"><span>Total</span><span>${{ number_format($order->total, 2) }}</span>
            </div>
        </div>
    </div>

</body>

</html>