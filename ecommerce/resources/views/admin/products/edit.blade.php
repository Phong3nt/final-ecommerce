<!DOCTYPE html>
<html>

<head>
    <title>Admin — Edit Product</title>
    <style>
        body {
            font-family: sans-serif;
            margin: 2rem;
            background: #f5f5f5;
        }

        h1 {
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        label {
            display: block;
            font-weight: 600;
            margin-bottom: .25rem;
        }

        input[type="text"],
        input[type="number"],
        textarea,
        select {
            width: 100%;
            max-width: 540px;
            padding: .5rem .75rem;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 1rem;
            box-sizing: border-box;
        }

        textarea {
            height: 120px;
            resize: vertical;
        }

        .error {
            color: #dc3545;
            font-size: .875rem;
            margin-top: .25rem;
        }

        .btn {
            padding: .5rem 1.25rem;
            background: #0d6efd;
            color: #fff;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            cursor: pointer;
        }

        .btn:hover {
            background: #0b5ed7;
        }

        a.back {
            color: #6c757d;
            text-decoration: none;
            margin-left: 1rem;
        }

        .slug-hint {
            font-size: .8rem;
            color: #6c757d;
            margin-top: .2rem;
        }
    </style>
</head>

<body>
    <h1>Edit Product: {{ $product->name }}</h1>

    <form method="POST" action="{{ route('admin.products.update', $product) }}" enctype="multipart/form-data">
        @csrf
        @method('PATCH')

        <div class="form-group">
            <label for="name">Name <span style="color:red">*</span></label>
            <input type="text" id="name" name="name" value="{{ old('name', $product->name) }}" required>
            <div class="slug-hint">Current slug: <strong>{{ $product->slug }}</strong> (auto-updated if name changes)
            </div>
            @error('name')<div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description">{{ old('description', $product->description) }}</textarea>
            @error('description')<div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="form-group">
            <label for="price">Price ($) <span style="color:red">*</span></label>
            <input type="number" id="price" name="price" value="{{ old('price', $product->price) }}" step="0.01"
                min="0.01" required>
            @error('price')<div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="form-group">
            <label for="stock">Stock <span style="color:red">*</span></label>
            <input type="number" id="stock" name="stock" value="{{ old('stock', $product->stock) }}" min="0" required>
            @error('stock')<div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="form-group">
            <label for="category_id">Category</label>
            <select id="category_id" name="category_id">
                <option value="">— None —</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ old('category_id', $product->category_id) == $cat->id ? 'selected' : '' }}>
                        {{ $cat->name }}
                    </option>
                @endforeach
            </select>
            @error('category_id')<div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="form-group">
            <label for="status">Status <span style="color:red">*</span></label>
            <select id="status" name="status">
                <option value="published" {{ old('status', $product->status) === 'published' ? 'selected' : '' }}>
                    Published</option>
                <option value="draft" {{ old('status', $product->status) === 'draft' ? 'selected' : '' }}>Draft</option>
            </select>
            @error('status')<div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="form-group">
            <label for="images">Add more images (multi-upload)</label>
            <input type="file" id="images" name="images[]" multiple accept="image/*">
            @error('images')<div class="error">{{ $message }}</div>@enderror
            @error('images.*')<div class="error">{{ $message }}</div>@enderror
            @if($product->images)
                <div class="slug-hint">Currently {{ count($product->images) }} image(s) stored.</div>
            @endif
        </div>

        <div class="form-group" style="margin-top:1.5rem;">
            <button type="submit" class="btn">Save Changes</button>
            <a href="{{ route('admin.products.index') }}" class="back">Cancel</a>
        </div>
    </form>
</body>

</html>