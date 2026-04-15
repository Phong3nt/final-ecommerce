<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

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
}
