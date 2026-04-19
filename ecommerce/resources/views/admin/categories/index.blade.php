<!DOCTYPE html>
<html>

<head>
    <title>Admin — Categories</title>
    <style>
        body {
            font-family: sans-serif;
            margin: 2rem;
            background: #f5f5f5;
        }

        h1 {
            margin-bottom: 1rem;
        }

        a.btn {
            display: inline-block;
            padding: .5rem 1rem;
            background: #0d6efd;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            margin-bottom: 1rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
        }

        th,
        td {
            text-align: left;
            padding: .6rem 1rem;
            border-bottom: 1px solid #ddd;
        }

        th {
            background: #343a40;
            color: #fff;
        }

        .btn-delete {
            background: none;
            border: none;
            color: #dc3545;
            cursor: pointer;
            font-size: inherit;
            padding: 0;
            margin-left: .75rem;
            text-decoration: underline;
        }

        .alert-success {
            background: #d1e7dd;
            border: 1px solid #badbcc;
            color: #0f5132;
            padding: .75rem 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
    </style>
</head>

<body>
    @include('partials.toast')
    <h1>Categories</h1>

    <a href="{{ route('admin.categories.create') }}" class="btn">+ New Category</a>

    <table>
        <thead>
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
                        <a href="{{ route('admin.categories.edit', $category) }}">Edit</a>
                        <form method="POST" action="{{ route('admin.categories.destroy', $category) }}"
                            style="display:inline"
                            data-confirm="Delete &quot;{{ $category->name }}&quot;? Products in this category will be uncategorised.">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-delete">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">No categories yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top:1rem;">{{ $categories->links() }}</div>

    <script>
        document.querySelectorAll('form[data-confirm]').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                if (!confirm(form.dataset.confirm)) { e.preventDefault(); }
            });
        });
    </script>
</body>

</html>