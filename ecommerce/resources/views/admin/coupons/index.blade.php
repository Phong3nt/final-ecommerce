<!DOCTYPE html>
<html>

<head>
    <title>Admin — Coupons</title>
    <style>
        body {
            font-family: sans-serif;
            margin: 2rem;
            background: #f5f5f5;
        }

        h1 {
            margin-bottom: 1rem;
        }

        a.btn {
            display: inline-block;
            padding: .5rem 1rem;
            background: #0d6efd;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            margin-bottom: 1rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
        }

        th,
        td {
            text-align: left;
            padding: .6rem 1rem;
            border-bottom: 1px solid #ddd;
        }

        th {
            background: #343a40;
            color: #fff;
        }

        .badge {
            display: inline-block;
            padding: .2rem .55rem;
            border-radius: 12px;
            font-size: .78rem;
            font-weight: 600;
        }

        .badge-active {
            background: #d1e7dd;
            color: #0f5132;
        }

        .badge-inactive {
            background: #f8d7da;
            color: #842029;
        }

        .btn-link {
            background: none;
            border: none;
            color: #0d6efd;
            cursor: pointer;
            font-size: inherit;
            padding: 0;
            text-decoration: underline;
        }

        .btn-delete {
            background: none;
            border: none;
            color: #dc3545;
            cursor: pointer;
            font-size: inherit;
            padding: 0;
            margin-left: .75rem;
            text-decoration: underline;
        }

        .alert-success {
            background: #d1e7dd;
            border: 1px solid #badbcc;
            color: #0f5132;
            padding: .75rem 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
    </style>
</head>

<body>
    @include('partials.toast')
    <h1>Coupons</h1>

    <a href="{{ route('admin.coupons.create') }}" class="btn">+ New Coupon</a>

    <table>
        <thead>
            <tr>
                <th>Code</th>
                <th>Type</th>
                <th>Value</th>
                <th>Min Order</th>
                <th>Usage Limit</th>
                <th>Times Used</th>
                <th>Expires</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($coupons as $coupon)
                <tr>
                    <td><strong>{{ $coupon->code }}</strong></td>
                    <td>{{ $coupon->type === 'percent' ? '%' : 'Fixed' }}</td>
                    <td>{{ $coupon->type === 'percent' ? $coupon->value . '%' : '$' . number_format($coupon->value, 2) }}
                    </td>
                    <td>{{ $coupon->min_order_amount !== null ? '$' . number_format($coupon->min_order_amount, 2) : '—' }}
                    </td>
                    <td>{{ $coupon->usage_limit ?? '∞' }}</td>
                    <td>{{ $coupon->times_used }}</td>
                    <td>{{ $coupon->expires_at ? $coupon->expires_at->format('Y-m-d') : '—' }}</td>
                    <td>
                        <span class="badge {{ $coupon->is_active ? 'badge-active' : 'badge-inactive' }}">
                            {{ $coupon->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td>
                        <a href="{{ route('admin.coupons.edit', $coupon) }}">Edit</a>

                        <form method="POST" action="{{ route('admin.coupons.toggle', $coupon) }}" style="display:inline">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn-link" style="margin-left:.75rem;">
                                {{ $coupon->is_active ? 'Deactivate' : 'Activate' }}
                            </button>
                        </form>

                        <form method="POST" action="{{ route('admin.coupons.destroy', $coupon) }}" style="display:inline"
                            data-confirm="Delete coupon &quot;{{ $coupon->code }}&quot;? This cannot be undone.">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-delete">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9">No coupons yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top:1rem;">{{ $coupons->links() }}</div>

    <script>
        document.querySelectorAll('form[data-confirm]').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                if (!confirm(form.dataset.confirm)) { e.preventDefault(); }
            });
        });
    </script>
</body>

</html>