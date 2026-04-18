<!DOCTYPE html>
<html>

<head>
    <title>Admin — Revenue by Product</title>
    <style>
        body {
            font-family: sans-serif;
            margin: 2rem;
            background: #f5f5f5;
        }

        h1 {
            margin-bottom: .25rem;
        }

        .breadcrumb {
            font-size: .85rem;
            color: #6c757d;
            margin-bottom: 1.5rem;
        }

        .breadcrumb a {
            color: #0d6efd;
            text-decoration: none;
        }

        .card {
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 1.25rem 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .07);
        }

        .card h2 {
            margin: 0 0 1rem 0;
            font-size: 1.1rem;
            color: #212529;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: .5rem;
        }

        .filter-row {
            display: flex;
            align-items: flex-end;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: .25rem;
        }

        .filter-group label {
            font-size: .8rem;
            font-weight: 600;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        select,
        input[type="date"] {
            padding: .35rem .65rem;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: .9rem;
            background: #fff;
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

        .btn-success {
            background: #198754;
            color: #fff;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: .6rem .85rem;
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
            color: #212529;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: .25rem;
        }

        th a:hover {
            color: #0d6efd;
        }

        td.amount,
        th.amount {
            text-align: right;
        }

        td.amount {
            font-variant-numeric: tabular-nums;
        }

        tr:last-child td {
            border-bottom: none;
        }

        .sort-arrow {
            font-size: .75rem;
            color: #0d6efd;
        }

        .empty {
            text-align: center;
            padding: 2rem;
            color: #6c757d;
        }

        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: .75rem;
        }

        .toolbar .count {
            font-size: .85rem;
            color: #6c757d;
        }
    </style>
</head>

<body>
    <div class="breadcrumb">
        <a href="{{ route('admin.dashboard') }}">Dashboard</a> &rsaquo;
        <a href="{{ route('admin.revenue.index') }}">Revenue Report</a> &rsaquo;
        Revenue by Product
    </div>

    <h1>Revenue by Product</h1>

    {{-- Filter Form --}}
    <div class="card">
        <h2>Filters</h2>
        <form method="GET" action="{{ route('admin.revenue.products') }}" id="filter-form">
            {{-- Preserve sort/direction when re-filtering --}}
            <input type="hidden" name="sort" value="{{ $sort }}">
            <input type="hidden" name="direction" value="{{ $direction }}">

            <div class="filter-row">
                <div class="filter-group">
                    <label for="category">Category</label>
                    <select id="category" name="category">
                        <option value="">All categories</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ (int) $category === $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-group">
                    <label for="date_from">From</label>
                    <input type="date" id="date_from" name="date_from"
                        value="{{ $dateFrom ? $dateFrom->format('Y-m-d') : '' }}">
                </div>

                <div class="filter-group">
                    <label for="date_to">To</label>
                    <input type="date" id="date_to" name="date_to"
                        value="{{ $dateTo ? $dateTo->format('Y-m-d') : '' }}">
                </div>

                <div class="filter-group">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-primary">Apply</button>
                </div>

                <div class="filter-group">
                    <label>&nbsp;</label>
                    <a href="{{ route('admin.revenue.products') }}" class="btn btn-secondary">Reset</a>
                </div>
            </div>
        </form>
    </div>

    {{-- Table --}}
    <div class="card">
        <div class="toolbar">
            <span class="count">{{ count($rows) }} product(s)</span>
            <a href="{{ route('admin.revenue.products.export', array_filter([
    'sort' => $sort,
    'direction' => $direction,
    'category' => $category,
    'date_from' => $dateFrom?->format('Y-m-d'),
    'date_to' => $dateTo?->format('Y-m-d'),
])) }}" class="btn btn-success" id="export-btn">Export CSV</a>
        </div>

        @php
            // Helper closures for sort URLs and arrows (closures avoid global function redeclaration)
            $sortUrl = function (string $col) use ($sort, $direction): string {
                $newDir = ($sort === $col && $direction === 'desc') ? 'asc' : 'desc';
                $params = array_filter(request()->except(['sort', 'direction']));
                return route('admin.revenue.products', array_merge($params, ['sort' => $col, 'direction' => $newDir]));
            };
            $sortArrow = function (string $col) use ($sort, $direction): string {
                if ($sort !== $col)
                    return '';
                return $direction === 'asc' ? ' ▲' : ' ▼';
            };
        @endphp

        @if(count($rows) === 0)
            <div class="empty">No revenue data for the selected filters.</div>
        @else
            <table id="product-revenue-table">
                <thead>
                    <tr>
                        <th>
                            <a href="{{ $sortUrl('product_name') }}">
                                Product<span class="sort-arrow">{{ $sortArrow('product_name') }}</span>
                            </a>
                        </th>
                        <th>
                            <a href="{{ $sortUrl('category_name') }}">
                                Category<span class="sort-arrow">{{ $sortArrow('category_name') }}</span>
                            </a>
                        </th>
                        <th class="amount">
                            <a href="{{ $sortUrl('units_sold') }}">
                                Units Sold<span class="sort-arrow">{{ $sortArrow('units_sold') }}</span>
                            </a>
                        </th>
                        <th class="amount">
                            <a href="{{ $sortUrl('gross_revenue') }}">
                                Gross Revenue<span class="sort-arrow">{{ $sortArrow('gross_revenue') }}</span>
                            </a>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $row)
                        <tr>
                            <td>{{ $row->product_name }}</td>
                            <td>{{ $row->category_name ?? '—' }}</td>
                            <td class="amount">{{ number_format($row->units_sold) }}</td>
                            <td class="amount">${{ number_format($row->gross_revenue, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="font-weight:700; background:#f8f9fa; border-top:2px solid #dee2e6;">
                        <td colspan="2">Total ({{ count($rows) }} products)</td>
                        <td class="amount">{{ number_format($rows->sum('units_sold')) }}</td>
                        <td class="amount">${{ number_format($rows->sum('gross_revenue'), 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        @endif
    </div>

    <a href="{{ route('admin.revenue.index') }}" class="btn btn-secondary">&larr; Back to Revenue Report</a>
</body>

</html>