@extends('layouts.admin')
{{-- @include('partials.toast') --}}

@section('title', 'Admin — Products')
@section('page-title', 'Products')

@section('content')
<div x-data x-init="$el.classList.add('fade-in')">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Products</h4>
        <a href="{{ route('admin.products.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i> New Product
        </a>
    </div>

    {{-- CSV Import --}}
    <div class="card shadow-sm border-0 rounded-3 mb-3">
        <div class="card-body">
            <h6 class="card-title">Import Products via CSV</h6>
            <p class="small text-muted mb-2">Expected headers: <strong>name,description,price,stock,status,category</strong></p>
            @if($errors->has('csv_file'))
                <div class="alert alert-danger py-2 small">{{ $errors->first('csv_file') }}</div>
            @endif
            <form method="POST" action="{{ route('admin.products.import') }}" enctype="multipart/form-data" class="d-flex align-items-center gap-2">
                @csrf
                <input type="file" name="csv_file" accept=".csv,text/csv" required class="form-control form-control-sm" style="max-width:300px;">
                <button type="submit" class="btn btn-success btn-sm">Upload &amp; Queue Import</button>
            </form>
        </div>
    </div>

    {{-- Import History --}}
    <div class="card shadow-sm border-0 rounded-3 mb-3">
        <div class="card-body p-0">
            <div class="px-3 pt-3 pb-2"><h6 class="mb-0">Import History</h6></div>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th><th>By</th><th>Status</th><th>Total</th><th>Success</th><th>Failed</th><th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($imports as $import)
                            <tr>
                                <td>{{ $import->id }}</td>
                                <td>{{ $import->user?->name ?? 'System' }}</td>
                                <td><span class="badge bg-{{ $import->status === 'completed' ? 'success' : ($import->status === 'failed' ? 'danger' : 'warning text-dark') }}">{{ $import->status }}</span></td>
                                <td>{{ $import->total_rows }}</td>
                                <td>{{ $import->success_rows }}</td>
                                <td>{{ $import->failed_rows }}</td>
                                <td>{{ $import->created_at->format('Y-m-d H:i') }}</td>
                            </tr>
                            @if(!empty($import->errors))
                                <tr>
                                    <td colspan="7" class="bg-light small">
                                        <strong>Row Errors:</strong>
                                        <ul class="mb-0 ps-3 text-danger">
                                            @foreach($import->errors as $error)
                                                <li>Row {{ $error['row'] ?? 'N/A' }}: {{ implode('; ', $error['messages'] ?? []) }}</li>
                                            @endforeach
                                        </ul>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-3">No import history yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Category filter --}}
    <form method="GET" action="{{ route('admin.products.index') }}" class="d-flex align-items-center gap-2 mb-3">
        <label for="category_id" class="fw-semibold small mb-0">Filter by category:</label>
        <select name="category_id" id="category_id" class="form-select form-select-sm" style="max-width:200px;">
            <option value="">All categories</option>
            @foreach($categories as $cat)
                <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                    {{ $cat->name }}
                </option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-outline-secondary btn-sm">Filter</button>
        @if(request('category_id'))
            <a href="{{ route('admin.products.index') }}" class="btn btn-link btn-sm p-0">Clear</a>
        @endif
    </form>

    {{-- IMP-013: Products table --}}
    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-body p-0">
            <div class="imp013-table-wrap" data-imp013="table-wrap" x-data="imp013TableSort()">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
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
                                <td>
                                    <span class="badge bg-{{ $product->status === 'published' ? 'success' : 'secondary' }}">
                                        {{ ucfirst($product->status) }}
                                    </span>
                                </td>
                                <td>{{ $product->created_at->format('Y-m-d') }}</td>
                                <td>
                                    <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-outline-secondary btn-sm">Edit</a>
                                    <form method="POST" action="{{ route('admin.products.destroy', $product) }}"
                                        style="display:inline"
                                        data-confirm="Archive &quot;{{ $product->name }}&quot;? This will hide it from the store.">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm ms-1">Archive</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No products yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-3">{{ $products->links() }}</div>
</div>
@endsection

@push('styles')
<style>
    .imp013-table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    .imp013-th--sort { cursor: pointer; user-select: none; white-space: nowrap; }
    .imp013-th--sort:hover { background: #edf0f3; }
    .imp013-sort-icon { font-size: .7rem; color: #adb5bd; margin-left: .25rem; }
    .imp013-th--asc .imp013-sort-icon, .imp013-th--desc .imp013-sort-icon { color: #0d6efd; }
</style>
@endpush

@push('scripts')
<script>
    function imp013TableSort() {
        return {
            col: null, dir: 'asc',
            sort(colIndex, type) {
                if (this.col === colIndex) { this.dir = this.dir === 'asc' ? 'desc' : 'asc'; }
                else { this.col = colIndex; this.dir = 'asc'; }
                const dir = this.dir;
                const tbody = this.$el.querySelector('tbody');
                if (!tbody) return;
                const rows = [...tbody.querySelectorAll('tr')];
                rows.sort((a, b) => {
                    const aText = a.cells[colIndex] ? a.cells[colIndex].innerText.trim() : '';
                    const bText = b.cells[colIndex] ? b.cells[colIndex].innerText.trim() : '';
                    let cmp;
                    if (type === 'num') { cmp = (parseFloat(aText.replace(/[^0-9.-]/g, '')) || 0) - (parseFloat(bText.replace(/[^0-9.-]/g, '')) || 0); }
                    else if (type === 'date') { cmp = new Date(aText) - new Date(bText); }
                    else { cmp = aText.localeCompare(bText); }
                    return dir === 'asc' ? cmp : -cmp;
                });
                rows.forEach(r => tbody.appendChild(r));
                const ths = this.$el.querySelectorAll('[data-imp013="sortable-th"]');
                ths.forEach(th => {
                    const idx = parseInt(th.getAttribute('data-col-index') || '-1');
                    th.setAttribute('aria-sort', idx === colIndex ? (dir === 'asc' ? 'ascending' : 'descending') : 'none');
                    const icon = th.querySelector('.imp013-sort-icon');
                    if (icon) {
                        if (idx === colIndex) { th.classList.add(dir === 'asc' ? 'imp013-th--asc' : 'imp013-th--desc'); th.classList.remove(dir === 'asc' ? 'imp013-th--desc' : 'imp013-th--asc'); icon.textContent = dir === 'asc' ? '▲' : '▼'; }
                        else { th.classList.remove('imp013-th--asc', 'imp013-th--desc'); icon.textContent = '↕'; }
                    }
                });
            }
        };
    }
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!confirm(form.dataset.confirm)) { e.preventDefault(); }
        });
    });
</script>
@endpush