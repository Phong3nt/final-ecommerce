<!DOCTYPE html>
<html>

<head>
    <title>Admin — User Profile</title>
    <style>
        body {
            font-family: sans-serif;
            margin: 2rem;
            background: #f5f5f5;
        }

        h1 {
            margin-bottom: .25rem;
        }

        .breadcrumb {
            font-size: .85rem;
            color: #6c757d;
            margin-bottom: 1.5rem;
        }

        .breadcrumb a {
            color: #0d6efd;
            text-decoration: none;
        }

        .card {
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 1.25rem 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .07);
        }

        .card h2 {
            margin: 0 0 1rem 0;
            font-size: 1.1rem;
            color: #212529;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: .5rem;
        }

        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: .6rem 2rem;
        }

        .profile-grid .label {
            font-size: .8rem;
            font-weight: 600;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .profile-grid .value {
            font-size: .95rem;
            color: #212529;
        }

        .badge {
            display: inline-block;
            padding: .2rem .55rem;
            border-radius: 20px;
            font-size: .78rem;
            font-weight: 600;
            text-transform: capitalize;
        }

        .badge-admin {
            background: #cfe2ff;
            color: #084298;
        }

        .badge-user {
            background: #d1e7dd;
            color: #0f5132;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: .6rem .85rem;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
            font-size: .9rem;
        }

        th {
            background: #f8f9fa;
            font-weight: 700;
            white-space: nowrap;
        }

        tr:last-child td {
            border-bottom: none;
        }

        .status-badge {
            display: inline-block;
            padding: .15rem .5rem;
            border-radius: 4px;
            font-size: .78rem;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-processing {
            background: #cfe2ff;
            color: #084298;
        }

        .status-shipped {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-delivered {
            background: #d1e7dd;
            color: #0f5132;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #842029;
        }

        .status-refunded {
            background: #e2e3e5;
            color: #383d41;
        }

        .empty {
            text-align: center;
            padding: 2rem;
            color: #6c757d;
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

        .btn-secondary {
            background: #6c757d;
            color: #fff;
        }

        .btn-danger {
            background: #dc3545;
            color: #fff;
        }

        .btn-success {
            background: #198754;
            color: #fff;
        }

        .account-status {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            font-weight: 600;
            font-size: .9rem;
        }

        .account-status.active {
            color: #0f5132;
        }

        .account-status.suspended {
            color: #842029;
        }

        .alert {
            padding: .75rem 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
            font-size: .9rem;
        }

        .btn-primary {
            background: #0d6efd;
            color: #fff;
        }

        .role-form {
            display: flex;
            align-items: center;
            gap: .75rem;
            flex-wrap: wrap;
        }

        select.form-select {
            padding: .35rem .65rem;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: .9rem;
            background: #fff;
        }

        .alert-success {
            background: #d1e7dd;
            color: #0f5132;
            border: 1px solid #a3cfbb;
        }

        .alert-danger {
            background: #f8d7da;
            color: #842029;
            border: 1px solid #f1aeb5;
        }
    </style>
</head>

<body>
    @include('partials.toast')
    <div class="breadcrumb">
        <a href="{{ route('admin.users.index') }}">Users</a> &rsaquo; {{ $user->name }}
    </div>

    <h1>User Profile</h1>

    {{-- Profile Summary --}}
    <div class="card">
        <h2>Profile</h2>
        <div class="profile-grid">
            <div>
                <div class="label">Name</div>
                <div class="value">{{ $user->name }}</div>
            </div>
            <div>
                <div class="label">Email</div>
                <div class="value">{{ $user->email }}</div>
            </div>
            <div>
                <div class="label">Role</div>
                <div class="value">
                    @forelse($user->roles as $role)
                        <span class="badge badge-{{ $role->name }}">{{ $role->name }}</span>
                    @empty
                        <span style="color:#6c757d;">—</span>
                    @endforelse
                </div>
            </div>
            <div>
                <div class="label">Registered</div>
                <div class="value">{{ $user->created_at->format('d M Y') }}</div>
            </div>
            <div>
                <div class="label">Total Orders</div>
                <div class="value">{{ $user->orders_count }}</div>
            </div>
            <div>
                <div class="label">Account Status</div>
                <div class="value">
                    @if($user->is_active)
                        <span class="account-status active">&#10003; Active</span>
                    @else
                        <span class="account-status suspended">&#9888; Suspended</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- UM-003: Toggle status button --}}
        <div style="margin-top:1.25rem;padding-top:1rem;border-top:1px solid #dee2e6;">
            @if($user->id !== auth()->id())
                <form method="POST" action="{{ route('admin.users.toggle-status', $user) }}"
                    onsubmit="return confirm('{{ $user->is_active ? 'Suspend this account? The user will not be able to log in.' : 'Activate this account? The user will be able to log in again.' }}')">
                    @csrf
                    @method('PATCH')
                    @if($user->is_active)
                        <button type="submit" class="btn btn-danger">Suspend Account</button>
                    @else
                        <button type="submit" class="btn btn-success">Activate Account</button>
                    @endif
                </form>
            @endif
        </div>

        {{-- UM-004: Assign / change role --}}
        @if($user->id !== auth()->id())
            <div style="margin-top:1.25rem;padding-top:1rem;border-top:1px solid #dee2e6;">
                <form method="POST" action="{{ route('admin.users.assign-role', $user) }}" class="role-form"
                    onsubmit="return confirm('Change this user\'s role?')">
                    @csrf
                    @method('PATCH')
                    <label for="role_select" style="font-weight:600;font-size:.9rem;">Role:</label>
                    <select id="role_select" name="role" class="form-select">
                        <option value="user" {{ $user->hasRole('user') ? 'selected' : '' }}>user</option>
                        <option value="admin" {{ $user->hasRole('admin') ? 'selected' : '' }}>admin</option>
                    </select>
                    <button type="submit" class="btn btn-primary">Save Role</button>
                </form>
            </div>
        @endif
    </div>

    {{-- Order History (last 10) --}}
    <div class="card">
        <h2>Order History <small style="font-weight:400;color:#6c757d;">(last 10)</small></h2>

        @if($orders->isEmpty())
            <div class="empty">No orders found for this user.</div>
        @else
            <table>
                <thead>
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
                                <span class="status-badge status-{{ $order->status }}">
                                    {{ $order->status }}
                                </span>
                            </td>
                            <td>${{ number_format($order->total, 2) }}</td>
                            <td>{{ $order->created_at->format('d M Y') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">&larr; Back to Users</a>
</body>

</html>