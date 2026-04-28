@extends('layouts.admin')
{{-- @include('partials.toast') --}}

@section('title', 'Admin — User Profile')
@section('page-title', 'User Profile')

@section('content')
<div x-data x-init="$el.classList.add('fade-in')">
    <div class="mb-3">
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to Users
        </a>
    </div>

    {{-- Profile --}}
    <div class="card shadow-sm border-0 rounded-3 mb-3">
        <div class="card-body">
            <h6 class="card-title border-bottom pb-2 mb-3">Profile</h6>
            <div class="row g-3">
                <div class="col-sm-6">
                    <div class="small fw-semibold text-muted text-uppercase">Name</div>
                    <div>{{ $user->name }}</div>
                </div>
                <div class="col-sm-6">
                    <div class="small fw-semibold text-muted text-uppercase">Email</div>
                    <div>{{ $user->email }}</div>
                </div>
                <div class="col-sm-6">
                    <div class="small fw-semibold text-muted text-uppercase">Role</div>
                    <div>
                        @forelse($user->roles as $role)
                            <span class="badge bg-{{ $role->name === 'admin' ? 'primary' : 'success' }}">{{ $role->name }}</span>
                        @empty
                            <span class="text-muted">—</span>
                        @endforelse
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="small fw-semibold text-muted text-uppercase">Registered</div>
                    <div>{{ $user->created_at->format('d M Y') }}</div>
                </div>
                <div class="col-sm-6">
                    <div class="small fw-semibold text-muted text-uppercase">Total Orders</div>
                    <div>{{ $user->orders_count }}</div>
                </div>
                <div class="col-sm-6">
                    <div class="small fw-semibold text-muted text-uppercase">Account Status</div>
                    <div>
                        @if($user->is_active)
                            <span class="text-success fw-semibold">&#10003; Active</span>
                        @else
                            <span class="text-danger fw-semibold">&#9888; Suspended</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- UM-003: Toggle status --}}
            @if($user->id !== auth()->id())
                <div class="border-top mt-3 pt-3">
                    <form method="POST" action="{{ route('admin.users.toggle-status', $user) }}"
                        onsubmit="return confirm('{{ $user->is_active ? 'Suspend this account? The user will not be able to log in.' : 'Activate this account? The user will be able to log in again.' }}')">
                        @csrf
                        @method('PATCH')
                        @if($user->is_active)
                            <button type="submit" class="btn btn-danger btn-sm">Suspend Account</button>
                        @else
                            <button type="submit" class="btn btn-success btn-sm">Activate Account</button>
                        @endif
                    </form>
                </div>
            @endif

            {{-- UM-004: Assign role --}}
            @if($user->id !== auth()->id())
                <div class="border-top mt-3 pt-3">
                    <form method="POST" action="{{ route('admin.users.assign-role', $user) }}"
                        class="d-flex align-items-center gap-2 flex-wrap"
                        onsubmit="return confirm('Change this user\'s role?')">
                        @csrf
                        @method('PATCH')
                        <label for="role_select" class="fw-semibold small mb-0">Role:</label>
                        <select id="role_select" name="role" class="form-select form-select-sm" style="max-width:120px;">
                            <option value="user" {{ $user->hasRole('user') ? 'selected' : '' }}>user</option>
                            <option value="admin" {{ $user->hasRole('admin') ? 'selected' : '' }}>admin</option>
                        </select>
                        <button type="submit" class="btn btn-primary btn-sm">Save Role</button>
                    </form>
                </div>
            @endif
        </div>
    </div>

    {{-- Order History --}}
    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-body">
            <h6 class="card-title border-bottom pb-2 mb-3">
                Order History <small class="text-muted fw-normal">(last 10)</small>
            </h6>
            @if($orders->isEmpty())
                <p class="text-muted text-center py-3">No orders found for this user.</p>
            @else
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Order #</th>
                                <th>Status</th>
                                <th>Total</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($orders as $order)
                                <tr>
                                    <td>#{{ $order->id }}</td>
                                    <td>
                                        <span class="badge bg-{{ $order->status === 'pending' ? 'warning text-dark' : ($order->status === 'cancelled' ? 'danger' : ($order->status === 'delivered' || $order->status === 'paid' ? 'success' : 'primary')) }}">
                                            {{ $order->status }}
                                        </span>
                                    </td>
                                    <td>${{ number_format($order->total, 2) }}</td>
                                    <td>{{ $order->created_at->format('d M Y') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection