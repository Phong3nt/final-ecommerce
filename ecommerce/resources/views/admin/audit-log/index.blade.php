<!DOCTYPE html>
<html>

<head>
    <title>Admin — Audit Log</title>
    <style>
        body {
            font-family: sans-serif;
            margin: 2rem;
            background: #f5f5f5;
        }

        h1 {
            margin-bottom: 1rem;
        }

        .filter-form {
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            display: flex;
            flex-wrap: wrap;
            gap: .75rem;
            align-items: flex-end;
        }

        .filter-form .fg {
            display: flex;
            flex-direction: column;
            gap: .2rem;
        }

        .filter-form label {
            font-size: .8rem;
            font-weight: 600;
            color: #495057;
        }

        .filter-form input,
        .filter-form select {
            padding: .35rem .6rem;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: .9rem;
        }

        .btn {
            display: inline-block;
            padding: .4rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: .9rem;
            text-decoration: none;
        }

        .btn-primary {
            background: #0d6efd;
            color: #fff;
        }

        .btn-secondary {
            background: #6c757d;
            color: #fff;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .07);
        }

        th, td {
            padding: .65rem .9rem;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
            font-size: .9rem;
        }

        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
            font-size: .8rem;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background: #f8f9fa;
        }

        .action-badge {
            display: inline-block;
            padding: .15rem .5rem;
            border-radius: 20px;
            font-size: .78rem;
            font-weight: 600;
            background: #e2e8f0;
            color: #374151;
        }

        .action-badge.auth { background: #dbeafe; color: #1e40af; }
        .action-badge.product { background: #dcfce7; color: #166534; }
        .action-badge.user { background: #fef3c7; color: #92400e; }

        .changes-toggle {
            font-size: .78rem;
            color: #0d6efd;
            cursor: pointer;
            background: none;
            border: none;
            padding: 0;
            text-decoration: underline;
        }

        .changes-detail {
            font-size: .78rem;
            color: #495057;
            margin-top: .3rem;
            white-space: pre-wrap;
            display: none;
        }

        .pagination {
            margin-top: 1rem;
            display: flex;
            gap: .35rem;
        }

        .pagination a, .pagination span {
            display: inline-block;
            padding: .3rem .7rem;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            font-size: .85rem;
            color: #0d6efd;
            text-decoration: none;
            background: #fff;
        }

        .pagination span.active {
            background: #0d6efd;
            color: #fff;
            border-color: #0d6efd;
        }

        .empty-row td {
            text-align: center;
            color: #6c757d;
            padding: 2rem;
        }
    </style>
</head>

<body>

<h1 data-imp016="page-title">Audit Log</h1>

<p><a href="{{ route('admin.dashboard') }}">← Back to Dashboard</a></p>

<form method="GET" action="{{ route('admin.audit-log.index') }}"
      data-imp016="filter-form"
      class="filter-form">

    <div class="fg">
        <label for="action">Action</label>
        <select id="action" name="action" data-imp016="filter-action">
            <option value="">All actions</option>
            @foreach ($actions as $act)
                <option value="{{ $act }}" @selected(($validated['action'] ?? '') === $act)>{{ $act }}</option>
            @endforeach
        </select>
    </div>

    <div class="fg">
        <label for="date_from">From</label>
        <input type="date" id="date_from" name="date_from"
               value="{{ $validated['date_from'] ?? '' }}"
               data-imp016="filter-date-from">
    </div>

    <div class="fg">
        <label for="date_to">To</label>
        <input type="date" id="date_to" name="date_to"
               value="{{ $validated['date_to'] ?? '' }}"
               data-imp016="filter-date-to">
    </div>

    <button type="submit" class="btn btn-primary" data-imp016="filter-submit">Filter</button>
    <a href="{{ route('admin.audit-log.index') }}" class="btn btn-secondary" data-imp016="filter-reset">Reset</a>
</form>

<div data-imp016="table-wrap" style="overflow-x:auto;">
    <table data-imp016="audit-table">
        <thead>
            <tr>
                <th>Date / Time</th>
                <th>Actor</th>
                <th>Action</th>
                <th>Subject</th>
                <th>IP Address</th>
                <th>Changes</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($logs as $log)
                @php
                    $actionPrefix = explode('.', $log->action)[0];
                @endphp
                <tr data-imp016="audit-row">
                    <td>{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                    <td>
                        @if ($log->user)
                            {{ $log->user->name }}<br>
                            <small style="color:#6c757d;">{{ $log->user->email }}</small>
                        @else
                            <span style="color:#6c757d;">—</span>
                        @endif
                    </td>
                    <td>
                        <span class="action-badge {{ $actionPrefix }}" data-imp016="action-badge">{{ $log->action }}</span>
                    </td>
                    <td>{{ $log->subject_type }} #{{ $log->subject_id }}</td>
                    <td>{{ $log->ip_address ?? '—' }}</td>
                    <td>
                        @if ($log->old_values || $log->new_values)
                            <button class="changes-toggle" onclick="
                                var d = this.nextElementSibling;
                                d.style.display = d.style.display === 'block' ? 'none' : 'block';
                                this.textContent = d.style.display === 'block' ? 'hide' : 'show';
                            " data-imp016="changes-toggle">show</button>
                            <div class="changes-detail" data-imp016="changes-detail">
@if ($log->old_values)Before: {{ json_encode($log->old_values, JSON_PRETTY_PRINT) }}
@endif
@if ($log->new_values)After:  {{ json_encode($log->new_values, JSON_PRETTY_PRINT) }}
@endif</div>
                        @else
                            <span style="color:#6c757d;">—</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr class="empty-row">
                    <td colspan="6" data-imp016="empty-state">No audit log entries found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($logs->hasPages())
    <div class="pagination" data-imp016="pagination">
        {!! $logs->links('pagination::simple-bootstrap-4') !!}
    </div>
@endif

</body>
</html>
