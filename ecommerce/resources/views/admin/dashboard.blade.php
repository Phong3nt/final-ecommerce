<!DOCTYPE html>
<html>

<head>
    <title>Admin Dashboard</title>
    <meta http-equiv="refresh" content="300">
    <style>
        body {
            font-family: sans-serif;
            margin: 2rem;
            background: #f5f5f5;
        }

        h1 {
            margin-bottom: 0.25rem;
        }

        p.welcome {
            color: #555;
            margin-top: 0;
        }

        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            max-width: 640px;
            margin-top: 1.5rem;
        }

        .kpi-card {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 1.25rem 1.5rem;
        }

        .kpi-value {
            font-size: 2rem;
            font-weight: 700;
            color: #333;
        }

        .kpi-label {
            font-size: 0.875rem;
            color: #666;
            margin-top: 0.25rem;
        }

        .chart-section {
            margin-top: 2rem;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 1.5rem;
            max-width: 800px;
        }

        .chart-section h2 {
            margin-top: 0;
            font-size: 1.1rem;
        }

        .range-toggles {
            margin-bottom: 1rem;
        }

        .range-btn {
            padding: 0.35rem 0.85rem;
            margin-right: 0.5rem;
            border: 1px solid #999;
            border-radius: 4px;
            background: #fff;
            cursor: pointer;
            font-size: 0.875rem;
        }

        .range-btn.active {
            background: #333;
            color: #fff;
            border-color: #333;
        }

        /* ── Skeleton Shimmer ────────────────────────────────── */
        @keyframes skel-shimmer {
            0% {
                background-position: -600px 0;
            }

            100% {
                background-position: 600px 0;
            }
        }

        /* KPI card value: shimmer shown while page paints, removed by JS on DOMContentLoaded */
        .kpi-card.kpi-loading .kpi-value,
        .kpi-card.kpi-loading .kpi-label {
            background: linear-gradient(90deg, #e2e5e7 25%, #f0f2f4 50%, #e2e5e7 75%);
            background-size: 600px 100%;
            animation: skel-shimmer 1.4s ease infinite;
            color: transparent;
            border-radius: 4px;
            user-select: none;
        }

        /* Chart skeleton overlay */
        .skel-chart-wrap {
            position: relative;
            line-height: 0;
        }

        #chart-skeleton {
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, #e2e5e7 25%, #f0f2f4 50%, #e2e5e7 75%);
            background-size: 600px 100%;
            animation: skel-shimmer 1.4s ease infinite;
            border-radius: 4px;
            z-index: 2;
            transition: opacity 0.3s ease;
        }

        #chart-skeleton.hidden {
            opacity: 0;
            pointer-events: none;
        }

        .tbl-empty {
            padding: 0.5rem 1rem;
            text-align: center;
            color: #888;
        }
    </style>
</head>

