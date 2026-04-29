<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $query = Order::with('user')->where('is_demo', false)->latest();

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

        /** @var \Illuminate\Pagination\LengthAwarePaginator $orders */
        $orders = $query->paginate(20);
        $orders->withQueryString();
        $statuses = ['pending', 'paid', 'processing', 'shipped', 'delivered', 'cancelled', 'failed'];

        return view('admin.orders.index', compact('orders', 'statuses'));
    }

    public function show(Order $order): View
    {
        $order->load('user', 'items', 'refundTransactions');

        $updatableStatuses = ['processing', 'shipped', 'delivered', 'cancelled'];

        return view('admin.orders.show', compact('order', 'updatableStatuses'));
    }

    public function export(Request $request): StreamedResponse
    {
        $query = Order::with(['user', 'items'])->where('is_demo', false)->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('customer')) {
            $search = $request->customer;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $orders = $query->get();
        $filename = 'orders-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($orders) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Order ID', 'Customer Name', 'Customer Email', 'Items', 'Total', 'Status', 'Date']);
            foreach ($orders as $order) {
                $items = $order->items
                    ->map(fn($item) => $item->product_name . ' x' . $item->quantity)
                    ->implode(', ');
                fputcsv($handle, [
                    $order->id,
                    $order->user?->name ?? '',
                    $order->user?->email ?? '',
                    $items,
                    number_format($order->total, 2),
                    $order->status,
                    $order->created_at->format('Y-m-d'),
                ]);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
