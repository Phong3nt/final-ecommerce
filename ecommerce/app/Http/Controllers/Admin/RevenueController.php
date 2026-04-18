<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\RefundTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class RevenueController extends Controller
{
    // Statuses that count as revenue-generating
    private const REVENUE_STATUSES = ['paid', 'processing', 'shipped', 'delivered'];

    // RM-001: Admin revenue report — daily / weekly / monthly / custom range
    public function index(Request $request): View
    {
        // Period: falls back to 'monthly' for any unrecognised value (AC: invalid period falls back to default)
        $allowed = ['daily', 'weekly', 'monthly', 'custom'];
        $period  = in_array($request->get('period'), $allowed) ? $request->get('period') : 'monthly';

        // Date range (validated, used only when period === 'custom')
        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to'   => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $dateFrom = isset($validated['date_from']) ? Carbon::parse($validated['date_from'])->startOfDay() : null;
        $dateTo   = isset($validated['date_to'])   ? Carbon::parse($validated['date_to'])->endOfDay()   : null;

        // Build periods and key format
        [$periods, $keyFmt, $labelFmt] = $this->buildPeriods($period, $dateFrom, $dateTo);

        // Gross revenue per period (revenue-status orders)
        $rangeEnd = $periods->last()->copy()->endOfDay();
        $grossRows = Order::whereIn('status', self::REVENUE_STATUSES)
            ->where('created_at', '>=', $periods->first())
            ->where('created_at', '<=', $rangeEnd)
            ->get(['total', 'created_at']);

        $grossMap = [];
        foreach ($grossRows as $o) {
            $key = Carbon::parse($o->created_at)->format($keyFmt);
            $grossMap[$key] = ($grossMap[$key] ?? 0.0) + $o->total;
        }

        // Refunds per period — from refund_transactions joined via orders
        $refundRows = RefundTransaction::join('orders', 'refund_transactions.order_id', '=', 'orders.id')
            ->where('orders.created_at', '>=', $periods->first())
            ->where('orders.created_at', '<=', $rangeEnd)
            ->get(['refund_transactions.amount', 'orders.created_at']);

        $refundMap = [];
        foreach ($refundRows as $r) {
            $key = Carbon::parse($r->created_at)->format($keyFmt);
            $refundMap[$key] = ($refundMap[$key] ?? 0.0) + $r->amount;
        }

        // Build rows
        $rows = $periods->map(function ($p) use ($keyFmt, $labelFmt, $grossMap, $refundMap) {
            $key     = $p->format($keyFmt);
            $gross   = round($grossMap[$key] ?? 0, 2);
            $refunds = round($refundMap[$key] ?? 0, 2);
            return [
                'label'   => $p->format($labelFmt),
                'gross'   => $gross,
                'refunds' => $refunds,
                'net'     => round($gross - $refunds, 2),
            ];
        })->values()->all();

        $totals = [
            'gross'   => round(array_sum(array_column($rows, 'gross')), 2),
            'refunds' => round(array_sum(array_column($rows, 'refunds')), 2),
            'net'     => round(array_sum(array_column($rows, 'net')), 2),
        ];

        return view('admin.revenue.index', compact(
            'rows', 'totals', 'period', 'dateFrom', 'dateTo'
        ));
    }

    /**
     * Returns [periods (Collection of Carbon), keyFormat, labelFormat].
     *
     * For custom range: day-by-day from dateFrom to dateTo (max 366 days capped).
     */
    private function buildPeriods(string $period, ?Carbon $dateFrom, ?Carbon $dateTo): array
    {
        return match ($period) {
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
            'custom' => $this->buildCustomPeriods($dateFrom, $dateTo),
            default  => [  // monthly
                collect(range(0, 11))->map(fn($i) => now()->subMonths(11 - $i)->startOfMonth()),
                'Y-m',
                'M Y',
            ],
        };
    }

    private function buildCustomPeriods(?Carbon $from, ?Carbon $to): array
    {
        $from = $from ?? now()->subDays(29)->startOfDay();
        $to   = $to   ?? now()->endOfDay();

        $days    = (int) $from->diffInDays($to);
        $days    = min($days, 365); // cap at 1 year
        $periods = collect(range(0, $days))->map(fn($i) => $from->copy()->addDays($i)->startOfDay());

        return [$periods, 'Y-m-d', 'M d'];
    }
}
