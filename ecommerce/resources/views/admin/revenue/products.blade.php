@extends('layouts.admin')

@section('title', 'Admin — Revenue by Product')
@section('page-title', 'Revenue by Product')

@section('content')
<div x-data x-init="$el.classList.add('fade-in')">
    {{-- Breadcrumb --}}
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.revenue.index') }}">Revenue Report</a></li>
            <li class="breadcrumb-item active" aria-current="page">Revenue by Product</li>
        </ol>
    </nav>

    {{-- Sub-nav --}}
    <div class="mb-3">
        <a href="{{ route('admin.revenue.index') }}" class="btn btn-outline-secondary btn-sm me-1">Revenue Overview</a>
        <a href="{{ route('admin.revenue.products') }}" class="btn btn-primary btn-sm">Product Revenue</a>
    </div>

    {{-- Filter --}}
    <div class="card shadow-sm border-0 rounded-3 mb-3">
        <div class="card-body">
            <h6 class="card-title mb-3">Filters</h6>
            <form method="GET" action="{{ route('admin.revenue.products') }}" id="filter-form">
                <input type="hidden" name="sort" value="{{ $sort }}">
                <input type="hidden" name="direction" value="{{ $direction }}">
                <div class="row g-2 align-items-end">
                    <div class="col-auto">
                        <label for="category" class="form-label fw-semibold small mb-1">Category</label>
                        <select id="category" name="category" class="form-select form-select-sm">
                            <option value="">All categories</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ (int) $category === $cat->id ? 'selected' : '' }}>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-auto">
                        <label for="date_from" class="form-label fw-semibold small mb-1">From</label>
                        <input type="date" id="date_from" name="date_from" class="form-control form-control-sm"
                            value="{{ $dateFrom ? $dateFrom->format('Y-m-d') : '' }}">
                    </div>
                    <div class="col-auto">
                        <label for="date_to" class="form-label fw-semibold small mb-1">To</label>
                        <input type="date" id="date_to" name="date_to" class="form-control form-control-sm"
                            value="{{ $dateTo ? $dateTo->format('Y-m-d') : '' }}">
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary btn-sm">Apply</button>
                        <a href="{{ route('admin.revenue.products') }}" class="btn btn-outline-secondary btn-sm ms-1">Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Table --}}
    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="small text-muted">{{ count($rows) }} product(s)</span>
                <a href="{{ route('admin.revenue.products.export', array_filter([
                    'sort' => $sort,
                    'direction' => $direction,
                    'category' => $category,
                    'date_from' => $dateFrom?->format('Y-m-d'),
                    'date_to' => $dateTo?->format('Y-m-d'),
                ])) }}" class="btn btn-success btn-sm" id="export-btn">Export CSV</a>
            </div>

            @php
                $sortUrl = function (string $col) use ($sort, $direction): string {
                    $newDir = ($sort === $col && $direction === 'desc') ? 'asc' : 'desc';
                    $params = array_filter(request()->except(['sort', 'direction']));
                    return route('admin.revenue.products', array_merge($params, ['sort' => $col, 'direction' => $newDir]));
                };
                $sortArrow = function (string $col) use ($sort, $direction): string {
                    if ($sort !== $col) return '';
                    return $direction === 'asc' ? ' ▲' : ' ▼';
                };
            @endphp

            @if(count($rows) === 0)
                <p class="text-muted text-center py-3">No revenue data for the selected filters.</p>
            @else
                <div class="table-responsive">
                    <table class="table align-middle mb-0" id="product-revenue-table">
                        <thead class="table-light">
                            <tr>
                                <th>
                                    <a href="{{ $sortUrl('product_name') }}" class="text-dark text-decoration-none">
                                        Product<span class="text-primary">{{ $sortArrow('product_name') }}</span>
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ $sortUrl('category_name') }}" class="text-dark text-decoration-none">
                                        Category<span class="text-primary">{{ $sortArrow('category_name') }}</span>
                                    </a>
                                </th>
                                <th class="text-end">
                                    <a href="{{ $sortUrl('units_sold') }}" class="text-dark text-decoration-none">
                                        Units Sold<span class="text-primary">{{ $sortArrow('units_sold') }}</span>
                                    </a>
                                </th>
                                <th class="text-end">
                                    <a href="{{ $sortUrl('gross_revenue') }}" class="text-dark text-decoration-none">
                                        Gross Revenue<span class="text-primary">{{ $sortArrow('gross_revenue') }}</span>
                                    </a>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rows as $row)
                                <tr>
                                    <td>{{ $row->product_name }}</td>
                                    <td>{{ $row->category_name ?? '—' }}</td>
                                    <td class="text-end">{{ number_format($row->units_sold) }}</td>
                                    <td class="text-end">${{ number_format($row->gross_revenue, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold table-light border-top">
                                <td colspan="2">Total ({{ count($rows) }} products)</td>
                                <td class="text-end">{{ number_format($rows->sum('units_sold')) }}</td>
                                <td class="text-end">${{ number_format($rows->sum('gross_revenue'), 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection