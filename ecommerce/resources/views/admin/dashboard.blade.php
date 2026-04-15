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
    </style>
</head>

<body>
    <h1>Admin Dashboard</h1>
    <p class="welcome">Welcome, {{ auth()->user()->name }}</p>

    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-value" id="kpi-total-revenue">${{ number_format($totalRevenue, 2) }}</div>
            <div class="kpi-label">Total Revenue</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-value" id="kpi-orders-today">{{ $ordersToday }}</div>
            <div class="kpi-label">Orders Today</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-value" id="kpi-new-users-today">{{ $newUsersToday }}</div>
            <div class="kpi-label">New Users Today</div>
        </div>
        <div class="kpi-card">
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
        <canvas id="revenue-chart" width="760" height="300"></canvas>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
    <script>
        const chartDataUrl = '{{ route("admin.chart-data") }}';
        let currentRange = 'daily';
        let chart = null;

        async function loadChart(range) {
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