@extends('layouts.admin')
{{-- @include('partials.toast') --}}

@section('title', 'Admin — Products')
@section('page-title', 'Products')

@section('content')
<div x-data x-init="$el.classList.add('fade-in')">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">
            Products
            @if($showArchived)
                <span class="badge bg-danger ms-2" style="font-size:.7rem">Archived view</span>
            @endif
        </h4>
        <div class="d-flex gap-2 flex-wrap">
            @if($showArchived)
                <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Back to Active
                </a>
            @else
                <a href="{{ route('admin.products.index', ['show_archived' => 1]) }}" class="btn btn-outline-warning btn-sm">
                    <i class="bi bi-archive me-1"></i> Show Archived
                </a>
            @endif
            <a href="{{ route('admin.products.export', array_filter(['show_archived' => $showArchived ? 1 : null])) }}"
               class="btn btn-outline-success btn-sm" id="export-btn">
                <i class="bi bi-download me-1"></i> Export CSV
            </a>
            @unless($showArchived)
            <button type="button" class="btn btn-outline-primary btn-sm"
                    data-bs-toggle="modal" data-bs-target="#icecatImportModal">
                <i class="bi bi-cloud-download me-1"></i> Import from Icecat
            </button>
            <button type="button" class="btn btn-outline-info btn-sm"
                    data-bs-toggle="modal" data-bs-target="#icecatSyncModal">
                <i class="bi bi-arrow-repeat me-1"></i> Sync from Icecat
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm"
                    data-bs-toggle="modal" data-bs-target="#icecatImportByIdModal">
                <i class="bi bi-hash me-1"></i> Import by ID
            </button>
            <a href="{{ route('admin.products.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i> New Product
            </a>
            @endunless
        </div>
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

    {{-- IMP-040 + IMP-039 + IMP-013: Unified Alpine wrapper (AJAX category filter + bulk actions + table sort) --}}
    <div x-data="productListAdmin(
            {{ $products->total() }},
            {{ request('category_id') ? 'true' : 'false' }},
            {{ json_encode($products->pluck('id')->map(fn($id) => (string)$id)->toArray()) }},
            {{ request('category_id') ?? 'null' }},
            {{ request('brand_id') ?? 'null' }},
            {{ json_encode(request('search', '')) }},
            {{ $showArchived ? 'true' : 'false' }}
        )"
        x-init="bindPaginationLinks()">

        {{-- IMP-040 + IMP-047: Category / Brand / Search filters (AJAX — no page reload) --}}
        <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
            <label for="category_id" class="fw-semibold small mb-0">Category:</label>
            <select id="category_id" class="form-select form-select-sm" style="max-width:180px;"
                    :disabled="loading"
                    @change="filterByCategory($event.target.value)">
                <option value="">All categories</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                        {{ $cat->name }}
                    </option>
                @endforeach
            </select>

            <label for="brand_id" class="fw-semibold small mb-0">Brand:</label>
            <select id="brand_id" class="form-select form-select-sm" style="max-width:160px;"
                    :disabled="loading"
                    @change="filterByBrand($event.target.value)">
                <option value="">All brands</option>
                @foreach($brands as $b)
                    <option value="{{ $b->id }}" {{ request('brand_id') == $b->id ? 'selected' : '' }}>
                        {{ $b->name }}
                    </option>
                @endforeach
            </select>

            <div class="input-group input-group-sm" style="max-width:220px;">
                <input type="text" id="product_search" class="form-control" placeholder="Search name / SKU…"
                       x-model="currentSearch"
                       :disabled="loading"
                       @keydown.enter.prevent="applySearch()">
                <button class="btn btn-outline-secondary" type="button" @click="applySearch()" :disabled="loading">
                    <i class="bi bi-search"></i>
                </button>
            </div>

            <span x-show="loading" x-cloak
                  class="spinner-border spinner-border-sm text-secondary" role="status"
                  aria-label="Loading products…"></span>
            <button type="button" class="btn btn-link btn-sm p-0"
                    x-show="currentCategoryId || currentBrandId || currentSearch" x-cloak
                    @click="clearFilters()">Clear all</button>
        </div>

        {{-- Hidden bulk-submit form (populated dynamically by Alpine) --}}
        <form method="POST" action="{{ route('admin.products.bulkStatus') }}" x-ref="bulkForm">
            @csrf
            <input type="hidden" name="bulk_action" x-ref="bulkActionInput" value="">
            <input type="hidden" name="select_all_in_category" x-ref="bulkAllInCat" value="0">
            <input type="hidden" name="bulk_category_id" :value="currentCategoryId || ''">
            <template x-for="id in (allInCategory ? [] : selected)" :key="id">
                <input type="hidden" name="product_ids[]" :value="id">
            </template>
        </form>

        {{-- Bulk action bar (visible when ≥1 item selected) --}}
        <div x-show="hasSelection" x-cloak
             class="alert alert-primary d-flex align-items-center gap-3 flex-wrap py-2 mb-3">
            <span class="fw-semibold small">
                <span x-text="selectionCount"></span> product(s) selected
                <template x-if="showSelectAllBanner">
                    <span> &mdash;
                        <button type="button" class="btn btn-link btn-sm p-0"
                                @click="selectAllInCategory()">
                            Select all <span x-text="totalInFilter"></span> in this category
                        </button>
                    </span>
                </template>
            </span>
            <div class="d-flex gap-2 ms-auto">
                <button type="button" class="btn btn-success btn-sm"
                        @click="submitBulk('published')">
                    <i class="bi bi-check-circle me-1"></i>Publish
                </button>
                <button type="button" class="btn btn-secondary btn-sm"
                        @click="submitBulk('draft')">
                    <i class="bi bi-file-earmark me-1"></i>Set Draft
                </button>
                <button type="button" class="btn btn-danger btn-sm"
                        @click="if(confirm('Archive selected products?')) submitBulk('delete')">
                    <i class="bi bi-archive me-1"></i>Archive
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm"
                        @click="clearSelection()">
                    Cancel
                </button>
            </div>
        </div>

        {{-- IMP-013 + IMP-039: Products table --}}
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-body p-0">
                <div class="imp013-table-wrap" data-imp013="table-wrap">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:42px">
                                    <input type="checkbox" class="form-check-input"
                                           @change="toggleAll($event.target.checked)"
                                           :checked="isAllPageChecked">
                                </th>
                                <th class="imp013-th--sort" data-imp013="sortable-th" data-col-index="1" aria-sort="none" x-on:click="sort(1, 'num')">
                                    ID <span class="imp013-sort-icon" aria-hidden="true">↕</span>
                                </th>
                                <th class="imp013-th--sort" data-imp013="sortable-th" data-col-index="2" aria-sort="none" x-on:click="sort(2, 'str')">
                                    Name <span class="imp013-sort-icon" aria-hidden="true">↕</span>
                                </th>
                                <th class="imp013-th--sort" data-imp013="sortable-th" data-col-index="3" aria-sort="none" x-on:click="sort(3, 'num')">
                                    Price <span class="imp013-sort-icon" aria-hidden="true">↕</span>
                                </th>
                                <th class="imp013-th--sort" data-imp013="sortable-th" data-col-index="4" aria-sort="none" x-on:click="sort(4, 'num')">
                                    Stock <span class="imp013-sort-icon" aria-hidden="true">↕</span>
                                </th>
                                <th>Category</th>
                                <th>Brand</th>
                                <th class="imp013-th--sort" data-imp013="sortable-th" data-col-index="7" aria-sort="none" x-on:click="sort(7, 'str')">
                                    Status <span class="imp013-sort-icon" aria-hidden="true">↕</span>
                                </th>
                                <th class="imp013-th--sort" data-imp013="sortable-th" data-col-index="8" aria-sort="none" x-on:click="sort(8, 'date')">
                                    Created <span class="imp013-sort-icon" aria-hidden="true">↕</span>
                                </th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="products-tbody">
                            @include('admin.products._rows')
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="mt-3" id="products-pagination">{{ $products->links() }}</div>
    </div>
</div>

{{-- IMP-038: Icecat Import Modal --}}
<div class="modal fade" id="icecatImportModal" tabindex="-1"
     aria-labelledby="icecatImportModalLabel" aria-hidden="true"
     x-data="icecatImportModal()">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="icecatImportModalLabel">
                    <i class="bi bi-cloud-download me-2"></i>Import from Icecat
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">
                    Fetches real product data from the Icecat catalogue API and imports
                    them as <strong>Draft</strong> products pending admin review.
                </p>

                {{-- Category multi-select --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold">Categories</label>
                    <div class="row g-2">
                        @foreach(['Laptops','Smartphones','Smartwatches','Tablets','Headphones & Headsets','Battery Chargers','Electronics'] as $cat)
                        <div class="col-6 col-md-4">
                            <div class="form-check">
                                <input class="form-check-input icecat-cat-check" type="checkbox"
                                       name="categories[]" value="{{ $cat }}"
                                       id="cat_{{ Str::slug($cat) }}" checked>
                                <label class="form-check-label" for="cat_{{ Str::slug($cat) }}">{{ $cat }}</label>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- EAN count --}}
                <div class="mb-3" style="max-width:200px">
                    <label for="icecatLimitInput" class="form-label fw-semibold">EANs per category</label>
                    <input type="number" id="icecatLimitInput" class="form-control form-control-sm"
                           x-model="limit" min="1" max="50" value="20">
                    <div class="form-text">Max 50</div>
                </div>

                {{-- Progress log --}}
                <div x-show="log.length > 0" class="mt-3">
                    <label class="form-label fw-semibold">Progress</label>
                    <div class="border rounded bg-light p-2" style="max-height:160px;overflow-y:auto;font-size:.8rem;font-family:monospace">
                        <template x-for="(line, i) in log" :key="i">
                            <div x-text="line"></div>
                        </template>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm"
                        @click="startImport"
                        :disabled="loading">
                    <span x-show="loading" class="spinner-border spinner-border-sm me-1" role="status"></span>
                    <span x-text="loading ? 'Queuing…' : 'Start Import'">Start Import</span>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- IMP-044: Icecat Sync Modal --}}
<div class="modal fade" id="icecatSyncModal" tabindex="-1"
     aria-labelledby="icecatSyncModalLabel" aria-hidden="true"
     x-data="icecatSyncModal()">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="icecatSyncModalLabel">
                    <i class="bi bi-arrow-repeat me-2"></i>Sync from Icecat
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">
                    Fetches fresh data from Icecat and updates <strong>name, description, specs,
                    and images</strong> for existing products where Icecat data is newer.<br>
                    <strong>Stock, price, and status are never changed.</strong>
                    Published products are always skipped.
                </p>

                {{-- Category multi-select --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold">Categories</label>
                    <div class="row g-2">
                        @foreach(['Laptops','Smartphones','Smartwatches','Tablets','Headphones & Headsets','Battery Chargers','Electronics'] as $cat)
                        <div class="col-6 col-md-4">
                            <div class="form-check">
                                <input class="form-check-input icecat-sync-cat-check" type="checkbox"
                                       name="categories[]" value="{{ $cat }}"
                                       id="sync_cat_{{ Str::slug($cat) }}" checked>
                                <label class="form-check-label" for="sync_cat_{{ Str::slug($cat) }}">{{ $cat }}</label>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- EAN count --}}
                <div class="mb-3" style="max-width:200px">
                    <label for="icecatSyncLimitInput" class="form-label fw-semibold">EANs per category</label>
                    <input type="number" id="icecatSyncLimitInput" class="form-control form-control-sm"
                           x-model="limit" min="1" max="50" value="20">
                    <div class="form-text">Max 50</div>
                </div>

                {{-- Progress log --}}
                <div x-show="log.length > 0" class="mt-3">
                    <label class="form-label fw-semibold">Progress</label>
                    <div class="border rounded bg-light p-2" style="max-height:160px;overflow-y:auto;font-size:.8rem;font-family:monospace">
                        <template x-for="(line, i) in log" :key="i">
                            <div x-text="line"></div>
                        </template>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-info btn-sm text-white"
                        @click="startSync"
                        :disabled="loading">
                    <span x-show="loading" class="spinner-border spinner-border-sm me-1" role="status"></span>
                    <span x-text="loading ? 'Queuing…' : 'Start Sync'">Start Sync</span>
                </button>
            </div>
        </div>
    </div>
