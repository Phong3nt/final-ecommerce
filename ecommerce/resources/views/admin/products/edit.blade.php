@extends('layouts.admin')

@section('title', 'Admin — Edit Product')
@section('page-title', 'Edit Product')

@section('content')
<div x-data x-init="$el.classList.add('fade-in')">
    <div class="mb-3">
        <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to Products
        </a>
    </div>

    <div class="card shadow-sm border-0 rounded-3" style="max-width:600px;">
        <div class="card-body">
            <h5 class="card-title mb-4">Edit Product: {{ $product->name }}</h5>

            <form method="POST" action="{{ route('admin.products.update', $product) }}" enctype="multipart/form-data">
                @csrf
                @method('PATCH')

                <div class="mb-3">
                    <label for="name" class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                    <input type="text" id="name" name="name" value="{{ old('name', $product->name) }}"
                        class="form-control @error('name') is-invalid @enderror" required>
                    <div class="form-text">Current slug: <strong>{{ $product->slug }}</strong> (auto-updated if name changes)</div>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label fw-semibold">Description</label>
                    <textarea id="description" name="description" rows="4"
                        class="form-control @error('description') is-invalid @enderror">{{ old('description', $product->description) }}</textarea>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label for="price" class="form-label fw-semibold">Price ($) <span class="text-danger">*</span></label>
                    <input type="number" id="price" name="price" value="{{ old('price', $product->price) }}" step="0.01" min="0.01"
                        class="form-control @error('price') is-invalid @enderror" required>
                    @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label for="stock" class="form-label fw-semibold">Stock <span class="text-danger">*</span></label>
                    <input type="number" id="stock" name="stock" value="{{ old('stock', $product->stock) }}" min="0"
                        class="form-control @error('stock') is-invalid @enderror" required>
                    @error('stock')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label for="low_stock_threshold" class="form-label fw-semibold">Low Stock Threshold <span class="text-muted fw-normal">(optional)</span></label>
                    <input type="number" id="low_stock_threshold" name="low_stock_threshold"
                        value="{{ old('low_stock_threshold', $product->low_stock_threshold) }}" min="0"
                        class="form-control @error('low_stock_threshold') is-invalid @enderror"
                        placeholder="Leave blank to disable">
                    @error('low_stock_threshold')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label for="category_id" class="form-label fw-semibold">Category</label>
                    <select id="category_id" name="category_id" class="form-select @error('category_id') is-invalid @enderror">
                        <option value="">— None —</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id', $product->category_id) == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label for="status" class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                    <select id="status" name="status" class="form-select @error('status') is-invalid @enderror">
                        <option value="published" {{ old('status', $product->status) === 'published' ? 'selected' : '' }}>Published</option>
                        <option value="draft" {{ old('status', $product->status) === 'draft' ? 'selected' : '' }}>Draft</option>
                    </select>
                    @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-4">
                    <label for="images" class="form-label fw-semibold">Add more images (multi-upload)</label>
                    <input type="file" id="images" name="images[]" multiple accept="image/*"
                        class="form-control @error('images') is-invalid @enderror">
                    @error('images')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @error('images.*')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @if($product->images)
                        <div class="form-text">Currently {{ count($product->images) }} image(s) stored.
                            <a href="{{ route('admin.products.images', $product) }}">Manage Images →</a>
                        </div>
                    @endif
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection