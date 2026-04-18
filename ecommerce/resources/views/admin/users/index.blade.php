<!DOCTYPE html>
<html>

<head>
    <title>Admin — Users</title>
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

        .filter-form input {
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

        th,
        td {
            padding: .65rem .9rem;
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

        .pagination {
            margin-top: 1rem;
            display: flex;
            gap: .3rem;
        }

        .pagination a,
        .pagination span {
            padding: .3rem .7rem;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            font-size: .85rem;
            text-decoration: none;
            color: #0d6efd;
        }

        .pagination span.active {
            background: #0d6efd;
            color: #fff;
            border-color: #0d6efd;
        }

        .empty {
            text-align: center;
            padding: 2rem;
            color: #6c757d;
        }
    </style>
</head>

<body>
    <h1>Users</h1>

    {{-- Search Form --}}
    <form method="GET" action="{{ route('admin.users.index') }}" class="filter-form">
        <div class="fg">
            <label for="search">Search</label>
            <input type="text" name="search" id="search" placeholder="Name or email" value="{{ request('search') }}"
                style="min-width:220px;">
        </div>
        <div class="fg" style="flex-direction:row;gap:.4rem;margin-top:.2rem;">
            <button type="submit" class="btn btn-primary">Search</button>
            @if(request()->filled('search'))
                <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Clear</a>
            @endif
        </div>
    </form>

    <p style="color:#6c757d;font-size:.875rem;margin-bottom:.75rem;">
        {{ $users->total() }} user{{ $users->total() !== 1 ? 's' : '' }} found
    </p>

    @if($users->isEmpty())
        <div class="empty">No users found.</div>
    @else
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Registered</th>
                    <th>Orders</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                    <tr>
                        <td>{{ $user->id }}</td>
                        <td><a href="{{ route('admin.users.show', $user) }}" style="color:#0d6efd;text-decoration:none;">{{ $user->name }}</a></td>
                        <td>{{ $user->email }}</td>
                        <td>
                            @foreach($user->roles as $role)
                                <span class="badge badge-{{ $role->name }}">{{ $role->name }}</span>
                            @endforeach
                        </td>
                        <td>{{ $user->created_at->format('d M Y') }}</td>
                        <td>{{ $user->orders_count }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Pagination --}}
        @if($users->hasPages())
            <div class="pagination">
                @if($users->onFirstPage())
                    <span>&laquo;</span>
                @else
                    <a href="{{ $users->previousPageUrl() }}">&laquo;</a>
                @endif

                @foreach($users->getUrlRange(max(1, $users->currentPage() - 2), min($users->lastPage(), $users->currentPage() + 2)) as $page => $url)
                    @if($page == $users->currentPage())
                        <span class="active">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}">{{ $page }}</a>
                    @endif
                @endforeach

                @if($users->hasMorePages())
                    <a href="{{ $users->nextPageUrl() }}">&raquo;</a>
                @else
                    <span>&raquo;</span>
                @endif
            </div>
        @endif
    @endif

</body>

</html>