</div>
{{-- IMP-045: Import by Icecat Product ID / EAN Modal --}}
<div class="modal fade" id="icecatImportByIdModal" tabindex="-1"
     aria-labelledby="icecatImportByIdModalLabel" aria-hidden="true"
     x-data="icecatImportByIdModal()">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="icecatImportByIdModalLabel">
                    <i class="bi bi-hash me-2"></i>Import by Icecat ID / EAN
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">
                    Enter one or more <strong>Icecat Product IDs</strong> (integers) or
                    <strong>EAN / product codes</strong> (alphanumeric), comma-separated.
                    Each is imported as a <strong>Draft</strong> product. Max&nbsp;20 per request.
                </p>

                <div class="mb-3">
                    <label for="icecatByIdInput" class="form-label fw-semibold">IDs or EANs</label>
                    <input type="text" id="icecatByIdInput" class="form-control form-control-sm"
                           x-model="ids"
                           placeholder="e.g. 42322695, 5901234123457, ABC-123">
                    <div class="form-text">Comma-separated · numeric = Icecat Product ID · alphanumeric = EAN / product code</div>
                </div>

                {{-- Error alert --}}
                <div x-show="errorMsg" class="alert alert-danger py-2 small" x-text="errorMsg"></div>

                {{-- Results table --}}
                <div x-show="results.length > 0" class="mt-3">
                    <label class="form-label fw-semibold">Results</label>
                    <table class="table table-sm table-bordered mb-0" style="font-size:.82rem">
                        <thead class="table-light">
                            <tr>
                                <th>Input</th>
                                <th>Product Name</th>
                                <th>Status</th>
                                <th>Note</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(row, i) in results" :key="i">
                                <tr>
                                    <td x-text="row.input" class="font-monospace"></td>
                                    <td x-text="row.name ?? '—'"></td>
                                    <td>
                                        <span class="badge"
                                            :class="{
                                                'bg-success': row.status === 'imported',
                                                'bg-warning text-dark': row.status === 'already_exists',
                                                'bg-danger': row.status === 'failed'
                                            }"
                                            x-text="row.status"></span>
                                    </td>
                                    <td x-text="row.error ?? ''"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-secondary btn-sm"
                        @click="startImport"
                        :disabled="loading">
                    <span x-show="loading" class="spinner-border spinner-border-sm me-1" role="status"></span>
                    <span x-text="loading ? 'Importing…' : 'Import'">Import</span>
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
    [x-cloak] { display: none !important; }
    .imp013-table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    .imp013-th--sort { cursor: pointer; user-select: none; white-space: nowrap; }
    .imp013-th--sort:hover { background: #edf0f3; }
    .imp013-sort-icon { font-size: .7rem; color: #adb5bd; margin-left: .25rem; }
    .imp013-th--asc .imp013-sort-icon, .imp013-th--desc .imp013-sort-icon { color: #0d6efd; }
</style>
@endpush

@push('scripts')
<script>
    // IMP-040 + IMP-039 + IMP-013: unified Alpine component for admin product list
    function productListAdmin(initTotal, initHasCategoryFilter, initPageIds, initCategoryId, initBrandId, initSearch, initShowArchived) {
        return {
            // ─── AJAX filter state (IMP-040 / IMP-047) ────────────
            totalInFilter:     initTotal,
            hasCategoryFilter: initHasCategoryFilter,
            pageIds:           initPageIds,
            currentCategoryId: initCategoryId,
            currentBrandId:    initBrandId,
            currentSearch:     initSearch || '',
            showArchived:      initShowArchived || false,
            loading:           false,

            // ─── selection state (IMP-039) ────────────────────────
            selected: [],             // array of string IDs checked on current page
            allInCategory: false,     // true when user chose "select all in category"

            // ─── sort state (IMP-013) ─────────────────────────────
            sortCol: null, sortDir: 'asc',

            // ─── computed ─────────────────────────────────────────
            get hasSelection()        { return this.allInCategory || this.selected.length > 0; },
            get selectionCount()      { return this.allInCategory ? this.totalInFilter : this.selected.length; },
            get isAllPageChecked()    { return this.pageIds.length > 0 && this.pageIds.every(id => this.selected.includes(id)); },
            // Show "select all N in category" banner: category active, all page checked, not already in bulk-all mode
            get showSelectAllBanner() {
                return this.hasCategoryFilter && !this.allInCategory && this.isAllPageChecked;
            },

            // ─── IMP-040 / IMP-047: AJAX filter + pagination ──────
            bindPaginationLinks() {
                const pagination = document.getElementById('products-pagination');
                if (!pagination || pagination._ajaxBound) return;
                pagination._ajaxBound = true;
                pagination.addEventListener('click', (event) => {
                    const link = event.target.closest('a');
                    if (!link) return;
                    event.preventDefault();
                    this.loadProducts(link.href, this.currentCategoryId, this.currentBrandId, this.currentSearch);
                });
            },

            loadProducts(urlOrPath, catId, brandId, search) {
                this.loading = true;
                this.selected = [];
                this.allInCategory = false;
                const base = urlOrPath && urlOrPath.startsWith('http')
                    ? new URL(urlOrPath, window.location.origin)
                    : new URL('/admin/products', window.location.origin);
                base.searchParams.set('_ajax', '1');
                if (catId) { base.searchParams.set('category_id', catId); }
                else       { base.searchParams.delete('category_id'); }
                if (brandId) { base.searchParams.set('brand_id', brandId); }
                else         { base.searchParams.delete('brand_id'); }
                if (search) { base.searchParams.set('search', search); }
                else        { base.searchParams.delete('search'); }
                if (this.showArchived) { base.searchParams.set('show_archived', '1'); }
                else                  { base.searchParams.delete('show_archived'); }
                // Update export button URL to reflect current filters
                const exportBtn = document.getElementById('export-btn');
                if (exportBtn) {
                    const exp = new URL(exportBtn.href, window.location.origin);
                    if (catId) { exp.searchParams.set('category_id', catId); } else { exp.searchParams.delete('category_id'); }
                    if (brandId) { exp.searchParams.set('brand_id', brandId); } else { exp.searchParams.delete('brand_id'); }
                    if (search) { exp.searchParams.set('search', search); } else { exp.searchParams.delete('search'); }
                    exportBtn.href = exp.toString();
                }
                fetch(base.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') || {}).content || '',
                    },
                })
                .then(r => { if (!r.ok) { this.loading = false; return null; } return r.json(); })
                .then(data => {
                    if (!data) return;
                    document.getElementById('products-tbody').innerHTML      = data.rows_html;
                    document.getElementById('products-pagination').innerHTML = data.pagination_html;
                    this.totalInFilter     = data.total;
                    this.pageIds           = data.page_ids;
                    this.currentCategoryId = data.category_id || null;
                    this.currentBrandId    = data.brand_id    || null;
                    this.currentSearch     = data.search      || '';
                    this.hasCategoryFilter = !!data.category_id;
                    this.loading           = false;
                    // Re-bind archive confirm dialogs on newly injected rows
                    document.querySelectorAll('#products-tbody form[data-confirm]').forEach(function (form) {
                        if (!form._confirmBound) {
                            form._confirmBound = true;
                            form.addEventListener('submit', function (e) {
                                if (!confirm(form.dataset.confirm)) { e.preventDefault(); }
                            });
                        }
                    });
                })
                .catch(() => { this.loading = false; });
            },

            filterByCategory(catId) {
                this.currentCategoryId = catId || null;
                this.loadProducts('/admin/products', this.currentCategoryId, this.currentBrandId, this.currentSearch);
            },

            filterByBrand(brandId) {
                this.currentBrandId = brandId || null;
                this.loadProducts('/admin/products', this.currentCategoryId, this.currentBrandId, this.currentSearch);
            },

            applySearch() {
                this.loadProducts('/admin/products', this.currentCategoryId, this.currentBrandId, this.currentSearch);
            },

            clearFilters() {
                this.currentCategoryId = null;
                this.currentBrandId    = null;
                this.currentSearch     = '';
                document.getElementById('category_id').value = '';
                document.getElementById('brand_id').value    = '';
                document.getElementById('product_search').value = '';
                this.loadProducts('/admin/products', null, null, '');
            },

            // ─── selection actions (IMP-039) ──────────────────────
            toggleRow(id) {
                this.allInCategory = false;
                const idx = this.selected.indexOf(id);
                if (idx >= 0) { this.selected.splice(idx, 1); }
                else          { this.selected.push(id); }
            },
            toggleAll(checked) {
                this.allInCategory = false;
                this.selected = checked ? [...this.pageIds] : [];
            },
            selectAllInCategory() { this.allInCategory = true; },
            clearSelection()      { this.selected = []; this.allInCategory = false; },

            // ─── bulk submit (IMP-039) ─────────────────────────────
            submitBulk(action) {
                this.$refs.bulkActionInput.value = action;
                this.$refs.bulkAllInCat.value = this.allInCategory ? '1' : '0';
                this.$refs.bulkForm.submit();
            },

            // ─── sort (IMP-013) ───────────────────────────────────
            sort(colIndex, type) {
                if (this.sortCol === colIndex) { this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc'; }
                else { this.sortCol = colIndex; this.sortDir = 'asc'; }
                const dir = this.sortDir;
                const wrap = this.$el.querySelector('[data-imp013="table-wrap"]');
                const tbody = wrap ? wrap.querySelector('tbody') : null;
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
                wrap.querySelectorAll('[data-imp013="sortable-th"]').forEach(th => {
                    const idx = parseInt(th.getAttribute('data-col-index') || '-1');
                    th.setAttribute('aria-sort', idx === colIndex ? (dir === 'asc' ? 'ascending' : 'descending') : 'none');
                    const icon = th.querySelector('.imp013-sort-icon');
                    if (icon) {
                        if (idx === colIndex) { th.classList.add(dir === 'asc' ? 'imp013-th--asc' : 'imp013-th--desc'); th.classList.remove(dir === 'asc' ? 'imp013-th--desc' : 'imp013-th--asc'); icon.textContent = dir === 'asc' ? '▲' : '▼'; }
                        else { th.classList.remove('imp013-th--asc', 'imp013-th--desc'); icon.textContent = '↕'; }
                    }
                });
            },
        };
    }

    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!confirm(form.dataset.confirm)) { e.preventDefault(); }
        });
    });

    // IMP-038: Icecat Import Modal Alpine component
    function icecatImportModal() {
        return {
            limit: 20,
            loading: false,
            log: [],
            startImport() {
                const checked = [...document.querySelectorAll('.icecat-cat-check:checked')].map(c => c.value);
                if (checked.length === 0) {
                    alert('Please select at least one category.');
                    return;
                }
                this.loading = true;
                this.log = ['Sending request…'];
                fetch('{{ route('admin.icecat.import') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ categories: checked, limit: this.limit }),
                })
                .then(r => r.json())
                .then(data => {
                    this.log.push(data.message ?? 'Done.');
                    this.loading = false;
                })
                .catch(() => {
                    this.log.push('Request failed. Check server logs.');
                    this.loading = false;
                });
            },
        };
    }

    // IMP-045: Import by ID Modal Alpine component
    function icecatImportByIdModal() {
        return {
            ids: '',
            loading: false,
            results: [],
            errorMsg: '',
            startImport() {
                this.errorMsg = '';
                this.results = [];
                const trimmed = this.ids.trim();
                if (!trimmed) {
                    this.errorMsg = 'Please enter at least one ID or EAN.';
                    return;
                }
                this.loading = true;
                fetch('{{ route('admin.icecat.import-by-id') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ ids: trimmed }),
                })
                .then(r => r.json())
                .then(data => {
                    if (data.error) {
                        this.errorMsg = data.error;
                    } else {
                        this.results = data.results ?? [];
                    }
                    this.loading = false;
                })
                .catch(() => {
                    this.errorMsg = 'Request failed. Check server logs.';
                    this.loading = false;
                });
            },
        };
    }

    // IMP-044: Icecat Sync Modal Alpine component
    function icecatSyncModal() {
        return {
            limit: 20,
            loading: false,
            log: [],
            startSync() {
                const checked = [...document.querySelectorAll('.icecat-sync-cat-check:checked')].map(c => c.value);
                if (checked.length === 0) {
                    alert('Please select at least one category.');
                    return;
                }
                this.loading = true;
                this.log = ['Sending request…'];
                fetch('{{ route('admin.icecat.sync') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ categories: checked, limit: this.limit }),
                })
                .then(r => r.json())
                .then(data => {
                    this.log.push(data.message ?? 'Done.');
                    this.loading = false;
                })
                .catch(() => {
                    this.log.push('Request failed. Check server logs.');
                    this.loading = false;
                });
            },
        };
    }
</script>
@endpush