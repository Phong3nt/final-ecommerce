<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * IMP-016: Consolidated audit log viewer for admin.
 * Displays all audit log entries with filtering by action type and date range.
 */
class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'action'     => ['nullable', 'string', 'max:60'],
            'date_from'  => ['nullable', 'date'],
            'date_to'    => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $query = AuditLog::with('user')->latest();

        if (!empty($validated['action'])) {
            $query->where('action', $validated['action']);
        }

        if (!empty($validated['date_from'])) {
            $query->whereDate('created_at', '>=', $validated['date_from']);
        }

        if (!empty($validated['date_to'])) {
            $query->whereDate('created_at', '<=', $validated['date_to']);
        }

        /** @var \Illuminate\Pagination\LengthAwarePaginator $logs */
        $logs = $query->paginate(30);
        $logs->withQueryString();

        // Distinct action types for the filter dropdown
        $actions = AuditLog::select('action')->distinct()->orderBy('action')->pluck('action');

        return view('admin.audit-log.index', compact('logs', 'actions', 'validated'));
    }
}
