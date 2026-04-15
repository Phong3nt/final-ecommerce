<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\OrderStatusChanged;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class OrderStatusController extends Controller
{
    private const VALID_STATUSES = ['processing', 'shipped', 'delivered'];

    private const STATUS_TIMESTAMPS = [
        'processing' => 'processing_at',
        'shipped' => 'shipped_at',
        'delivered' => 'delivered_at',
    ];

    public function update(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(self::VALID_STATUSES)],
        ]);

        $newStatus = $validated['status'];
        $timestampColumn = self::STATUS_TIMESTAMPS[$newStatus];

        $order->update([
            'status' => $newStatus,
            $timestampColumn => now(),
        ]);

        Mail::to($order->user)->send(new OrderStatusChanged($order));

        return redirect()->back()->with('success', 'Order status updated to ' . $newStatus . '.');
    }
}
