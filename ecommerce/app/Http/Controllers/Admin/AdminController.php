<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
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
}
