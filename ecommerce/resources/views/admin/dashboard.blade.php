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
</body>

</html>