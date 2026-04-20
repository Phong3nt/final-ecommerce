<!DOCTYPE html>
<html>

<head>
    <title>Admin — Products</title>
    <!-- IMP-013: Alpine.js for client-side table sort -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
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

        /* IMP-013: sortable columns + responsive layout */
        .imp013-table-wrap {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .imp013-th--sort {
            cursor: pointer;
            user-select: none;
            white-space: nowrap;
        }

        .imp013-th--sort:hover {
            background: #2c3137;
        }

        .imp013-sort-icon {
            font-size: .7rem;
            opacity: .5;
            margin-left: .25rem;
        }

        .imp013-th--asc .imp013-sort-icon,
        .imp013-th--desc .imp013-sort-icon {
            opacity: 1;
            color: #86c7fb;
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

    <div class="imp013-table-wrap" data-imp013="table-wrap" x-data="imp013TableSort()">
        <table>
            <thead>
                <tr>
                    <th class="imp013-th--sort" data-imp013="sortable-th" aria-sort="none" x-on:click="sort(0, 'num')">
                        ID <span class="imp013-sort-icon" aria-hidden="true">↕</span>
                    </th>
                    <th class="imp013-th--sort" data-imp013="sortable-th" aria-sort="none" x-on:click="sort(1, 'str')">
                        Name <span class="imp013-sort-icon" aria-hidden="true">↕</span>
                    </th>
                    <th class="imp013-th--sort" data-imp013="sortable-th" aria-sort="none" x-on:click="sort(2, 'num')">
                        Price <span class="imp013-sort-icon" aria-hidden="true">↕</span>
                    </th>
                    <th class="imp013-th--sort" data-imp013="sortable-th" aria-sort="none" x-on:click="sort(3, 'num')">
                        Stock <span class="imp013-sort-icon" aria-hidden="true">↕</span>
                    </th>
                    <th>Category</th>
                    <th class="imp013-th--sort" data-imp013="sortable-th" aria-sort="none" x-on:click="sort(5, 'str')">
                        Status <span class="imp013-sort-icon" aria-hidden="true">↕</span>
                    </th>
                    <th class="imp013-th--sort" data-imp013="sortable-th" aria-sort="none" x-on:click="sort(6, 'date')">
                        Created <span class="imp013-sort-icon" aria-hidden="true">↕</span>
                    </th>
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
                            <form method="POST" action="{{ route('admin.products.destroy', $product) }}"
                                style="display:inline"
                                data-confirm="Archive &quot;{{ $product->name }}&quot;? This will hide it from the store.">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-delete">Archive</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">No products yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>{{-- /imp013-table-wrap --}}

    </div>{{-- /imp013-table-wrap --}}

    <div style="margin-top:1rem;">{{ $products->links() }}</div>

    <script>
        /* IMP-013: client-side table sort */
        function imp013TableSort() {
            return {
                col: null,
                dir: 'asc',
                sort(colIndex, type) {
                    if (this.col === colIndex) {
                        this.dir = this.dir === 'asc' ? 'desc' : 'asc';
                    } else {
                        this.col = colIndex;
                        this.dir = 'asc';
                    }
                    const dir = this.dir;
                    const tbody = this.$el.querySelector('tbody');
                    if (!tbody) return;
                    const rows = [...tbody.querySelectorAll('tr')];
                    rows.sort((a, b) => {
                        const aText = a.cells[colIndex] ? a.cells[colIndex].innerText.trim() : '';
                        const bText = b.cells[colIndex] ? b.cells[colIndex].innerText.trim() : '';
                        let cmp;
                        if (type === 'num') {
                            cmp = (parseFloat(aText.replace(/[^0-9.-]/g, '')) || 0) -
                                (parseFloat(bText.replace(/[^0-9.-]/g, '')) || 0);
                        } else if (type === 'date') {
                            cmp = new Date(aText) - new Date(bText);
                        } else {
                            cmp = aText.localeCompare(bText);
                        }
                        return dir === 'asc' ? cmp : -cmp;
                    });
                    rows.forEach(r => tbody.appendChild(r));
                    const ths = this.$el.querySelectorAll('[data-imp013="sortable-th"]');
                    ths.forEach(th => {
                        const idx = parseInt(th.getAttribute('data-col-index') || '-1');
                        th.setAttribute('aria-sort',
                            idx === colIndex ? (dir === 'asc' ? 'ascending' : 'descending') : 'none');
                        const icon = th.querySelector('.imp013-sort-icon');
                        if (icon) {
                            if (idx === colIndex) {
                                th.classList.add(dir === 'asc' ? 'imp013-th--asc' : 'imp013-th--desc');
                                th.classList.remove(dir === 'asc' ? 'imp013-th--desc' : 'imp013-th--asc');
                                icon.textContent = dir === 'asc' ? '▲' : '▼';
                            } else {
                                th.classList.remove('imp013-th--asc', 'imp013-th--desc');
                                icon.textContent = '↕';
                            }
                        }
                    });
                }
            };
        }

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