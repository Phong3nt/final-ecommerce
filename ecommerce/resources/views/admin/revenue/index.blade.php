@extends('layouts.admin')

@section('title', 'Admin — Revenue Report')
@section('page-title', 'Revenue Report')

@section('content')
<div x-data x-init="$el.classList.add('fade-in')">
    {{-- Breadcrumb --}}
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Revenue Report</li>
        </ol>
    </nav>

    {{-- Sub-nav --}}
    <div class="mb-3">
        <a href="{{ route('admin.revenue.index') }}" class="btn btn-primary btn-sm me-1">Revenue Overview</a>
        <a href="{{ route('admin.revenue.products') }}" class="btn btn-outline-secondary btn-sm">Product Revenue</a>
    </div>

    {{-- Filter --}}
    <div class="card shadow-sm border-0 rounded-3 mb-3">
        <div class="card-body">
            <h6 class="card-title mb-3">Filters</h6>
            <form method="GET" action="{{ route('admin.revenue.index') }}">
                <div class="row g-2 align-items-end">
                    <div class="col-auto">
                        <label for="period" class="form-label fw-semibold small mb-1">Period</label>
                        <select id="period" name="period" class="form-select form-select-sm" onchange="toggleCustomDates(this.value)">
                            <option value="daily"   {{ $period === 'daily'   ? 'selected' : '' }}>Daily (last 7 days)</option>
                            <option value="weekly"  {{ $period === 'weekly'  ? 'selected' : '' }}>Weekly (last 8 weeks)</option>
                            <option value="monthly" {{ $period === 'monthly' ? 'selected' : '' }}>Monthly (last 12 months)</option>
                            <option value="custom"  {{ $period === 'custom'  ? 'selected' : '' }}>Custom range</option>
                        </select>
                    </div>
                    <div class="col-auto" id="custom-dates" style="{{ $period === 'custom' ? '' : 'display:none;' }}">
                        <label for="date_from" class="form-label fw-semibold small mb-1">From</label>
                        <input type="date" id="date_from" name="date_from" class="form-control form-control-sm"
                            value="{{ $dateFrom ? $dateFrom->format('Y-m-d') : '' }}">
                    </div>
                    <div class="col-auto" id="custom-dates-to" style="{{ $period === 'custom' ? '' : 'display:none;' }}">
                        <label for="date_to" class="form-label fw-semibold small mb-1">To</label>
                        <input type="date" id="date_to" name="date_to" class="form-control form-control-sm"
                            value="{{ $dateTo ? $dateTo->format('Y-m-d') : '' }}">
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary btn-sm">Apply</button>
                        <a href="{{ route('admin.revenue.index') }}" class="btn btn-outline-secondary btn-sm ms-1">Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Summary cards --}}
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body">
                    <div class="small fw-semibold text-muted text-uppercase mb-1">Gross Revenue</div>
                    <div class="fs-4 fw-bold">${{ number_format($totals['gross'], 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body">
                    <div class="small fw-semibold text-muted text-uppercase mb-1">Refunds</div>
                    <div class="fs-4 fw-bold text-danger">&minus;${{ number_format($totals['refunds'], 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body">
                    <div class="small fw-semibold text-muted text-uppercase mb-1">Net Revenue</div>
                    <div class="fs-4 fw-bold text-success">${{ number_format($totals['net'], 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Breakdown table --}}
    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-body">
            <h6 class="card-title mb-3">Breakdown</h6>
            @if(count($rows) === 0)
                <p class="text-muted text-center py-3">No data for the selected period.</p>
            @else
                <div class="table-responsive">
                    <table class="table align-middle mb-0" id="revenue-table">
                        <thead class="table-light">
                            <tr>
                                <th>Period</th>
                                <th class="text-end">Gross Revenue</th>
                                <th class="text-end">Refunds</th>
                                <th class="text-end">Net Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rows as $row)
                                <tr>
                                    <td>{{ $row['label'] }}</td>
                                    <td class="text-end">${{ number_format($row['gross'], 2) }}</td>
                                    <td class="text-end">&minus;${{ number_format($row['refunds'], 2) }}</td>
                                    <td class="text-end">${{ number_format($row['net'], 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold table-light border-top">
                                <td>Total</td>
                                <td class="text-end">${{ number_format($totals['gross'], 2) }}</td>
                                <td class="text-end">&minus;${{ number_format($totals['refunds'], 2) }}</td>
                                <td class="text-end">${{ number_format($totals['net'], 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function toggleCustomDates(value) {
        const show = value === 'custom';
        document.getElementById('custom-dates').style.display    = show ? '' : 'none';
        document.getElementById('custom-dates-to').style.display = show ? '' : 'none';
    }
</script>
@endpush