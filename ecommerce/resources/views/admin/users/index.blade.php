@extends('layouts.admin')

@section('title', 'Admin — Users')
@section('page-title', 'Users')

@section('content')
<div x-data x-init="$el.classList.add('fade-in')">
    {{-- Search Form --}}
    <div class="card shadow-sm border-0 rounded-3 mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.users.index') }}" class="d-flex gap-2 align-items-end">
                <div>
                    <label for="search" class="form-label fw-semibold small mb-1">Search</label>
                    <input type="text" name="search" id="search" class="form-control form-control-sm" placeholder="Name or email" value="{{ request('search') }}" style="min-width:220px;">
                </div>
                <div class="d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm">Search</button>
                    @if(request()->filled('search'))
                        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <p class="small text-muted mb-2">
        {{ $users->total() }} user{{ $users->total() !== 1 ? 's' : '' }} found
    </p>

    @if($users->isEmpty())
        <div class="text-center text-muted py-4">No users found.</div>
    @else
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-body p-0">
                <div class="imp013-table-wrap" data-imp013="table-wrap" x-data="imp013TableSort()">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="imp013-th--sort" data-imp013="sortable-th" aria-sort="none" x-on:click="sort(0, 'num')">
                                    # <span class="imp013-sort-icon" aria-hidden="true">↕</span>
                                </th>
                                <th class="imp013-th--sort" data-imp013="sortable-th" aria-sort="none" x-on:click="sort(1, 'str')">
                                    Name <span class="imp013-sort-icon" aria-hidden="true">↕</span>
                                </th>
                                <th class="imp013-th--sort" data-imp013="sortable-th" aria-sort="none" x-on:click="sort(2, 'str')">
                                    Email <span class="imp013-sort-icon" aria-hidden="true">↕</span>
                                </th>
                                <th>Role</th>
                                <th class="imp013-th--sort" data-imp013="sortable-th" aria-sort="none" x-on:click="sort(4, 'date')">
                                    Registered <span class="imp013-sort-icon" aria-hidden="true">↕</span>
                                </th>
                                <th class="imp013-th--sort" data-imp013="sortable-th" aria-sort="none" x-on:click="sort(5, 'num')">
                                    Orders <span class="imp013-sort-icon" aria-hidden="true">↕</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $user)
                                <tr>
                                    <td>{{ $user->id }}</td>
                                    <td>
                                        <a href="{{ route('admin.users.show', $user) }}" class="text-decoration-none">
                                            {{ $user->name }}
                                        </a>
                                    </td>
                                    <td>{{ $user->email }}</td>
                                    <td>
                                        @foreach($user->roles as $role)
                                            <span class="badge bg-{{ $role->name === 'admin' ? 'primary' : 'success' }}">{{ $role->name }}</span>
                                        @endforeach
                                    </td>
                                    <td>{{ $user->created_at->format('d M Y') }}</td>
                                    <td>{{ $user->orders_count }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @if($users->hasPages())
            <div class="mt-3">{{ $users->links() }}</div>
        @endif
    @endif
</div>
@endsection

@push('styles')
<style>
    .imp013-table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    .imp013-th--sort { cursor: pointer; user-select: none; white-space: nowrap; }
    .imp013-th--sort:hover { background: #edf0f3; }
    .imp013-sort-icon { font-size: .7rem; color: #adb5bd; margin-left: .25rem; }
    .imp013-th--asc .imp013-sort-icon, .imp013-th--desc .imp013-sort-icon { color: #0d6efd; }
</style>
@endpush

@push('scripts')
<script>
    function imp013TableSort() {
        return {
            col: null, dir: 'asc',
            sort(colIndex, type) {
                if (this.col === colIndex) { this.dir = this.dir === 'asc' ? 'desc' : 'asc'; }
                else { this.col = colIndex; this.dir = 'asc'; }
                const dir = this.dir;
                const tbody = this.$el.querySelector('tbody');
                if (!tbody) return;
                const rows = [...tbody.querySelectorAll('tr')];
                rows.sort((a, b) => {
                    const aText = a.cells[colIndex] ? a.cells[colIndex].innerText.trim() : '';
                    const bText = b.cells[colIndex] ? b.cells[colIndex].innerText.trim() : '';
                    let cmp;
                    if (type === 'num') { cmp = (parseFloat(aText.replace(/[^0-9.-]/g, '')) || 0) - (parseFloat(bText.replace(/[^0-9.-]/g, '')) || 0); }
                    else if (type === 'date') { cmp = new Date(aText) - new Date(bText); }
                    else { cmp = aText.localeCompare(bText); }
                    return dir === 'asc' ? cmp : -cmp;
                });
                rows.forEach(r => tbody.appendChild(r));
                const ths = this.$el.querySelectorAll('[data-imp013="sortable-th"]');
                ths.forEach(th => {
                    const idx = parseInt(th.getAttribute('data-col-index') || '-1');
                    th.setAttribute('aria-sort', idx === colIndex ? (dir === 'asc' ? 'ascending' : 'descending') : 'none');
                    const icon = th.querySelector('.imp013-sort-icon');
                    if (icon) {
                        if (idx === colIndex) { th.classList.add(dir === 'asc' ? 'imp013-th--asc' : 'imp013-th--desc'); th.classList.remove(dir === 'asc' ? 'imp013-th--desc' : 'imp013-th--asc'); icon.textContent = dir === 'asc' ? '▲' : '▼'; }
                        else { th.classList.remove('imp013-th--asc', 'imp013-th--desc'); icon.textContent = '↕'; }
                    }
                });
            }
        };
    }
</script>
@endpush