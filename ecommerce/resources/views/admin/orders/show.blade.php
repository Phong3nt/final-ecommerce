@extends('layouts.admin')
{{-- @include('partials.toast') --}}

@section('title', 'Admin — Order #{{ $order->id }}')
@section('page-title', 'Order Detail')

@section('content')
    <div x-data x-init="$el.classList.add('fade-in')">

        {{-- IMP-048: Demo banner + shipping sim progress --}}
        @if ($order->is_demo)
            <div class="alert alert-warning border-warning mb-3 d-flex align-items-center gap-2" role="alert">
                <i class="bi bi-cone-striped fs-5"></i>
                <div><strong>[DEMO]</strong> This is a demo/sandbox order — excluded from revenue and analytics.</div>
            </div>
            <div class="card shadow-sm border-warning border-2 mb-3"
                x-data="shipSimPoller('', '{{ route('admin.demo.status', $order->id) }}', '{{ $order->ship_sim_status }}')">
                <div class="card-header bg-warning-subtle d-flex align-items-center justify-content-between border-0">
                    <span class="fw-semibold"><i class="bi bi-truck me-1"></i> Shipping Simulation <span
                            class="badge bg-warning text-dark ms-1">[DEMO]</span></span>
                    <span class="text-muted small" x-text="isTerminal ? 'Completed' : 'Live'"></span>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center gap-0 overflow-auto pb-2">
                        @php
                            $simSteps = [
                                ['payment_confirmed', 'Payment Confirmed', 'bi-check-circle'],
                                ['preparing', 'Preparing Goods', 'bi-box-seam'],
                                ['picked_up', 'Handed to Courier', 'bi-truck'],
                                ['in_transit', 'On the Way', 'bi-geo-alt'],
                                ['arrived', 'Arrived', 'bi-house-check'],
                            ];
                        @endphp
                        @foreach ($simSteps as [$sKey, $sLabel, $sIcon])
                            <div class="text-center flex-fill" style="min-width:80px">
                                <div :class="{
                                                'bg-success text-white border-success': stepDone('{{ $sKey }}') || (currentStatus === '{{ $sKey }}' && isTerminal && currentStatus !== 'incident'),
                                                'bg-primary text-white border-primary': currentStatus === '{{ $sKey }}' && !isTerminal,
                                                'bg-danger text-white border-danger': currentStatus === 'incident' && '{{ $sKey }}' === 'arrived',
                                                'bg-white text-secondary border-secondary': !stepDone('{{ $sKey }}') && currentStatus !== '{{ $sKey }}'
                                             }"
                                    class="rounded-circle border d-inline-flex align-items-center justify-content-center"
                                    style="width:36px;height:36px">
                                    <i class="bi {{ $sIcon }}"></i>
                                </div>
                                <div class="mt-1" style="font-size:.7rem;font-weight:600">{{ $sLabel }}</div>
                            </div>
                            @if (!$loop->last)
                                <div class="flex-shrink-0 align-self-start mt-3 px-1 text-muted"><i class="bi bi-arrow-right"></i></div>
                            @endif
                        @endforeach
                    </div>
                    <template x-if="currentStatus === 'incident'">
                        <div class="alert alert-danger mt-3 mb-0 py-2">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            <strong>[DEMO] Incident</strong> — refund triggered.
                        </div>
                    </template>
                    <template x-if="currentStatus === 'delivered'">
                        <div class="alert alert-success mt-3 mb-0 py-2">
                            <i class="bi bi-bag-check me-1"></i> <strong>[DEMO] Delivered!</strong>
                        </div>
                    </template>
                </div>
            </div>
        @endif

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="mb-0">Order #{{ $order->id }}</h5>
                <small class="text-muted">
                    Placed {{ $order->created_at->format('d M Y, H:i') }}
                    &mdash;
                    <span
                        class="badge bg-{{ $order->status === 'pending' ? 'warning text-dark' : ($order->status === 'cancelled' || $order->status === 'failed' ? 'danger' : ($order->status === 'delivered' || $order->status === 'paid' ? 'success' : 'primary')) }}">
                        {{ $order->status }}
                    </span>
                </small>
            </div>
            <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> All Orders
            </a>
        </div>

        <div class="row g-3 mb-3">
            {{-- Customer --}}
            <div class="col-md-6">
                <div class="card shadow-sm border-0 rounded-3 h-100">
                    <div class="card-body">
                        <h6 class="card-title border-bottom pb-2 mb-3">Customer</h6>
                        <p class="mb-0">
                            <strong>{{ $order->user?->name ?? '—' }}</strong><br>
                            <span class="text-muted">{{ $order->user?->email ?? '—' }}</span>
                        </p>
                    </div>
                </div>
            </div>

            {{-- Shipping Address --}}
            <div class="col-md-6">
                <div class="card shadow-sm border-0 rounded-3 h-100">
                    <div class="card-body">
                        <h6 class="card-title border-bottom pb-2 mb-3">Shipping Address</h6>
                        <address class="mb-0" style="font-style:normal;line-height:1.7;font-size:.9rem;">
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
                </div>
            </div>

            {{-- Payment --}}
            <div class="col-md-6">
                <div class="card shadow-sm border-0 rounded-3 h-100">
                    <div class="card-body">
                        <h6 class="card-title border-bottom pb-2 mb-3">Payment</h6>
                        <p class="mb-0 small">
                            <strong>Method:</strong> Stripe PaymentIntent<br>
                            <strong>Status:</strong>
                            <span
                                class="badge bg-{{ $order->status === 'pending' ? 'warning text-dark' : ($order->status === 'cancelled' || $order->status === 'failed' ? 'danger' : ($order->status === 'delivered' || $order->status === 'paid' ? 'success' : 'primary')) }}">
                                {{ $order->status }}
                            </span><br>
                            @if($order->stripe_payment_intent_id)
                                <strong>Intent:</strong> <small
                                    class="text-muted">{{ $order->stripe_payment_intent_id }}</small>
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            {{-- Status History + Update Form --}}
            <div class="col-md-6">
                <div class="card shadow-sm border-0 rounded-3 h-100">
                    <div class="card-body">
                        <h6 class="card-title border-bottom pb-2 mb-3">Status History</h6>
                        <ol class="timeline mb-3">
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
                            <li
                                class="timeline-step {{ $order->shipped_at ? 'timeline-step--done' : 'timeline-step--pending' }}">
                                <strong>Shipped</strong>
                                @if($order->shipped_at)
                                    <span class="timeline-ts">{{ $order->shipped_at->format('d M Y, H:i') }}</span>
                                @endif
                            </li>
                            <li
                                class="timeline-step {{ $order->delivered_at ? 'timeline-step--done' : 'timeline-step--pending' }}">
                                <strong>Delivered</strong>
                                @if($order->delivered_at)
                                    <span class="timeline-ts">{{ $order->delivered_at->format('d M Y, H:i') }}</span>
                                @endif
                            </li>
                            @if($order->cancelled_at)
                                <li class="timeline-step timeline-step--done" style="border-left-color:#dc3545;">
                                    <strong>Cancelled</strong>
                                    <span class="timeline-ts">{{ $order->cancelled_at->format('d M Y, H:i') }}</span>
                                </li>
                            @endif
                            @if($order->refunded_at)
                                <li class="timeline-step timeline-step--done" style="border-left-color:#0c4a6e;">
                                    <strong>Refunded</strong>
                                    <span class="timeline-ts">{{ $order->refunded_at->format('d M Y, H:i') }}</span>
                                </li>
                            @endif
                        </ol>

                        {{-- Status update form --}}
                        <form method="POST" action="{{ route('admin.orders.status', $order) }}"
                            class="d-flex gap-2 align-items-center">
                            @csrf
                            @method('PATCH')
                            <select name="status" class="form-select form-select-sm" style="max-width:160px;">
                                @foreach($updatableStatuses as $s)
                                    <option value="{{ $s }}" {{ $order->status === $s ? 'selected' : '' }}>{{ ucfirst($s) }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-primary btn-sm">Update Status</button>
                        </form>

                        {{-- OM-005: Refund button --}}
                        @if($order->status === 'cancelled' && $order->stripe_payment_intent_id)
                            @error('order')
                                <div class="alert alert-danger mt-2 py-2 small d-flex align-items-center gap-2">
                                    <i class="bi bi-exclamation-triangle-fill"></i>
                                    {{ $message }}
                                </div>
                            @enderror
                            <form method="POST" action="{{ route('admin.orders.refund', $order) }}" class="mt-2" x-data
                                @submit.prevent="if(confirm('Process a refund of ${{ number_format($order->total, 2) }} for Order #{{ $order->id }}?')) $el.submit()">
                                @csrf
                                <button type="submit" class="btn btn-danger btn-sm">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i>
                                    Process Refund (${{ number_format($order->total, 2) }})
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Items --}}
        <div class="card shadow-sm border-0 rounded-3 mb-3">
            <div class="card-body">
                <h6 class="card-title border-bottom pb-2 mb-3">Items</h6>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Unit Price</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->items as $item)
                                <tr>
                                    <td>{{ $item->product_name }}</td>
                                    <td class="text-end">{{ $item->quantity }}</td>
                                    <td class="text-end">${{ number_format($item->unit_price, 2) }}</td>
                                    <td class="text-end">${{ number_format($item->subtotal, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-end mt-3">
                    <div style="min-width:240px;">
                        <div class="d-flex justify-content-between small mb-1">
                            <span>Subtotal</span><span>${{ number_format($order->subtotal, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between small mb-1"><span>Shipping
                                ({{ $order->shipping_label }})</span><span>${{ number_format($order->shipping_cost, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between fw-bold border-top pt-2">
                            <span>Total</span><span>${{ number_format($order->total, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- OM-005: Refund Transactions --}}
        @if($order->refundTransactions->isNotEmpty())
            <div class="card shadow-sm border-0 rounded-3 mb-3">
                <div class="card-body">
                    <h6 class="card-title border-bottom pb-2 mb-3">Refund Transactions</h6>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th class="text-end">Amount</th>
                                    <th>Stripe Refund ID</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->refundTransactions as $refund)
                                    <tr>
                                        <td>{{ $refund->created_at->format('d M Y, H:i') }}</td>
                                        <td class="text-end">${{ number_format($refund->amount, 2) }}</td>
                                        <td><small class="text-muted">{{ $refund->stripe_refund_id ?? '—' }}</small></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection

@push('scripts')
    <script>
        function shipSimPoller(_unused, statusUrl, initialStatus) {
            const STEP_ORDER = ['payment_confirmed', 'preparing', 'picked_up', 'in_transit', 'arrived'];
            const TERMINAL = ['delivered', 'incident'];
            return {
                currentStatus: initialStatus,
                timer: null,
                get isTerminal() { return TERMINAL.includes(this.currentStatus); },
                init() {
                    if (!this.isTerminal) {
                        this.timer = setInterval(() => this.poll(), 3000);
                    }
                },
                async poll() {
                    try {
                        const res = await fetch(statusUrl);
                        if (!res.ok) return;
                        const data = await res.json();
                        this.currentStatus = data.ship_sim_status ?? this.currentStatus;
                        if (this.isTerminal) clearInterval(this.timer);
                    } catch (_) { }
                },
                stepDone(key) {
                    const curIdx = STEP_ORDER.indexOf(this.currentStatus);
                    const keyIdx = STEP_ORDER.indexOf(key);
                    if (keyIdx === -1 || curIdx === -1) return false;
                    return keyIdx < curIdx || (this.currentStatus === 'delivered' && keyIdx <= curIdx);
                },
            };
        }
    </script>
@endpush

@push('styles')
    <style>
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
    </style>
@endpush