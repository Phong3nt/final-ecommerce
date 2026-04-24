@extends('layouts.admin')
{{-- @include('partials.toast') --}}

@section('title', 'Admin — Categories')
@section('page-title', 'Categories')

@section('content')
    <div x-data x-init="$el.classList.add('fade-in')">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">Categories</h4>
            <a href="{{ route('admin.categories.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i> New Category
            </a>
        </div>

        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Parent</th>
                                <th>Products</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($categories as $category)
                                <tr>
                                    <td>{{ $category->id }}</td>
                                    <td>{{ $category->name }}</td>
                                    <td>{{ $category->parent?->name ?? '—' }}</td>
                                    <td>{{ $category->products()->count() }}</td>
                                    <td>
                                        <a href="{{ route('admin.categories.edit', $category) }}"
                                            class="btn btn-outline-secondary btn-sm">Edit</a>
                                        <form method="POST" action="{{ route('admin.categories.destroy', $category) }}"
                                            style="display:inline"
                                            data-confirm="Delete &quot;{{ $category->name }}&quot;? Products in this category will be uncategorised.">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm ms-1">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">No categories yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="mt-3">{{ $categories->links() }}</div>
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