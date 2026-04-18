<!DOCTYPE html>
<html>

<head>
    <title>Admin — Revenue Report</title>
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

        td.amount {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        th.amount {
            text-align: right;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr.totals-row td {
            font-weight: 700;
            background: #f8f9fa;
            border-top: 2px solid #dee2e6;
        }

        .empty {
            text-align: center;
            padding: 2rem;
            color: #6c757d;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .summary-card {
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 1rem 1.25rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .07);
        }

        .summary-card .label {
            font-size: .78rem;
            font-weight: 600;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: .04em;
            margin-bottom: .25rem;
        }

        .summary-card .value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #212529;
        }

        .summary-card .value.net {
            color: #0f5132;
        }

        .summary-card .value.refunds {
            color: #842029;
        }
    </style>
</head>

<body>
    <div class="breadcrumb">
        <a href="{{ route('admin.dashboard') }}">Dashboard</a> &rsaquo; Revenue Report
    </div>

    <h1>Revenue Report</h1>

    {{-- Filter Form --}}
    <div class="card">
        <h2>Filters</h2>
        <form method="GET" action="{{ route('admin.revenue.index') }}">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="period">Period</label>
                    <select id="period" name="period" onchange="toggleCustomDates(this.value)">
                        <option value="daily"   {{ $period === 'daily'   ? 'selected' : '' }}>Daily (last 7 days)</option>
                        <option value="weekly"  {{ $period === 'weekly'  ? 'selected' : '' }}>Weekly (last 8 weeks)</option>
                        <option value="monthly" {{ $period === 'monthly' ? 'selected' : '' }}>Monthly (last 12 months)</option>
                        <option value="custom"  {{ $period === 'custom'  ? 'selected' : '' }}>Custom range</option>
                    </select>
                </div>

                <div class="filter-group" id="custom-dates" style="{{ $period === 'custom' ? '' : 'display:none;' }}">
                    <label for="date_from">From</label>
                    <input type="date" id="date_from" name="date_from"
                           value="{{ $dateFrom ? $dateFrom->format('Y-m-d') : '' }}">
                </div>

                <div class="filter-group" id="custom-dates-to" style="{{ $period === 'custom' ? '' : 'display:none;' }}">
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
                    <a href="{{ route('admin.revenue.index') }}" class="btn btn-secondary">Reset</a>
                </div>
            </div>
        </form>
    </div>

    {{-- Summary Totals --}}
    <div class="summary-grid">
        <div class="summary-card">
            <div class="label">Gross Revenue</div>
            <div class="value">${{ number_format($totals['gross'], 2) }}</div>
        </div>
        <div class="summary-card">
            <div class="label">Refunds</div>
            <div class="value refunds">&minus;${{ number_format($totals['refunds'], 2) }}</div>
        </div>
        <div class="summary-card">
            <div class="label">Net Revenue</div>
            <div class="value net">${{ number_format($totals['net'], 2) }}</div>
        </div>
    </div>

    {{-- Breakdown Table --}}
    <div class="card">
        <h2>Breakdown</h2>

        @if(count($rows) === 0)
            <div class="empty">No data for the selected period.</div>
        @else
            <table id="revenue-table">
                <thead>
                    <tr>
                        <th>Period</th>
                        <th class="amount">Gross Revenue</th>
                        <th class="amount">Refunds</th>
                        <th class="amount">Net Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $row)
                        <tr>
                            <td>{{ $row['label'] }}</td>
                            <td class="amount">${{ number_format($row['gross'], 2) }}</td>
                            <td class="amount">&minus;${{ number_format($row['refunds'], 2) }}</td>
                            <td class="amount">${{ number_format($row['net'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="totals-row">
                        <td>Total</td>
                        <td class="amount">${{ number_format($totals['gross'], 2) }}</td>
                        <td class="amount">&minus;${{ number_format($totals['refunds'], 2) }}</td>
                        <td class="amount">${{ number_format($totals['net'], 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        @endif
    </div>

    <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">&larr; Back to Dashboard</a>

    <script>
        function toggleCustomDates(value) {
            const show = value === 'custom';
            document.getElementById('custom-dates').style.display    = show ? '' : 'none';
            document.getElementById('custom-dates-to').style.display = show ? '' : 'none';
        }
    </script>
</body>

</html>
