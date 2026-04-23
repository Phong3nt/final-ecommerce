@extends('layouts.app')

@section('title', 'Order #{{ $order->id }} — E-Commerce')

@push('styles')
    <style>
        /* IMP-023: order status badges */
        .status-pending {
            background: #fef3c7;
            color: #92400e;
            padding: 2px 10px;
            border-radius: 9999px;
            font-size: .8rem;
            font-weight: 600;
        }

        .status-paid {
            background: #d1fae5;
            color: #065f46;
            padding: 2px 10px;
            border-radius: 9999px;
            font-size: .8rem;
            font-weight: 600;
        }

        .status-failed {
            background: #fee2e2;
            color: #991b1b;
            padding: 2px 10px;
            border-radius: 9999px;
            font-size: .8rem;
            font-weight: 600;
        }

        .status-cancelled {
            background: #f3f4f6;
            color: #374151;
            padding: 2px 10px;
            border-radius: 9999px;
            font-size: .8rem;
            font-weight: 600;
        }

        .status-processing {
            background: #e0e7ff;
            color: #3730a3;
            padding: 2px 10px;
            border-radius: 9999px;
            font-size: .8rem;
            font-weight: 600;
        }

        .status-shipped {
            background: #ede9fe;
            color: #6d28d9;
            padding: 2px 10px;
            border-radius: 9999px;
            font-size: .8rem;
            font-weight: 600;
        }

        .status-delivered {
            background: #d1fae5;
            color: #065f46;
            padding: 2px 10px;
            border-radius: 9999px;
            font-size: .8rem;
            font-weight: 600;
        }

        .status-refunded {
            background: #eff6ff;
            color: #1d4ed8;
            padding: 2px 10px;
            border-radius: 9999px;
            font-size: .8rem;
            font-weight: 600;
        }

        /* IMP-011: keep — Visual progress stepper */
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

        .imp011-ts {
            margin-top: .2rem;
            font-size: .72rem;
            color: #6b7280;
            text-align: center;
            line-height: 1.3;
        }

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
@endpush

@section('content')
    <div x-data x-init="$el.classList.add('fade-in')">

        {{-- Page header --}}
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h1 class="h3 fw-bold mb-1">Order #{{ $order->id }}</h1>
                <p class="text-muted mb-0">
                    Placed on {{ $order->created_at->format('d M Y, H:i') }}
                    &mdash;
                    <span class="status-{{ $order->status }}">{{ ucfirst($order->status) }}</span>
                </p>
            </div>
            <a href="{{ route('orders.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Back to Orders
            </a>
        </div>

        {{-- IMP-011: Order Status --}}
        <div class="card shadow-sm border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3">Order Status</h5>

                @php
                    $isCancelled = in_array($order->status, ['cancelled']);
                    $isRefunded = in_array($order->status, ['refunded']);

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
                    @foreach ($steps as $step)
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
            </div>
        </div>

        <div class="row g-4">

            {{-- Items --}}
            <div class="col-lg-8">
                <div class="card shadow-sm border-0 rounded-3 mb-4">
                    <div class="card-header bg-transparent fw-bold border-bottom py-3">
                        Items
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-end">Unit Price</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($order->items as $item)
                                    <tr>
                                        <td>{{ $item->product_name }}</td>
                                        <td class="text-center">{{ $item->quantity }}</td>
                                        <td class="text-end">${{ number_format($item->unit_price, 2) }}</td>
                                        <td class="text-end">${{ number_format($item->subtotal, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    {{-- Order summary --}}
                    <div class="card-footer bg-transparent border-top pt-3 pb-3">
                        <div class="d-flex justify-content-end">
                            <div style="min-width:240px;">
                                <div class="d-flex justify-content-between mb-1 text-muted small">
                                    <span>Subtotal</span>
                                    <span>${{ number_format($order->subtotal, 2) }}</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2 text-muted small">
                                    <span>Shipping ({{ $order->shipping_label }})</span>
                                    <span>${{ number_format($order->shipping_cost, 2) }}</span>
                                </div>
                                <div class="d-flex justify-content-between fw-bold border-top pt-2">
                                    <span>Total</span>
                                    <span>${{ number_format($order->total, 2) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sidebar: address + payment --}}
            <div class="col-lg-4">

                {{-- Shipping Address --}}
                <div class="card shadow-sm border-0 rounded-3 mb-3">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3">
                            <i class="bi bi-geo-alt me-2 text-primary"></i>Shipping Address
                        </h6>
                        <address class="mb-0 text-muted small" style="font-style:normal;line-height:1.7;">
                            <strong class="text-body">{{ $order->address['name'] }}</strong><br>
                            {{ $order->address['address_line1'] }}
                            @if (!empty($order->address['address_line2']))
                                <br>{{ $order->address['address_line2'] }}
                            @endif
                            <br>{{ $order->address['city'] }}, {{ $order->address['state'] }}
                            {{ $order->address['postal_code'] }}<br>
                            {{ $order->address['country'] }}
                        </address>
                    </div>
                </div>

                {{-- Payment Method --}}
                <div class="card shadow-sm border-0 rounded-3 mb-3">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3">
                            <i class="bi bi-credit-card me-2 text-primary"></i>Payment Method
                        </h6>
                        <p class="mb-1 text-muted small">Stripe &mdash; PaymentIntent</p>
                        @if ($order->stripe_payment_intent_id)
                            <p class="mb-0 text-muted small">{{ $order->stripe_payment_intent_id }}</p>
                        @endif
                    </div>
                </div>

                {{-- Cancel order (pending only) --}}
                @if ($order->status === 'pending')
                    <div class="card shadow-sm border-0 rounded-3 border-danger-subtle">
                        <div class="card-body p-4">
                            <h6 class="fw-bold mb-2 text-danger">Cancel Order</h6>
                            <p class="text-muted small mb-3">This action cannot be undone.</p>
                            <form method="POST" action="{{ route('orders.cancel', $order) }}" x-data
                                @submit.prevent="if(confirm('Are you sure you want to cancel this order? This cannot be undone.')) $el.submit()">
                                @csrf
                                <button type="submit" class="btn btn-danger btn-sm w-100">
                                    <i class="bi bi-x-circle me-1"></i>Cancel Order
                                </button>
                            </form>
                        </div>
                    </div>
                @endif

            </div>
        </div>

    </div>
@endsection