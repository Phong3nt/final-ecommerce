@extends('layouts.app')

@section('title', 'Shop — Products')

@push('styles')
    <style>
        body {
            background-color: #f8f9fa;
        }

        /* ── Bento Grid ─────────────────────────────────────────────── */
        .bento-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            grid-auto-rows: auto;
            gap: 1.25rem;
        }

        .bento-card {
            display: flex;
            flex-direction: column;
        }

        .bento-featured {
            grid-column: span 2;
            grid-row: span 2;
        }

        /* IMP-037(g): uniform 220px fixed height for all product card images */
        .bento-card .card-img-top {
            height: 220px;
            object-fit: cover;
        }

        .bento-card .card {
            height: 100%;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .bento-card .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.1) !important;
        }

        /* ── Sidebar ────────────────────────────────────────────────── */
        .filter-sidebar .card {
            position: sticky;
            top: 1.5rem;
        }

        /* ── Star rating ────────────────────────────────────────────── */
        .stars {
            color: #f5a623;
            font-size: 0.85rem;
            letter-spacing: 1px;
        }

        /* ── Skeleton Shimmer ───────────────────────────────────────── */
        @keyframes skel-shimmer {
            0% {
                background-position: -600px 0;
            }

            100% {
                background-position: 600px 0;
            }
        }

        .skel-img {
            background: linear-gradient(90deg, #e2e5e7 25%, #f0f2f4 50%, #e2e5e7 75%);
            background-size: 600px 100%;
            animation: skel-shimmer 1.4s ease infinite;
        }

        /* ── Responsive breakpoints ─────────────────────────────────── */
        @media (max-width: 991.98px) {
            .bento-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .bento-featured {
                grid-column: span 2;
                grid-row: span 1;
            }

            .bento-card .card-img-top {
                height: 220px;
            }
        }

        @media (max-width: 575.98px) {
            .bento-grid {
                grid-template-columns: 1fr;
            }

            .bento-featured {
                grid-column: span 1;
            }
        }
    </style>
@endpush

@section('content')
    <!-- ── Main layout ─────────────────────────────────────────────── -->
    <div class="container-xl py-4">

        <!-- Page header -->
        <div class="d-flex align-items-baseline justify-content-between mb-4">
            <h1 class="h3 fw-bold mb-0">All Products</h1>
            @if (!$products->isEmpty())
                <span class="text-muted small">{{ $products->total() }}
                    product{{ $products->total() !== 1 ? 's' : '' }}</span>
            @endif
        </div>

        <div class="row g-4">

            <!-- ── Filter sidebar ─────────────────────────────────── -->
            <aside class="col-lg-3 filter-sidebar">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white fw-semibold border-bottom">
                        <i class="bi bi-funnel me-1"></i> Filters
                    </div>
                    <div class="card-body">
                        <form action="{{ route('products.index') }}" method="GET" id="filter-form">

                            <div class="mb-3">
                                <label for="category"
                                    class="form-label small fw-semibold text-uppercase text-muted">Category</label>
                                <select name="category" id="category" class="form-select form-select-sm">
                                    <option value="">All Categories</option>
                                    @foreach ($categories as $cat)
                                        <option value="{{ $cat->id }}" {{ (string) ($filters['category'] ?? '') === (string) $cat->id ? 'selected' : '' }}>
                                            {{ $cat->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-uppercase text-muted">Price
                                    Range</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">$</span>
                                    <input type="number" name="min_price" id="min_price" class="form-control"
                                        placeholder="Min" step="0.01" min="0" value="{{ $filters['min_price'] ?? '' }}">
                                    <span class="input-group-text">–</span>
                                    <input type="number" name="max_price" id="max_price" class="form-control"
                                        placeholder="Max" step="0.01" min="0" value="{{ $filters['max_price'] ?? '' }}">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="min_rating" class="form-label small fw-semibold text-uppercase text-muted">Min
                                    Rating</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" name="min_rating" id="min_rating" class="form-control"
                                        placeholder="0 – 5" step="0.1" min="0" max="5"
                                        value="{{ $filters['min_rating'] ?? '' }}">
                                    <span class="input-group-text">★</span>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="sort" class="form-label small fw-semibold text-uppercase text-muted">Sort
                                    By</label>
                                <select name="sort" id="sort" class="form-select form-select-sm">
                                    <option value="newest" {{ ($filters['sort'] ?? 'newest') === 'newest' ? 'selected' : '' }}>Newest</option>
                                    <option value="oldest" {{ ($filters['sort'] ?? '') === 'oldest' ? 'selected' : '' }}>
                                        Oldest</option>
                                    <option value="price_asc" {{ ($filters['sort'] ?? '') === 'price_asc' ? 'selected' : '' }}>Price: Low to High</option>
                                    <option value="price_desc" {{ ($filters['sort'] ?? '') === 'price_desc' ? 'selected' : '' }}>Price: High to Low</option>
                                    <option value="rating" {{ ($filters['sort'] ?? '') === 'rating' ? 'selected' : '' }}>
                                        Top Rated</option>
                                </select>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-dark btn-sm">Apply Filters</button>
                                <a href="{{ route('products.index') }}" class="btn btn-outline-secondary btn-sm">Clear
                                    Filters</a>
                            </div>

                        </form>
                    </div>
                </div>
            </aside>

            <!-- ── Product bento grid ─────────────────────────────── -->
            <main class="col-lg-9">
                @if ($products->isEmpty())
                    <div class="alert alert-info d-flex align-items-center gap-2" role="alert">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor"
                            class="bi bi-info-circle-fill flex-shrink-0" viewBox="0 0 16 16">
                            <path
                                d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z" />
                        </svg>
                        No products available.
                    </div>
                @else
                    <div class="bento-grid">
                        @foreach ($products as $product)
                            <div class="bento-card {{ $loop->first ? 'bento-featured' : '' }}">
                                <div class="card border-0 shadow-sm h-100">

                                    @if ($product->image)
                                        <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}"
                                            class="card-img-top skel-img" loading="lazy" onload="this.classList.remove('skel-img')">
                                    @else
                                        <div class="card-img-top bg-light d-flex align-items-center justify-content-center
                                                                                                {{ $loop->first ? '' : '' }}"
                                            style="height: {{ $loop->first ? '340px' : '180px' }};">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="#ced4da"
                                                viewBox="0 0 16 16">
                                                <path d="M6.002 5.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z" />
                                                <path
                                                    d="M2.002 1a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2h-12zm12 1a1 1 0 0 1 1 1v6.5l-3.777-1.947a.5.5 0 0 0-.577.093l-3.71 3.71-2.66-1.772a.5.5 0 0 0-.63.062L1.002 12V3a1 1 0 0 1 1-1h12z" />
                                            </svg>
                                        </div>
                                    @endif

                                    <div class="card-body d-flex flex-column">

                                        @if ($product->category)
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary mb-2 align-self-start">
                                                {{ $product->category->name }}
                                            </span>
                                        @endif

                                        <h2 class="card-title {{ $loop->first ? 'h5' : 'h6' }} mb-1">
                                            @if ($product->slug)
                                                <a href="{{ route('products.show', $product->slug) }}"
                                                    class="text-decoration-none text-dark stretched-link">
                                                    {{ $product->name }}
                                                </a>
                                            @else
                                                {{ $product->name }}
                                            @endif
                                        </h2>

                                        @if ($product->rating !== null)
                                            <div class="stars mb-1"
                                                aria-label="Rating: {{ number_format($product->rating, 1) }} out of 5">
                                                @php
                                                    $full = (int) floor($product->rating);
                                                    $half = ($product->rating - $full) >= 0.5 ? 1 : 0;
                                                    $empty = 5 - $full - $half;
                                                @endphp
                                                {{ str_repeat('★', $full) }}{{ $half ? '½' : '' }}{{ str_repeat('☆', $empty) }}
                                                <small class="text-muted ms-1">{{ number_format($product->rating, 1) }}</small>
                                            </div>
                                        @endif

                                        <div class="mt-auto d-flex align-items-center justify-content-between pt-2">
                                            <span class="fw-bold {{ $loop->first ? 'fs-5' : '' }} text-dark">
                                                ${{ number_format($product->price, 2) }}
                                            </span>
                                            @if ($product->stock > 0)
                                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                    In Stock
                                                </span>
                                            @else
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                                    Out of Stock
                                                </span>
                                            @endif
                                        </div>

                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-4 d-flex justify-content-center">
                        {{ $products->links() }}
                    </div>
                @endif
            </main>

        </div>
    </div>

    <!-- IMP-005: Off-canvas cart drawer ──────────────────────────────── -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="cartDrawer" aria-labelledby="cartDrawerLabel"
        style="width:min(380px,100vw);">
        <div class="offcanvas-header border-bottom py-3">
            <h5 class="offcanvas-title fw-bold d-flex align-items-center gap-2 mb-0" id="cartDrawerLabel">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"
                    aria-hidden="true">
                    <path
                        d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .49.598l-1 5a.5.5 0 0 1-.465.401l-9.397.948L4.043 12H13a.5.5 0 0 1 0 1H4a.5.5 0 0 1-.465-.686L5.28 8.643 3.055 3.75 2.61 2H.5a.5.5 0 0 1-.5-.5zM3.226 4l.893 4.462 9.144-.925.79-3.537H3.226zM5.5 13a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3zm-2 1.5a2 2 0 1 1 4 0 2 2 0 0 1-4 0zm9-2a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3zm-2 1.5a2 2 0 1 1 4 0 2 2 0 0 1-4 0z" />
                </svg>
                Your Cart
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body p-0 d-flex flex-column" style="min-height:0;">
            @php
                $__dc = session('cart', []);
                $__ds = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $__dc));
            @endphp
            {{-- Empty state --}}
            <div id="drawer-empty-state"
                class="{{ empty($__dc) ? '' : 'd-none' }} flex-grow-1 d-flex flex-column align-items-center justify-content-center text-center px-4 py-5">
                <svg xmlns="http://www.w3.org/2000/svg" width="52" height="52" fill="#ced4da" class="mb-3"
                    viewBox="0 0 16 16" aria-hidden="true">
                    <path
                        d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .49.598l-1 5a.5.5 0 0 1-.465.401l-9.397.948L4.043 12H13a.5.5 0 0 1 0 1H4a.5.5 0 0 1-.465-.686L5.28 8.643 3.055 3.75 2.61 2H.5a.5.5 0 0 1-.5-.5zM3.226 4l.893 4.462 9.144-.925.79-3.537H3.226zM5.5 13a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3zm-2 1.5a2 2 0 1 1 4 0 2 2 0 0 1-4 0zm9-2a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3zm-2 1.5a2 2 0 1 1 4 0 2 2 0 0 1-4 0z" />
                </svg>
                <p class="fw-semibold text-dark mb-1">Your cart is empty</p>
                <p class="text-muted small mb-3">Add items to get started.</p>
                <button type="button" class="btn btn-primary btn-sm" data-bs-dismiss="offcanvas">Continue
                    Shopping</button>
            </div>
            {{-- Has items --}}
            <div id="drawer-has-items" class="{{ empty($__dc) ? 'd-none' : '' }} d-flex flex-column flex-grow-1"
                style="min-height:0;">
                <div class="flex-grow-1 overflow-auto px-3 pt-3" id="drawer-items-list">
                    @foreach($__dc as $__item)
                        <div class="d-flex align-items-start gap-3 pb-3 mb-3 border-bottom">
                            <div class="flex-grow-1" style="min-width:0;">
                                <div class="fw-semibold lh-sm mb-1" style="font-size:.88rem;">
                                    @if($__item['slug'])
                                        <a href="{{ route('products.show', $__item['slug']) }}"
                                            class="text-dark text-decoration-none">
                                            {{ $__item['name'] }}
                                        </a>
                                    @else
                                        {{ $__item['name'] }}
                                    @endif
                                </div>
                                <div class="text-muted" style="font-size:.78rem;">
                                    ${{ number_format($__item['price'], 2) }} &times; {{ $__item['quantity'] }}
                                </div>
                            </div>
                            <span class="fw-semibold text-nowrap" style="font-size:.88rem;">
                                ${{ number_format($__item['price'] * $__item['quantity'], 2) }}
                            </span>
                        </div>
                    @endforeach
                </div>
                <div class="p-3 border-top" id="drawer-footer">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted small fw-semibold text-uppercase"
                            style="letter-spacing:.04em;">Subtotal</span>
                        <span class="fw-bold fs-6" id="drawer-subtotal">${{ number_format($__ds, 2) }}</span>
                    </div>
                    <a href="{{ route('cart.index') }}" class="btn btn-outline-dark w-100 mb-2 btn-sm">View Full
                        Cart</a>
                    @auth
                        <a href="{{ route('checkout.index') }}" class="btn btn-dark w-100 btn-sm">Checkout &rarr;</a>
                    @else
                        <a href="{{ route('checkout.guest.index') }}" class="btn btn-dark w-100 btn-sm mb-1">Checkout as
                            Guest</a>
                        <a href="{{ route('login') }}" class="btn btn-outline-secondary w-100 btn-sm">Sign In to
                            Checkout</a>
                    @endauth
                </div>
            </div>
        </div>
    </div>

@endsection