<body>
    <h1>Admin Dashboard</h1>
    <p class="welcome">Welcome, {{ auth()->user()->name }}</p>

    <div class="kpi-grid">
        <div class="kpi-card kpi-loading">
            <div class="kpi-value" id="kpi-total-revenue">${{ number_format($totalRevenue, 2) }}</div>
            <div class="kpi-label">Total Revenue</div>
        </div>
        <div class="kpi-card kpi-loading">
            <div class="kpi-value" id="kpi-orders-today">{{ $ordersToday }}</div>
            <div class="kpi-label">Orders Today</div>
        </div>
        <div class="kpi-card kpi-loading">
            <div class="kpi-value" id="kpi-new-users-today">{{ $newUsersToday }}</div>
            <div class="kpi-label">New Users Today</div>
        </div>
        <div class="kpi-card kpi-loading">
            <div class="kpi-value" id="kpi-low-stock">{{ $lowStockProducts }}</div>
            <div class="kpi-label">Low-Stock Products</div>
        </div>
    </div>

    <div class="chart-section">
        <h2>Revenue &amp; Orders</h2>
        <div class="range-toggles">
            <button class="range-btn active" data-range="daily">Daily</button>
            <button class="range-btn" data-range="weekly">Weekly</button>
            <button class="range-btn" data-range="monthly">Monthly</button>
        </div>
        <div class="skel-chart-wrap">
            <div id="chart-skeleton"></div>
            <canvas id="revenue-chart" width="760" height="300"></canvas>
        </div>
    </div>

    <div class="chart-section" id="top-selling-section" style="margin-top:2.5rem;">
        <h2>Top-Selling Products</h2>
        <form method="GET" style="margin-bottom:1rem;display:flex;gap:1rem;align-items:end;">
            <div>
                <label for="top_selling_start">From:</label>
                <input type="date" id="top_selling_start" name="top_selling_start" value="{{ $dateStart }}">
            </div>
            <div>
                <label for="top_selling_end">To:</label>
                <input type="date" id="top_selling_end" name="top_selling_end" value="{{ $dateEnd }}">
            </div>
            <button type="submit" style="padding:0.5rem 1.2rem;">Filter</button>
        </form>
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr style="background:#f3f4f6;">
                        <th style="padding:0.5rem 1rem;text-align:left;">#</th>
                        <th style="padding:0.5rem 1rem;text-align:left;">Product</th>
                        <th style="padding:0.5rem 1rem;text-align:right;">Units Sold</th>
                        <th style="padding:0.5rem 1rem;text-align:right;">Revenue ($)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topSelling as $i => $row)
                        <tr style="border-bottom:1px solid #eee;">
                            <td style="padding:0.5rem 1rem;">{{ $i + 1 }}</td>
                            <td style="padding:0.5rem 1rem;">{{ $row->product_name }}</td>
                            <td style="padding:0.5rem 1rem;text-align:right;">{{ $row->units_sold }}</td>
                            <td style="padding:0.5rem 1rem;text-align:right;">${{ number_format($row->total_revenue, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="tbl-empty">No sales in this period.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="chart-section" id="recent-orders-section" style="margin-top:2.5rem;">
        <h2>Recent Orders</h2>
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr style="background:#f3f4f6;">
                        <th style="padding:0.5rem 1rem;text-align:left;">Order #</th>
                        <th style="padding:0.5rem 1rem;text-align:left;">Customer</th>
                        <th style="padding:0.5rem 1rem;text-align:left;">Date</th>
                        <th style="padding:0.5rem 1rem;text-align:left;">Status</th>
                        <th style="padding:0.5rem 1rem;text-align:right;">Total ($)</th>
                        <th style="padding:0.5rem 1rem;text-align:center;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentOrders as $order)
                        <tr style="border-bottom:1px solid #eee;">
                            <td style="padding:0.5rem 1rem;">{{ $order->id }}</td>
                            <td style="padding:0.5rem 1rem;">{{ $order->user ? $order->user->name : 'Guest' }}</td>
                            <td style="padding:0.5rem 1rem;">{{ $order->created_at->format('Y-m-d H:i') }}</td>
                            <td style="padding:0.5rem 1rem;">
                                <span
                                    style="display:inline-block;padding:0.2em 0.7em;border-radius:12px;font-size:0.95em;background:#eef;">{{ ucfirst($order->status) }}</span>
                            </td>
                            <td style="padding:0.5rem 1rem;text-align:right;">${{ number_format($order->total, 2) }}</td>
                            <td style="padding:0.5rem 1rem;text-align:center;">
                                <a href="{{ route('admin.orders.show', $order) }}"
                                    style="color:#2563eb;text-decoration:underline;">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="tbl-empty">No recent orders.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
    <script>
        const chartDataUrl = '{{ route("admin.chart-data") }}';
        let currentRange = 'daily';
        let chart = null;

        // Remove KPI loading skeleton once DOM is fully painted
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.kpi-card.kpi-loading').forEach(function (card) {
                card.classList.remove('kpi-loading');
            });
        });

        async function loadChart(range) {
            const skeleton = document.getElementById('chart-skeleton');

            // Show skeleton while fetching
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
                    responsive: false,
                    scales: {
                        y: { position: 'left', title: { display: true, text: 'Revenue ($)' } },
                        y1: { position: 'right', title: { display: true, text: 'Orders' }, grid: { drawOnChartArea: false } },
                    },
                },
            });

            // Hide skeleton after chart renders
            skeleton.classList.add('hidden');
        }

        document.querySelectorAll('.range-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.range-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                loadChart(btn.dataset.range);
            });
        });

        loadChart(currentRange);
    </script>
</body>

</html>