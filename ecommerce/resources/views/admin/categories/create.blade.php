<!DOCTYPE html>
<html>

<head>
    <title>Admin — New Category</title>
    <style>
        body { font-family: sans-serif; margin: 2rem; background: #f5f5f5; }
        h1 { margin-bottom: 1rem; }
        .form-group { margin-bottom: 1rem; }
        label { display: block; font-weight: 600; margin-bottom: .25rem; }
        input, select { width: 100%; max-width: 480px; padding: .5rem; border: 1px solid #ccc; border-radius: 4px; }
        .error { color: #dc3545; font-size: .875rem; margin-top: .25rem; }
        button[type=submit] {
            padding: .5rem 1.5rem; background: #198754; color: #fff;
            border: none; border-radius: 4px; cursor: pointer; font-size: 1rem;
        }
        a { color: #0d6efd; }
    </style>
</head>

<body>
    <h1>New Category</h1>

    <form method="POST" action="{{ route('admin.categories.store') }}">
        @csrf

        <div class="form-group">
            <label for="name">Name *</label>
            <input type="text" id="name" name="name" value="{{ old('name') }}" required>
            @error('name')<div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="form-group">
            <label for="parent_id">Parent Category (optional)</label>
            <select id="parent_id" name="parent_id">
                <option value="">— None —</option>
                @foreach($parents as $parent)
                    <option value="{{ $parent->id }}" {{ old('parent_id') == $parent->id ? 'selected' : '' }}>
                        {{ $parent->name }}
                    </option>
                @endforeach
            </select>
            @error('parent_id')<div class="error">{{ $message }}</div>@enderror
        </div>

        <button type="submit">Create Category</button>
        <a href="{{ route('admin.categories.index') }}" style="margin-left:1rem;">Cancel</a>
    </form>
</body>

</html>
