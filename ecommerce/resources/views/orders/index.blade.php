@extends('layouts.app')

@section('title', 'My Orders — E-Commerce')

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
    </style>
@endpush

@section('content')
@include('partials.toast')

        {{-- Page header --}}
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h1 class="h3 fw-bold mb-1">My Orders</h1>
                <p class="text-muted mb-0">View and track all your past purchases.</p>
            </div>
            <a href="{{ route('products.index') }}" class="btn btn-primary btn-sm px-3">
                <i class="bi bi-shop me-1"></i>Continue Shopping
            </a>
        </div>

        @if ($orders->isEmpty())
            {{-- Empty state --}}
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body text-center py-5">
                    <div class="d-inline-flex align-items-center justify-content-center
                                    bg-secondary bg-opacity-10 rounded-circle mb-3" style="width:64px;height:64px;">
                        <i class="bi bi-bag fs-2 text-secondary"></i>
                    </div>
                    <p class="text-muted mb-3">You haven't placed any orders yet.</p>
                    <a href="{{ route('products.index') }}" class="btn btn-primary px-4">
                        <i class="bi bi-shop me-1"></i>Start shopping
                    </a>
                </div>
            </div>
        @else
            {{-- Orders table --}}
            <div class="card shadow-sm border-0 rounded-3">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Order #</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($orders as $order)
                                <tr>
                                    <td class="fw-semibold">#{{ $order->id }}</td>
                                    <td class="text-muted">{{ $order->created_at->format('d M Y') }}</td>
                                    <td>${{ number_format($order->total, 2) }}</td>
                                    <td><span class="status-{{ $order->status }}">{{ ucfirst($order->status) }}</span></td>
                                    <td class="text-end">
                                        <a href="{{ route('orders.show', $order) }}" class="btn btn-outline-secondary btn-sm">
                                            View <i class="bi bi-arrow-right ms-1"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Pagination --}}
            <div class="mt-3 pagination-wrapper">
                {{ $orders->links() }}
            </div>
        @endif

        {{-- Back link --}}
        <div class="mt-4">
            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
            </a>
        </div>

    </div>
@endsection