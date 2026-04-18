<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\RefundTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
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
        $period = in_array($request->get('period'), $allowed) ? $request->get('period') : 'monthly';

        // Date range (validated, used only when period === 'custom')
        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $dateFrom = isset($validated['date_from']) ? Carbon::parse($validated['date_from'])->startOfDay() : null;
        $dateTo = isset($validated['date_to']) ? Carbon::parse($validated['date_to'])->endOfDay() : null;

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
            $key = $p->format($keyFmt);
            $gross = round($grossMap[$key] ?? 0, 2);
            $refunds = round($refundMap[$key] ?? 0, 2);
            return [
                'label' => $p->format($labelFmt),
                'gross' => $gross,
                'refunds' => $refunds,
                'net' => round($gross - $refunds, 2),
            ];
        })->values()->all();

        $totals = [
            'gross' => round(array_sum(array_column($rows, 'gross')), 2),
            'refunds' => round(array_sum(array_column($rows, 'refunds')), 2),
            'net' => round(array_sum(array_column($rows, 'net')), 2),
        ];

        return view('admin.revenue.index', compact(
            'rows',
            'totals',
            'period',
            'dateFrom',
            'dateTo'
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
            default => [  // monthly
                collect(range(0, 11))->map(fn($i) => now()->subMonths(11 - $i)->startOfMonth()),
                'Y-m',
                'M Y',
            ],
        };
    }

    private function buildCustomPeriods(?Carbon $from, ?Carbon $to): array
    {
        $from = $from ?? now()->subDays(29)->startOfDay();
        $to = $to ?? now()->endOfDay();

        $days = (int) $from->diffInDays($to);
        $days = min($days, 365); // cap at 1 year
        $periods = collect(range(0, $days))->map(fn($i) => $from->copy()->addDays($i)->startOfDay());

        return [$periods, 'Y-m-d', 'M d'];
    }

    // RM-002: Admin revenue by category/product — sortable table + CSV export
    public function products(Request $request): View
    {
        $validated = $request->validate([
            'sort' => ['nullable'],
            'direction' => ['nullable'],
            'category' => ['nullable', 'integer'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $allowedSorts = ['units_sold', 'gross_revenue', 'product_name', 'category_name'];
        $sort = in_array($validated['sort'] ?? '', $allowedSorts) ? $validated['sort'] : 'gross_revenue';
        $direction = ($validated['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $category = isset($validated['category']) ? (int) $validated['category'] : null;
        $dateFrom = isset($validated['date_from']) ? Carbon::parse($validated['date_from'])->startOfDay() : null;
        $dateTo = isset($validated['date_to']) ? Carbon::parse($validated['date_to'])->endOfDay() : null;

        $rows = $this->buildProductRows($sort, $direction, $category, $dateFrom, $dateTo);
        $categories = Category::orderBy('name')->get(['id', 'name']);

        return view('admin.revenue.products', compact(
            'rows',
            'categories',
            'sort',
            'direction',
            'category',
            'dateFrom',
            'dateTo'
        ));
    }

    // RM-002: Export product revenue table as CSV
    public function exportProducts(Request $request): Response
    {
        $validated = $request->validate([
            'sort' => ['nullable'],
            'direction' => ['nullable'],
            'category' => ['nullable', 'integer'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $allowedSorts = ['units_sold', 'gross_revenue', 'product_name', 'category_name'];
        $sort = in_array($validated['sort'] ?? '', $allowedSorts) ? $validated['sort'] : 'gross_revenue';
        $direction = ($validated['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $category = isset($validated['category']) ? (int) $validated['category'] : null;
        $dateFrom = isset($validated['date_from']) ? Carbon::parse($validated['date_from'])->startOfDay() : null;
        $dateTo = isset($validated['date_to']) ? Carbon::parse($validated['date_to'])->endOfDay() : null;

        $rows = $this->buildProductRows($sort, $direction, $category, $dateFrom, $dateTo);

        $csv = "Product,Category,Units Sold,Gross Revenue\n";
        foreach ($rows as $row) {
            $csv .= implode(',', [
                '"' . str_replace('"', '""', $row->product_name) . '"',
                '"' . str_replace('"', '""', $row->category_name ?? '') . '"',
                $row->units_sold,
                number_format($row->gross_revenue, 2, '.', ''),
            ]) . "\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="revenue-by-product-' . now()->format('Y-m-d') . '.csv"',
        ]);
    }

    /**
     * Query order_items joined to orders (revenue statuses) + products + categories.
     * Returns a collection of stdClass rows with: product_name, category_name, units_sold, gross_revenue.
     */
    private function buildProductRows(
        string $sort,
        string $direction,
        ?int $categoryId,
        ?Carbon $dateFrom,
        ?Carbon $dateTo
    ) {
        $query = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->leftJoin('products', 'order_items.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->whereIn('orders.status', self::REVENUE_STATUSES)
            ->select([
                'order_items.product_id',
                DB::raw('MAX(order_items.product_name) as product_name'),
                DB::raw('MAX(categories.name) as category_name'),
                DB::raw('SUM(order_items.quantity) as units_sold'),
                DB::raw('SUM(order_items.subtotal) as gross_revenue'),
            ])
            ->groupBy('order_items.product_id');

        if ($dateFrom) {
            $query->where('orders.created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->where('orders.created_at', '<=', $dateTo);
        }
        if ($categoryId !== null) {
            $query->where('products.category_id', $categoryId);
        }

        return $query->orderBy($sort, $direction)->get();
    }
}
