<!DOCTYPE html>
<html>

<head>
    <title>Admin — Orders</title>
    <style>
        body {
            font-family: sans-serif;
            margin: 2rem;
            background: #f5f5f5;
        }

        h1 {
            margin-bottom: 1rem;
        }

        .filter-form {
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            display: flex;
            flex-wrap: wrap;
            gap: .75rem;
            align-items: flex-end;
        }

        .filter-form .fg {
            display: flex;
            flex-direction: column;
            gap: .2rem;
        }

        .filter-form label {
            font-size: .8rem;
            font-weight: 600;
            color: #495057;
        }

        .filter-form input,
        .filter-form select {
            padding: .35rem .6rem;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: .9rem;
        }

        .btn {
            display: inline-block;
            padding: .4rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: .9rem;
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

        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .07);
        }

        th,
        td {
            padding: .65rem .9rem;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
            font-size: .9rem;
        }

        th {
            background: #f8f9fa;
            font-weight: 700;
            white-space: nowrap;
        }

        th a {
            color: inherit;
            text-decoration: none;
        }

        th a:hover {
            text-decoration: underline;
        }

        tr:last-child td {
            border-bottom: none;
        }

        .badge {
            display: inline-block;
            padding: .2rem .55rem;
            border-radius: 20px;
            font-size: .78rem;
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

        .pagination {
            margin-top: 1rem;
            display: flex;
            gap: .3rem;
        }

        .pagination a,
        .pagination span {
            padding: .3rem .7rem;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            font-size: .85rem;
            text-decoration: none;
            color: #0d6efd;
        }

        .pagination span.active {
            background: #0d6efd;
            color: #fff;
            border-color: #0d6efd;
        }

        .alert-success {
            background: #d1e7dd;
            border: 1px solid #badbcc;
            color: #0f5132;
            padding: .75rem 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }

        .empty {
            text-align: center;
            padding: 2rem;
            color: #6c757d;
        }
    </style>
</head>

<body>
    <h1>Orders</h1>

    @if(session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif

    {{-- Filter Form --}}
    <form method="GET" action="{{ route('admin.orders.index') }}" class="filter-form">
        <div class="fg">
            <label for="status">Status</label>
            <select name="status" id="status">
                <option value="">All</option>
                @foreach($statuses as $s)
                    <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>
                        {{ ucfirst($s) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="fg">
            <label for="date_from">From</label>
            <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}">
        </div>

        <div class="fg">
            <label for="date_to">To</label>
            <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}">
        </div>

        <div class="fg">
            <label for="customer">Customer</label>
            <input type="text" name="customer" id="customer" placeholder="Name or email"
                value="{{ request('customer') }}" style="min-width:180px;">
        </div>

        <input type="hidden" name="sort" value="{{ request('sort', 'newest') }}">

        <div class="fg" style="flex-direction:row;gap:.4rem;margin-top:.2rem;">
            <button type="submit" class="btn btn-primary">Filter</button>
            @if(request()->hasAny(['status', 'date_from', 'date_to', 'customer']))
                <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary">Clear</a>
            @endif
        </div>
    </form>

    {{-- Sort helpers --}}
    @php
        $sortUrl = fn(string $col) => route('admin.orders.index', array_merge(request()->except('sort', 'page'), ['sort' => $col]));
        $currentSort = request('sort', 'newest');
        $sortIcon = fn(string $col) => $currentSort === $col ? ' ▲' : ($currentSort === $col . '_desc' ? ' ▼' : '');
    @endphp

    {{-- Table --}}
    <table>
        <thead>
            <tr>
                <th><a
                        href="{{ $sortUrl('newest') }}">ID{{ $currentSort === 'newest' ? ' ▼' : ($currentSort === 'oldest' ? ' ▲' : '') }}</a>
                </th>
                <th>Customer</th>
                <th>Status</th>
                <th><a
                        href="{{ $sortUrl($currentSort === 'total_asc' ? 'total_desc' : 'total_asc') }}">Total{{ $currentSort === 'total_asc' ? ' ▲' : ($currentSort === 'total_desc' ? ' ▼' : '') }}</a>
                </th>
                <th><a
                        href="{{ $sortUrl($currentSort === 'newest' ? 'oldest' : 'newest') }}">Date{{ $currentSort === 'newest' ? ' ▼' : ($currentSort === 'oldest' ? ' ▲' : '') }}</a>
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
                        <small style="color:#6c757d;">{{ $order->user?->email ?? '' }}</small>
                    </td>
                    <td>
                        <span class="badge badge-{{ $order->status }}">{{ $order->status }}</span>
                    </td>
                    <td>${{ number_format($order->total, 2) }}</td>
                    <td>{{ $order->created_at->format('Y-m-d') }}</td>
                    <td>
                        <a href="{{ route('orders.show', $order) }}" class="btn btn-secondary"
                            style="font-size:.8rem;padding:.25rem .65rem;">View</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="empty">No orders found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Pagination --}}
    @if($orders->hasPages())
        <div class="pagination">
            {{ $orders->links() }}
        </div>
    @endif

</body>

</html>