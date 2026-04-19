<!DOCTYPE html>
<html>

<head>
    <title>Admin — Products</title>
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

        .badge-published {
            color: #198754;
            font-weight: 600;
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

        .btn-delete:hover {
            color: #a71d2a;
        }

        .badge-draft {
            color: #6c757d;
            font-weight: 600;
        }

        .alert-success {
            background: #d1e7dd;
            border: 1px solid #badbcc;
            color: #0f5132;
            padding: .75rem 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }

        .card {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 1rem 1.25rem;
            margin-bottom: 1rem;
        }

        .card h2 {
            margin: 0 0 .75rem;
            font-size: 1rem;
        }

        .alert-error {
            background: #f8d7da;
            border: 1px solid #f5c2c7;
            color: #842029;
            padding: .75rem 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }

        .import-errors {
            margin: .5rem 0 0;
            padding-left: 1rem;
            color: #842029;
        }

        .status-badge {
            display: inline-block;
            padding: .15rem .55rem;
            border-radius: 999px;
            font-size: .78rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-pending {
            background: #fff3cd;
            color: #664d03;
        }

        .status-processing {
            background: #cff4fc;
            color: #055160;
        }

        .status-completed {
            background: #d1e7dd;
            color: #0f5132;
        }

        .status-failed {
            background: #f8d7da;
            color: #842029;
        }
    </style>
</head>

<body>
    @include('partials.toast')
    <h1>Products</h1>

    <a href="{{ route('admin.products.create') }}" class="btn">+ New Product</a>

    <div class="card">
        <h2>Import Products via CSV (PM-005)</h2>
        <p style="margin:.25rem 0 .75rem;color:#555;">Expected headers:
            <strong>name,description,price,stock,status,category</strong>
        </p>

        @if($errors->has('csv_file'))
            <div class="alert-error">{{ $errors->first('csv_file') }}</div>
        @endif

        <form method="POST" action="{{ route('admin.products.import') }}" enctype="multipart/form-data">
            @csrf
            <input type="file" name="csv_file" accept=".csv,text/csv" required>
            <button type="submit"
                style="margin-left:.5rem;padding:.4rem 1rem;background:#198754;color:#fff;border:none;border-radius:4px;cursor:pointer;">Upload
                &amp; Queue Import</button>
        </form>
    </div>

    <div class="card">
        <h2>Import History</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>By</th>
                    <th>Status</th>
                    <th>Total</th>
                    <th>Success</th>
                    <th>Failed</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                @forelse($imports as $import)
                    <tr>
                        <td>{{ $import->id }}</td>
                        <td>{{ $import->user?->name ?? 'System' }}</td>
                        <td>
                            <span class="status-badge status-{{ $import->status }}">{{ $import->status }}</span>
                        </td>
                        <td>{{ $import->total_rows }}</td>
                        <td>{{ $import->success_rows }}</td>
                        <td>{{ $import->failed_rows }}</td>
                        <td>{{ $import->created_at->format('Y-m-d H:i') }}</td>
                    </tr>
                    @if(!empty($import->errors))
                        <tr>
                            <td colspan="7" style="background:#fcfcfc;">
                                <strong>Row Errors:</strong>
                                <ul class="import-errors">
                                    @foreach($import->errors as $error)
                                        <li>
                                            Row {{ $error['row'] ?? 'N/A' }}:
                                            {{ implode('; ', $error['messages'] ?? []) }}
                                        </li>
                                    @endforeach
                                </ul>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="7">No import history yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <form method="GET" action="{{ route('admin.products.index') }}" style="margin-bottom:1rem;">
        <label for="category_id" style="font-weight:600;margin-right:.5rem;">Filter by category:</label>
        <select name="category_id" id="category_id"
            style="padding:.4rem .6rem;border:1px solid #ccc;border-radius:4px;">
            <option value="">All categories</option>
            @foreach($categories as $cat)
                <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                    {{ $cat->name }}
                </option>
            @endforeach
        </select>
        <button type="submit"
            style="margin-left:.5rem;padding:.4rem 1rem;background:#6c757d;color:#fff;border:none;border-radius:4px;cursor:pointer;">Filter</button>
        @if(request('category_id'))
            <a href="{{ route('admin.products.index') }}" style="margin-left:.5rem;">Clear</a>
        @endif
    </form>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Price</th>
                <th>Stock</th>
                <th>Category</th>
                <th>Status</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($products as $product)
                <tr>
                    <td>{{ $product->id }}</td>
                    <td>{{ $product->name }}</td>
                    <td>${{ number_format($product->price, 2) }}</td>
                    <td>{{ $product->stock }}</td>
                    <td>{{ $product->category?->name ?? '—' }}</td>
                    <td><span class="badge-{{ $product->status }}">{{ ucfirst($product->status) }}</span></td>
                    <td>{{ $product->created_at->format('Y-m-d') }}</td>
                    <td>
                        <a href="{{ route('admin.products.edit', $product) }}">Edit</a>
                        <form method="POST" action="{{ route('admin.products.destroy', $product) }}" style="display:inline"
                            data-confirm="Archive &quot;{{ $product->name }}&quot;? This will hide it from the store.">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-delete">Archive</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">No products yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top:1rem;">{{ $products->links() }}</div>

    <script>
        document.querySelectorAll('form[data-confirm]').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                if (!confirm(form.dataset.confirm)) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>

</html>