@extends('layouts.app')

@section('title', 'Search Results — {{ $q }}')

@section('content')
    <div x-data x-init="$el.classList.add('fade-in')">

        {{-- Search bar --}}
        <form action="{{ route('products.search') }}" method="GET" class="mb-4">
            <div class="input-group input-group-lg shadow-sm">
                <input type="text" name="q" value="{{ $q }}" class="form-control border-end-0"
                    placeholder="Search products…" required>
                <button class="btn btn-primary px-4" type="submit">
                    <i class="bi bi-search"></i>
                </button>
            </div>
        </form>

        @if ($results->isEmpty())
            {{-- Empty state --}}
            <div class="text-center py-5">
                <i class="bi bi-search fs-1 text-muted mb-3 d-block"></i>
                <h2 class="h5 fw-semibold">No products found for &ldquo;{{ e($q) }}&rdquo;</h2>
                <p class="text-muted">Try different keywords or browse our catalogue.</p>
                <a href="{{ route('products.index') }}" class="btn btn-primary mt-2">
                    <i class="bi bi-grid me-1"></i> Browse All Products
                </a>
            </div>
        @else
            <div class="d-flex justify-content-between align-items-center mb-3">
                <p class="text-muted mb-0">
                    Showing <strong>{{ $results->total() }}</strong> result(s) for
                    &ldquo;<strong>{{ e($q) }}</strong>&rdquo;
                </p>
            </div>

            <div class="row g-4">
                @foreach ($results as $product)
                    <div class="col-6 col-md-4 col-lg-3">
                        <a href="{{ route('products.show', $product->slug) }}"
                            class="card card-hover shadow-sm border-0 h-100 text-decoration-none text-reset">
                            <div class="overflow-hidden rounded-top-3" style="height:220px;">
                                @if ($product->image)
                                    <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" class="w-100 h-100"
                                        style="object-fit:cover;">
                                @else
                                    <div class="w-100 h-100 bg-light d-flex align-items-center
                                                                            justify-content-center">
                                        <i class="bi bi-box-seam fs-1 text-muted"></i>
                                    </div>
                                @endif
                            </div>
                            <div class="card-body p-3">
                                <h2 class="h6 fw-semibold mb-1">{{ $product->name }}</h2>
                                <p class="fw-bold text-primary mb-1">${{ number_format($product->price, 2) }}</p>
                                @if ($product->stock > 0)
                                    <span class="badge bg-success bg-opacity-10 text-success">In Stock</span>
                                @else
                                    <span class="badge bg-danger bg-opacity-10 text-danger">Out of Stock</span>
                                @endif
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>

            <div class="mt-4">
                {{ $results->appends(['q' => $q])->links() }}
            </div>
        @endif
    </div>
@endsection