<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class OrderController extends Controller
{
    /**
     * OH-001: Show the authenticated user's order history.
     * Orders listed newest-first, paginated at 10 per page.
     */
    public function index(): View
    {
        $orders = auth()->user()
            ->orders()
            ->latest()
            ->paginate(10);

        return view('orders.index', compact('orders'));
    }

    /**
     * OH-002: Show a single past order's detail.
     * Scoped to the authenticated user — returns 403 for orders belonging to others.
     */
    public function show(Order $order): View|RedirectResponse
    {
        if ($order->user_id !== auth()->id()) {
            abort(403);
        }

        $order->load('items');

        return view('orders.show', compact('order'));
    }
}
