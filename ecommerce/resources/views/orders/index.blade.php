<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders</title>
    <style>
        body {
            font-family: sans-serif;
            max-width: 900px;
            margin: 40px auto;
            padding: 0 1rem;
        }

        h1 {
            margin-bottom: 1.5rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            text-align: left;
            padding: .6rem .75rem;
            border-bottom: 1px solid #e5e7eb;
        }

        th {
            background: #f9fafb;
            font-weight: 600;
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

        .empty {
            text-align: center;
            padding: 3rem;
            color: #6b7280;
        }

        .pagination {
            margin-top: 1.5rem;
            display: flex;
            gap: .5rem;
        }

        .pagination a,
        .pagination span {
            padding: .4rem .75rem;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            text-decoration: none;
            color: #374151;
        }

        .pagination .active span {
            background: #1d4ed8;
            color: #fff;
            border-color: #1d4ed8;
        }
    </style>
</head>

<body>
    <h1>My Orders</h1>

    @if ($orders->isEmpty())
        <p class="empty">You haven't placed any orders yet. <a href="{{ route('products.index') }}">Start shopping</a></p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Date</th>
                    <th>Total</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($orders as $order)
                    <tr>
                        <td>#{{ $order->id }}</td>
                        <td>{{ $order->created_at->format('d M Y') }}</td>
                        <td>${{ number_format($order->total, 2) }}</td>
                        <td><span class="status-{{ $order->status }}">{{ ucfirst($order->status) }}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="pagination">
            {{ $orders->links() }}
        </div>
    @endif

    <p><a href="{{ route('dashboard') }}">&larr; Back to Dashboard</a></p>
</body>

</html>