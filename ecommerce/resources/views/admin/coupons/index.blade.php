@extends('layouts.admin')
{{-- @include('partials.toast') --}}

@section('title', 'Admin — Coupons')
@section('page-title', 'Coupons')

@section('content')
    <div x-data x-init="$el.classList.add('fade-in')">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">Coupons</h4>
            <a href="{{ route('admin.coupons.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i> New Coupon
            </a>
        </div>

        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Code</th>
                                <th>Type</th>
                                <th>Value</th>
                                <th>Min Order</th>
                                <th>Usage Limit</th>
                                <th>Times Used</th>
                                <th>Expires</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($coupons as $coupon)
                                <tr>
                                    <td><strong>{{ $coupon->code }}</strong></td>
                                    <td>{{ $coupon->type === 'percent' ? '%' : 'Fixed' }}</td>
                                    <td>{{ $coupon->type === 'percent' ? $coupon->value . '%' : '$' . number_format($coupon->value, 2) }}
                                    </td>
                                    <td>{{ $coupon->min_order_amount !== null ? '$' . number_format($coupon->min_order_amount, 2) : '—' }}
                                    </td>
                                    <td>{{ $coupon->usage_limit ?? '∞' }}</td>
                                    <td>{{ $coupon->times_used }}</td>
                                    <td>{{ $coupon->expires_at ? $coupon->expires_at->format('Y-m-d') : '—' }}</td>
                                    <td>
                                        <span class="badge {{ $coupon->is_active ? 'bg-success' : 'bg-danger' }}">
                                            {{ $coupon->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.coupons.edit', $coupon) }}"
                                            class="btn btn-outline-secondary btn-sm">Edit</a>

                                        <form method="POST" action="{{ route('admin.coupons.toggle', $coupon) }}"
                                            style="display:inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-outline-primary btn-sm ms-1">
                                                {{ $coupon->is_active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>

                                        <form method="POST" action="{{ route('admin.coupons.destroy', $coupon) }}"
                                            style="display:inline"
                                            data-confirm="Delete coupon &quot;{{ $coupon->code }}&quot;? This cannot be undone.">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm ms-1">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">No coupons yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="mt-3">{{ $coupons->links() }}</div>
    </div>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('form[data-confirm]').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                if (!confirm(form.dataset.confirm)) { e.preventDefault(); }
            });
        });
    </script>
@endpush