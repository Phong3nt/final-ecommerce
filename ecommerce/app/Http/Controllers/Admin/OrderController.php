<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $query = Order::with('user')->latest();

        // Filter: status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter: date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Filter: customer name or email
        if ($request->filled('customer')) {
            $search = $request->customer;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sort = $request->input('sort', 'newest');
        match ($sort) {
            'oldest' => $query->reorder('created_at', 'asc'),
            'total_asc' => $query->reorder('total', 'asc'),
            'total_desc' => $query->reorder('total', 'desc'),
            default => $query->reorder('created_at', 'desc'),
        };

        $orders = $query->paginate(20)->withQueryString();
        $statuses = ['pending', 'paid', 'processing', 'shipped', 'delivered', 'cancelled', 'failed'];

        return view('admin.orders.index', compact('orders', 'statuses'));
    }
}
