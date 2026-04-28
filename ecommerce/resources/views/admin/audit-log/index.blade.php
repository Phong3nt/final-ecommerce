@extends('layouts.admin')
@section('title', 'Audit Log — Admin')
@section('page-title', 'Audit Log')

@section('content')
<div x-data x-init="$el.classList.add('fade-in')">

    {{-- Filter card --}}
    <div class="card shadow-sm border-0 rounded-3 mb-4">
        <div class="card-body p-4">
            <form method="GET" action="{{ route('admin.audit-log.index') }}"
                  data-imp016="filter-form"
                  class="row g-3 align-items-end">

                <div class="col-md-3">
                    <label for="action" class="form-label small fw-semibold">Action</label>
                    <select id="action" name="action"
                            class="form-select form-select-sm"
                            data-imp016="filter-action">
                        <option value="">All actions</option>
                        @foreach ($actions as $act)
                            <option value="{{ $act }}" @selected(($validated['action'] ?? '') === $act)>{{ $act }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="date_from" class="form-label small fw-semibold">From</label>
                    <input type="date" id="date_from" name="date_from"
                           class="form-control form-control-sm"
                           value="{{ $validated['date_from'] ?? '' }}"
                           data-imp016="filter-date-from">
                </div>

                <div class="col-md-3">
                    <label for="date_to" class="form-label small fw-semibold">To</label>
                    <input type="date" id="date_to" name="date_to"
                           class="form-control form-control-sm"
                           value="{{ $validated['date_to'] ?? '' }}"
                           data-imp016="filter-date-to">
                </div>

                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm"
                            data-imp016="filter-submit">
                        <i class="bi bi-funnel me-1"></i>Filter
                    </button>
                    <a href="{{ route('admin.audit-log.index') }}"
                       class="btn btn-outline-secondary btn-sm"
                       data-imp016="filter-reset">
                        Reset
                    </a>
                </div>

            </form>
        </div>
    </div>

    {{-- Audit log table --}}
    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-body p-0">
            <div data-imp016="table-wrap" class="table-responsive">
                <table class="table table-hover align-middle mb-0"
                       data-imp016="audit-table">
                    <thead class="table-light">
                        <tr>
                            <th class="text-label ps-4 py-3">Date / Time</th>
                            <th class="text-label py-3">Actor</th>
                            <th class="text-label py-3">Action</th>
                            <th class="text-label py-3">Subject</th>
                            <th class="text-label py-3">IP Address</th>
                            <th class="text-label pe-4 py-3">Changes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $log)
                            @php $actionPrefix = explode('.', $log->action)[0]; @endphp
                            <tr data-imp016="audit-row">
                                <td class="ps-4 small text-muted">
                                    {{ $log->created_at->format('Y-m-d H:i:s') }}
                                </td>
                                <td>
                                    @if ($log->user)
                                        <span class="fw-medium small">{{ $log->user->name }}</span><br>
                                        <small class="text-muted">{{ $log->user->email }}</small>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $badgeClass = match($actionPrefix) {
                                            'auth'    => 'bg-primary bg-opacity-10 text-primary',
                                            'product' => 'bg-success bg-opacity-10 text-success',
                                            'user'    => 'bg-warning bg-opacity-10 text-warning',
                                            default    => 'bg-secondary bg-opacity-10 text-secondary',
                                        };
                                    @endphp
                                    <span class="badge rounded-pill {{ $badgeClass }} fw-medium"
                                          data-imp016="action-badge">
                                        {{ $log->action }}
                                    </span>
                                </td>
                                <td class="small text-muted">
                                    {{ $log->subject_type }} #{{ $log->subject_id }}
                                </td>
                                <td class="small text-muted">
                                    {{ $log->ip_address ?? '—' }}
                                </td>
                                <td class="pe-4" x-data="{ open: false }">
                                    @if ($log->old_values || $log->new_values)
                                        <button type="button"
                                                class="btn btn-link btn-sm p-0 text-decoration-none"
                                                @click="open = !open"
                                                data-imp016="changes-toggle">
                                            <span x-text="open ? 'hide' : 'show'" class="small">show</span>
                                        </button>
                                        <div x-show="open" x-transition
                                             class="mt-1 small text-muted"
                                             style="white-space:pre-wrap;font-family:monospace;font-size:.75rem;"
                                             data-imp016="changes-detail">@if ($log->old_values)Before: {{ json_encode($log->old_values, JSON_PRETTY_PRINT) }}
@endif
@if ($log->new_values)After:  {{ json_encode($log->new_values, JSON_PRETTY_PRINT) }}
@endif</div>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5"
                                    data-imp016="empty-state">
                                    <i class="bi bi-journal-text fs-1 d-block text-muted opacity-25 mb-2"></i>
                                    <span class="text-muted">No audit log entries found.</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if ($logs->hasPages())
        <div class="mt-3 d-flex justify-content-center" data-imp016="pagination">
            {{ $logs->links() }}
        </div>
    @endif

</div>
@endsection