@extends('layouts.admin')
{{-- @include('partials.toast') --}}

@section('title', 'Admin — Orders')
@section('page-title', 'Orders')

@section('content')
<div x-data x-init="$el.classList.add('fade-in')">
    {{-- Filter Form --}}
    <div class="card shadow-sm border-0 rounded-3 mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.orders.index') }}" class="row g-2 align-items-end">
                <div class="col-auto">
                    <label for="status" class="form-label fw-semibold small mb-1">Status</label>
                    <select name="status" id="status" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach($statuses as $s)
                            <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>
                                {{ ucfirst($s) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <label for="date_from" class="form-label fw-semibold small mb-1">From</label>
                    <input type="date" name="date_from" id="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
                </div>
                <div class="col-auto">
                    <label for="date_to" class="form-label fw-semibold small mb-1">To</label>
                    <input type="date" name="date_to" id="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
                </div>
                <div class="col-auto">
                    <label for="customer" class="form-label fw-semibold small mb-1">Customer</label>
                    <input type="text" name="customer" id="customer" class="form-control form-control-sm" placeholder="Name or email" value="{{ request('customer') }}" style="min-width:180px;">
                </div>
                <input type="hidden" name="sort" value="{{ request('sort', 'newest') }}">
                <div class="col-auto d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                    @if(request()->hasAny(['status', 'date_from', 'date_to', 'customer']))
                        <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- OM-004: Export CSV --}}
    @php
        $exportParams = array_filter(request()->only(['status', 'date_from', 'date_to', 'customer']));
    @endphp
    <div class="mb-3">
        <a href="{{ route('admin.orders.export', $exportParams) }}" class="btn btn-outline-secondary btn-sm">Export CSV</a>
    </div>

    {{-- Sort helpers --}}
    @php
        $sortUrl = fn(string $col) => route('admin.orders.index', array_merge(request()->except('sort', 'page'), ['sort' => $col]));
        $currentSort = request('sort', 'newest');
        $thClass = function (array $ascKeys, array $descKeys) use ($currentSort): string {
            if (in_array($currentSort, $ascKeys)) return 'imp013-th--sort imp013-th--asc';
            if (in_array($currentSort, $descKeys)) return 'imp013-th--sort imp013-th--desc';
            return 'imp013-th--sort';
        };
        $ariaSortVal = function (array $ascKeys, array $descKeys) use ($currentSort): string {
            if (in_array($currentSort, $ascKeys)) return 'ascending';
            if (in_array($currentSort, $descKeys)) return 'descending';
            return 'none';
        };
        $sortIconHtml = function (array $asc, array $desc) use ($currentSort): string {
            if (in_array($currentSort, $asc)) return '<span class="imp013-sort-icon" aria-hidden="true">▲</span>';
            if (in_array($currentSort, $desc)) return '<span class="imp013-sort-icon" aria-hidden="true">▼</span>';
            return '<span class="imp013-sort-icon" aria-hidden="true">↕</span>';
        };
    @endphp

    {{-- IMP-013: responsive table --}}
    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-body p-0">
            <div class="imp013-table-wrap" data-imp013="table-wrap">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="{{ $thClass(['oldest'], ['newest']) }}" data-imp013="sortable-th"
                                aria-sort="{{ $ariaSortVal(['oldest'], ['newest']) }}">
                                <a href="{{ $sortUrl($currentSort === 'oldest' ? 'newest' : 'oldest') }}" class="text-dark text-decoration-none">
                                    ID {!! $sortIconHtml(['oldest'], ['newest']) !!}
                                </a>
                            </th>
                            <th>Customer</th>
                            <th>Status</th>
                            <th class="{{ $thClass(['total_asc'], ['total_desc']) }}" data-imp013="sortable-th"
                                aria-sort="{{ $ariaSortVal(['total_asc'], ['total_desc']) }}">
                                <a href="{{ $sortUrl($currentSort === 'total_asc' ? 'total_desc' : 'total_asc') }}" class="text-dark text-decoration-none">
                                    Total {!! $sortIconHtml(['total_asc'], ['total_desc']) !!}
                                </a>
                            </th>
                            <th class="{{ $thClass(['oldest'], ['newest']) }}" data-imp013="sortable-th"
                                aria-sort="{{ $ariaSortVal(['oldest'], ['newest']) }}">
                                <a href="{{ $sortUrl($currentSort === 'newest' ? 'oldest' : 'newest') }}" class="text-dark text-decoration-none">
                                    Date {!! $sortIconHtml(['oldest'], ['newest']) !!}
                                </a>
                            </th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                            <tr>
                                <td>#{{ $order->id }}</td>
                                <td>
                                    {{ $order->user?->name ?? '—' }}<br>
                                    <small class="text-muted">{{ $order->user?->email ?? '' }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $order->status === 'pending' ? 'warning text-dark' : ($order->status === 'cancelled' || $order->status === 'failed' ? 'danger' : ($order->status === 'delivered' || $order->status === 'paid' ? 'success' : 'primary')) }}">
                                        {{ $order->status }}
                                    </span>
                                </td>
                                <td>${{ number_format($order->total, 2) }}</td>
                                <td>{{ $order->created_at->format('Y-m-d') }}</td>
                                <td>
                                    <a href="{{ route('orders.show', $order) }}" class="btn btn-outline-secondary btn-sm">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No orders found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Pagination --}}
    @if($orders->hasPages())
        <div class="mt-3">{{ $orders->links() }}</div>
    @endif
</div>
@endsection

@push('styles')
<style>
    .imp013-table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    .imp013-th--sort { white-space: nowrap; }
    .imp013-th--sort a { color: inherit; text-decoration: none; display: inline-flex; align-items: center; gap: .3rem; }
    .imp013-th--sort a:hover { text-decoration: underline; }
    .imp013-sort-icon { font-size: .7rem; color: #adb5bd; }
    .imp013-th--asc .imp013-sort-icon, .imp013-th--desc .imp013-sort-icon { color: #0d6efd; }
</style>
@endpush