@extends('layouts.admin')
@section('title', 'Admin Dashboard — E-Commerce')
@section('page-title', 'Dashboard')

@push('styles')
    <meta http-equiv="refresh" content="300">
    <style>
        /* IMP-027: skeleton shimmer — chart loading indicator */
        @keyframes skel-shimmer {
            0% {
                background-position: -600px 0;
            }

            100% {
                background-position: 600px 0;
            }
        }

        .skel-chart-wrap {
            position: relative;
        }

        #chart-skeleton {
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, #e2e5e7 25%, #f0f2f4 50%, #e2e5e7 75%);
            background-size: 600px 100%;
            animation: skel-shimmer 1.4s ease infinite;
            border-radius: 6px;
            z-index: 2;
            transition: opacity 0.3s ease;
        }

        #chart-skeleton.hidden {
            opacity: 0;
            pointer-events: none;
        }

        .range-btn.active {
            background: #0d6efd !important;
            color: #fff !important;
            border-color: #0d6efd !important;
        }
    </style>
@endpush

@section('content')
    <div data-imp017="realtime-enabled" x-data x-init="$el.classList.add('fade-in')">

        {{-- ── KPI Cards ─────────────────────────────────────────────── --}}
        <div class="row g-3 mb-4">

            <div class="col-sm-6 col-xl-3">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body d-flex align-items-center gap-3 p-4">
                        <div class="rounded-circle bg-success bg-opacity-10 d-flex align-items-center
                                    justify-content-center flex-shrink-0" style="width:48px;height:48px;">
                            <i class="bi bi-graph-up text-success fs-5"></i>
                        </div>
                        <div>
                            <div class="fs-4 fw-bold lh-1 mb-1" id="kpi-total-revenue">
                                ${{ number_format($totalRevenue, 2) }}
                            </div>
                            <div class="text-label">Total Revenue</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body d-flex align-items-center gap-3 p-4">
                        <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center
                                    justify-content-center flex-shrink-0" style="width:48px;height:48px;">
                            <i class="bi bi-bag-check text-primary fs-5"></i>
                        </div>
                        <div>
                            <div class="fs-4 fw-bold lh-1 mb-1" id="kpi-orders-today">
                                {{ $ordersToday }}
                            </div>
                            <div class="text-label">Orders Today</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body d-flex align-items-center gap-3 p-4">
                        <div class="rounded-circle bg-info bg-opacity-10 d-flex align-items-center
                                    justify-content-center flex-shrink-0" style="width:48px;height:48px;">
                            <i class="bi bi-people text-info fs-5"></i>
                        </div>
                        <div>
                            <div class="fs-4 fw-bold lh-1 mb-1" id="kpi-new-users-today">
                                {{ $newUsersToday }}
                            </div>
                            <div class="text-label">New Users Today</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body d-flex align-items-center gap-3 p-4">
                        <div class="rounded-circle bg-warning bg-opacity-10 d-flex align-items-center
                                    justify-content-center flex-shrink-0" style="width:48px;height:48px;">
                            <i class="bi bi-exclamation-triangle text-warning fs-5"></i>
                        </div>
                        <div>
                            <div class="fs-4 fw-bold lh-1 mb-1" id="kpi-low-stock">
                                {{ $lowStockProducts }}
                            </div>
                            <div class="text-label">Low-Stock Products</div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- ── Revenue & Orders Chart ──────────────────────────────────── --}}
        <div class="card shadow-sm border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                    <h6 class="fw-semibold mb-0">Revenue &amp; Orders</h6>
                    <div class="btn-group btn-group-sm" role="group" aria-label="Chart range">
                        <button class="btn btn-outline-secondary range-btn active" data-range="daily">Daily</button>
                        <button class="btn btn-outline-secondary range-btn" data-range="weekly">Weekly</button>
                        <button class="btn btn-outline-secondary range-btn" data-range="monthly">Monthly</button>
                    </div>
                </div>
                <div class="skel-chart-wrap" style="height:300px;">
                    <div id="chart-skeleton"></div>
                    <canvas id="revenue-chart" style="width:100%;height:300px;"></canvas>
                </div>
            </div>
        </div>

        {{-- ── Bottom row: Top-Selling + Recent Orders ─────────────────── --}}
        <div class="row g-4">

            {{-- Top-Selling Products --}}
            <div class="col-lg-6">
                <div class="card shadow-sm border-0 rounded-3 h-100" id="top-selling-section">
                    <div class="card-body p-4">
                        <h6 class="fw-semibold mb-3">Top-Selling Products</h6>

                        <form method="GET" class="row g-2 align-items-end mb-3">
                            <div class="col-5">
                                <label for="top_selling_start" class="form-label small fw-semibold">From</label>
                                <input type="date" id="top_selling_start" name="top_selling_start"
                                    class="form-control form-control-sm" value="{{ $dateStart }}">
                            </div>
                            <div class="col-5">
                                <label for="top_selling_end" class="form-label small fw-semibold">To</label>
                                <input type="date" id="top_selling_end" name="top_selling_end"
                                    class="form-control form-control-sm" value="{{ $dateEnd }}">
                            </div>
                            <div class="col-2">
                                <button type="submit" class="btn btn-primary btn-sm w-100">Go</button>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle small mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-label ps-3 py-2">#</th>
                                        <th class="text-label py-2">Product</th>
                                        <th class="text-label text-end py-2">Units Sold</th>
                                        <th class="text-label text-end pe-3 py-2">Revenue ($)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($topSelling as $i => $row)
                                        <tr>
                                            <td class="ps-3 text-muted">{{ $i + 1 }}</td>
                                            <td>{{ $row->product_name }}</td>
                                            <td class="text-end">{{ $row->units_sold }}</td>
                                            <td class="text-end pe-3">${{ number_format($row->total_revenue, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="tbl-empty text-center text-muted py-4">No sales in this period.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Recent Orders --}}
            <div class="col-lg-6">
                <div class="card shadow-sm border-0 rounded-3 h-100" id="recent-orders-section">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h6 class="fw-semibold mb-0">Recent Orders</h6>
                            <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-secondary btn-sm">
                                View all <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle small mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-label ps-3 py-2">Order #</th>
                                        <th class="text-label py-2">Customer</th>
                                        <th class="text-label py-2">Status</th>
                                        <th class="text-label text-end pe-3 py-2">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($recentOrders as $order)
                                        <tr>
                                            <td class="ps-3">
                                                <a href="{{ route('admin.orders.show', $order) }}"
                                                    class="text-decoration-none fw-medium">
                                                    #{{ $order->id }}
                                                </a>
                                            </td>
                                            <td class="text-muted">{{ $order->user ? $order->user->name : 'Guest' }}</td>
                                            <td>
                                                @php
                                                    $statusClass = match ($order->status) {
                                                        'paid', 'delivered' => 'bg-success bg-opacity-10 text-success',
                                                        'pending' => 'bg-warning bg-opacity-10 text-warning',
                                                        'cancelled', 'failed' => 'bg-danger bg-opacity-10 text-danger',
                                                        default => 'bg-primary bg-opacity-10 text-primary',
                                                    };
                                                @endphp
                                                <span class="badge rounded-pill {{ $statusClass }} fw-medium">
                                                    {{ ucfirst($order->status) }}
                                                </span>
                                            </td>
                                            <td class="text-end pe-3">
                                                <a href="{{ route('admin.orders.show', $order) }}"
                                                    class="btn btn-outline-primary btn-sm">View</a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="tbl-empty text-center text-muted py-4">No recent orders.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
    <script>
        const chartDataUrl = '{{ route("admin.chart-data") }}';
        let currentRange = 'daily';
        let chart = null;

        async function loadChart(range) {
            const skeleton = document.getElementById('chart-skeleton');
            skeleton.classList.remove('hidden');

            const res = await fetch(chartDataUrl + '?range=' + range);
            const data = await res.json();

            const ctx = document.getElementById('revenue-chart').getContext('2d');
            if (chart) chart.destroy();

            chart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [
                        {
                            label: 'Revenue ($)',
                            data: data.revenue,
                            backgroundColor: 'rgba(59,130,246,0.6)',
                            yAxisID: 'y',
                        },
                        {
                            label: 'Orders',
                            data: data.orders,
                            type: 'line',
                            borderColor: 'rgba(16,185,129,1)',
                            backgroundColor: 'transparent',
                            yAxisID: 'y1',
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { position: 'left', title: { display: true, text: 'Revenue ($)' } },
                        y1: { position: 'right', title: { display: true, text: 'Orders' }, grid: { drawOnChartArea: false } },
                    },
                },
            });

            skeleton.classList.add('hidden');
        }

        document.querySelectorAll('.range-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.range-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                currentRange = btn.dataset.range;
                loadChart(currentRange);
            });
        });

        loadChart(currentRange);
    </script>

    {{-- IMP-017: Firebase real-time listener — refreshes Recent Orders on new order --}}
    @if(config('services.firebase.api_key'))
        <script src="https://www.gstatic.com/firebasejs/10.12.0/firebase-app-compat.js" defer></script>
        <script src="https://www.gstatic.com/firebasejs/10.12.0/firebase-database-compat.js" defer></script>
    @endif
    <script>
        (function () {
            var _fbApiKey = '{{ config("services.firebase.api_key", "") }}';
            var _fbDbUrl = '{{ config("services.firebase.db_url", "") }}';
            if (_fbApiKey && _fbDbUrl) {
                document.addEventListener('DOMContentLoaded', function () {
                    if (typeof firebase !== 'undefined') {
                        firebase.initializeApp({ apiKey: _fbApiKey, databaseURL: _fbDbUrl });
                        firebase.database().ref('admin/latest_notification').on('value', function (snap) {
                            if (!snap.val()) return;
                            var section = document.getElementById('recent-orders-section');
                            if (section && !document.getElementById('rtdb-refresh-hint')) {
                                var hint = document.createElement('p');
                                hint.id = 'rtdb-refresh-hint';
                                hint.style.cssText = 'color:#2563eb;font-size:0.875rem;margin:0.5rem 0 0;';
                                hint.textContent = 'New orders available — reload page to see latest.';
                                section.insertBefore(hint, section.querySelector('div'));
                            }
                        });
                    }
                });
            }
        })();
    </script>
@endpush