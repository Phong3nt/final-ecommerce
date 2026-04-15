<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminController extends Controller
{
    private const LOW_STOCK_THRESHOLD = 5;
    private const REVENUE_STATUSES = ['paid', 'processing', 'shipped', 'delivered'];

    public function dashboard(): View
    {
        $totalRevenue = Order::whereIn('status', self::REVENUE_STATUSES)->sum('total');
        $ordersToday = Order::whereDate('created_at', today())->count();
        $newUsersToday = User::whereDate('created_at', today())->count();
        $lowStockProducts = Product::where('stock', '<=', self::LOW_STOCK_THRESHOLD)->count();

        return view('admin.dashboard', compact(
            'totalRevenue',
            'ordersToday',
            'newUsersToday',
            'lowStockProducts'
        ));
    }

    public function chartData(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'range' => ['required', Rule::in(['daily', 'weekly', 'monthly'])],
        ]);

        [$periods, $keyFmt, $labelFmt] = match ($validated['range']) {
            'daily' => [
                collect(range(0, 6))->map(fn($i) => now()->subDays(6 - $i)->startOfDay()),
                'Y-m-d',
                'M d',
            ],
            'weekly' => [
                collect(range(0, 7))->map(fn($i) => now()->subWeeks(7 - $i)->startOfWeek()),
                'o-W',
                'M d',
            ],
            'monthly' => [
                collect(range(0, 11))->map(fn($i) => now()->subMonths(11 - $i)->startOfMonth()),
                'Y-m',
                'M Y',
            ],
        };

        $start = $periods->first();
        $revenueOrders = Order::whereIn('status', self::REVENUE_STATUSES)
            ->where('created_at', '>=', $start)->get(['total', 'created_at']);
        $allOrders = Order::where('created_at', '>=', $start)->get(['created_at']);

        $revenueMap = [];
        foreach ($revenueOrders as $o) {
            $key = Carbon::parse($o->created_at)->format($keyFmt);
            $revenueMap[$key] = ($revenueMap[$key] ?? 0.0) + $o->total;
        }

        $orderMap = [];
        foreach ($allOrders as $o) {
            $key = Carbon::parse($o->created_at)->format($keyFmt);
            $orderMap[$key] = ($orderMap[$key] ?? 0) + 1;
        }

        return response()->json([
            'labels' => $periods->map(fn($p) => $p->format($labelFmt))->values()->all(),
            'revenue' => $periods->map(fn($p) => round($revenueMap[$p->format($keyFmt)] ?? 0, 2))->values()->all(),
            'orders' => $periods->map(fn($p) => $orderMap[$p->format($keyFmt)] ?? 0)->values()->all(),
        ]);
    }
}
