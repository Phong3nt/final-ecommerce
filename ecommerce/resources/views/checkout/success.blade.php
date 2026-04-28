@extends('layouts.app')

@section('title', 'Order Confirmed — #{{ $order->id }}')

@section('content')
    <div x-data x-init="$el.classList.add('fade-in')">
        <div class="row justify-content-center">
            <div class="col-lg-8">

                {{-- Success banner --}}
                <div class="text-center mb-4">
                    <div class="d-inline-flex align-items-center justify-content-center
                                bg-success bg-opacity-10 rounded-circle mb-3" style="width:72px;height:72px;">
                        <i class="bi bi-check-circle-fill fs-2 text-success"></i>
                    </div>
                    <h1 class="h4 fw-bold mb-1">Payment Successful!</h1>
                    <p class="text-muted">Thank you for your order. Your payment has been confirmed.</p>
                </div>

                {{-- Order summary card --}}
                <div class="card shadow-sm border-0 rounded-3 mb-4">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-semibold">Order <span class="text-primary">#{{ $order->id }}</span></span>
                            <span class="badge bg-success">Confirmed</span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">Product</th>
                                        <th class="text-center">Qty</th>
                                        <th class="text-end">Unit Price</th>
                                        <th class="text-end pe-4">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($order->items as $item)
                                        <tr>
                                            <td class="ps-4">{{ $item->product_name }}</td>
                                            <td class="text-center">{{ $item->quantity }}</td>
                                            <td class="text-end">${{ number_format($item->unit_price, 2) }}</td>
                                            <td class="text-end pe-4">${{ number_format($item->subtotal, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <td colspan="3" class="ps-4 text-muted">Subtotal</td>
                                        <td class="text-end pe-4">${{ number_format($order->subtotal, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="ps-4 text-muted">{{ $order->shipping_label }}</td>
                                        <td class="text-end pe-4">${{ number_format($order->shipping_cost, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="ps-4 fw-bold">Total</td>
                                        <td class="text-end pe-4 fw-bold">${{ number_format($order->total, 2) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Shipping destination --}}
                <div class="card shadow-sm border-0 rounded-3 mb-4">
                    <div class="card-body p-4 d-flex align-items-start gap-3">
                        <i class="bi bi-geo-alt-fill text-primary fs-5 mt-1"></i>
                        <div>
                            <div class="text-label mb-1">Shipping to</div>
                            <span class="fw-semibold">
                                {{ $order->address['city'] ?? '' }}, {{ $order->address['country'] ?? '' }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <a href="{{ route('orders.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-bag me-1"></i> My Orders
                    </a>
                    <a href="{{ route('products.index') }}" class="btn btn-primary">
                        <i class="bi bi-shop me-1"></i> Continue Shopping
                    </a>
                </div>

            </div>
        </div>
    </div>
@endsection