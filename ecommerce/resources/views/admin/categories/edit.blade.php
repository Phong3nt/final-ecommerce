@extends('layouts.admin')

@section('title', 'Admin — Edit Category')
@section('page-title', 'Edit Category')

@section('content')
    <div x-data x-init="$el.classList.add('fade-in')">
        <div class="mb-3">
            <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Back to Categories
            </a>
        </div>

        <div class="card shadow-sm border-0 rounded-3" style="max-width:540px;">
            <div class="card-body">
                <h5 class="card-title mb-4">Edit Category: {{ $category->name }}</h5>

                <form method="POST" action="{{ route('admin.categories.update', $category) }}">
                    @csrf
                    @method('PATCH')

                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                        <input type="text" id="name" name="name" value="{{ old('name', $category->name) }}"
                            class="form-control @error('name') is-invalid @enderror" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-4">
                        <label for="parent_id" class="form-label fw-semibold">Parent Category <span
                                class="text-muted fw-normal">(optional)</span></label>
                        <select id="parent_id" name="parent_id"
                            class="form-select @error('parent_id') is-invalid @enderror">
                            <option value="">— None —</option>
                            @foreach($parents as $parent)
                                <option value="{{ $parent->id }}" {{ old('parent_id', $category->parent_id) == $parent->id ? 'selected' : '' }}>
                                    {{ $parent->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('parent_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                        <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection