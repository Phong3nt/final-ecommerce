@extends('layouts.app')

@section('title', 'Welcome - ShopName')

@push('styles')
    <style>
        /* IMP-028: Homepage hero & sections */
        .imp028-hero {
            background: linear-gradient(135deg, #4f46e5 0%, #0ea5e9 100%);
            border-radius: 1rem;
            padding: 5rem 2rem;
            color: #fff;
        }

        .imp028-hero .hero-title {
            font-size: clamp(1.75rem, 4vw, 3rem);
            font-weight: 700;
            line-height: 1.15;
        }

        .imp028-hero .hero-sub {
            font-size: 1.125rem;
            opacity: 0.9;
        }

        .imp028-feature-icon {
            width: 56px;
            height: 56px;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .imp028-category-card {
            border-radius: 1rem;
            overflow: hidden;
            min-height: 160px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform .15s ease, box-shadow .15s ease;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
        }

        .imp028-category-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 32px rgba(0, 0, 0, .12) !important;
            text-decoration: none;
            color: inherit;
        }

        .imp028-promo {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 100%);
            border-radius: 1rem;
            color: #fff;
        }

        .imp028-product-chip {
            font-size: .75rem;
            font-weight: 600;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .imp028-price-tag {
            font-size: 1.25rem;
            font-weight: 700;
            color: #0f172a;
        }

        /* IMP-037: Fixed-height product image thumbnail */
        .imp028-product-img {
            height: 180px;
            overflow: hidden;
        }

        .imp028-product-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
    </style>
@endpush

@section('content')
    <div x-data x-init="$el.classList.add('fade-in')">

        {{-- Hero Banner --}}
        <div class="imp028-hero text-center mb-5">
            <p class="text-label mb-2" style="color:rgba(255,255,255,.7); letter-spacing:.1em;">
                NEW ARRIVALS EVERY WEEK
            </p>
            <h1 class="hero-title mb-3">
                Your One-Stop Shop<br>for Everything You Need
            </h1>
            <p class="hero-sub mb-4">
                Discover thousands of products at unbeatable prices. Quality, convenience, and great deals all in one place.
            </p>
            <div class="d-flex justify-content-center gap-3 flex-wrap">
                <a href="{{ route('products.index') }}" class="btn btn-light btn-lg fw-semibold px-4 shadow-sm">
                    <i class="bi bi-grid me-2"></i>Shop Now
                </a>
                @guest
                    <a href="{{ route('register') }}" class="btn btn-outline-light btn-lg fw-semibold px-4">
                        <i class="bi bi-person-plus me-2"></i>Create Free Account
                    </a>
                @endguest
                @auth
                    <a href="{{ route('dashboard') }}" class="btn btn-outline-light btn-lg fw-semibold px-4">
                        <i class="bi bi-grid me-2"></i>My Dashboard
                    </a>
                @endauth
            </div>
        </div>

        {{-- Features / Trust Badges --}}
        <div class="row g-3 mb-5">
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-start gap-3 p-3">
                        <div class="imp028-feature-icon bg-primary bg-opacity-10">
                            <i class="bi bi-truck text-primary"></i>
                        </div>
                        <div>
                            <div class="fw-semibold small">Free Shipping</div>
                            <div class="text-muted" style="font-size:.8125rem;">On orders over $50</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-start gap-3 p-3">
                        <div class="imp028-feature-icon bg-success bg-opacity-10">
                            <i class="bi bi-shield-check text-success"></i>
                        </div>
                        <div>
                            <div class="fw-semibold small">Secure Payment</div>
                            <div class="text-muted" style="font-size:.8125rem;">SSL encrypted checkout</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-start gap-3 p-3">
                        <div class="imp028-feature-icon bg-warning bg-opacity-10">
                            <i class="bi bi-arrow-repeat text-warning"></i>
                        </div>
                        <div>
                            <div class="fw-semibold small">Easy Returns</div>
                            <div class="text-muted" style="font-size:.8125rem;">30-day return policy</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-start gap-3 p-3">
                        <div class="imp028-feature-icon bg-info bg-opacity-10">
                            <i class="bi bi-headset text-info"></i>
                        </div>
                        <div>
                            <div class="fw-semibold small">24/7 Support</div>
                            <div class="text-muted" style="font-size:.8125rem;">Always here to help</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Browse Categories — IMP-037(f): dynamic from DB --}}
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h5 class="fw-bold mb-0">Browse by Category</h5>
            <a href="{{ route('products.index') }}" class="btn btn-outline-secondary btn-sm">
                View all <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>
        <div class="row g-3 mb-5">
            @php
                $categoryIcons = [
                    'Electronics'           => ['icon' => 'bi-cpu',              'bg' => 'bg-primary',   'text' => 'text-primary'],
                    'Laptops'               => ['icon' => 'bi-laptop',           'bg' => 'bg-info',      'text' => 'text-info'],
                    'Smartphones'           => ['icon' => 'bi-phone',            'bg' => 'bg-success',   'text' => 'text-success'],
                    'Smartwatches'          => ['icon' => 'bi-smartwatch',       'bg' => 'bg-warning',   'text' => 'text-warning'],
                    'Tablets'               => ['icon' => 'bi-tablet',           'bg' => 'bg-danger',    'text' => 'text-danger'],
                    'Headphones & Headsets' => ['icon' => 'bi-headphones',       'bg' => 'bg-secondary', 'text' => 'text-secondary'],
                    'Battery Chargers'      => ['icon' => 'bi-battery-charging', 'bg' => 'bg-primary',   'text' => 'text-primary'],
                ];
            @endphp
            @forelse($categories as $category)
                @php
                    $meta = $categoryIcons[$category->name] ?? ['icon' => 'bi-tag', 'bg' => 'bg-primary', 'text' => 'text-primary'];
                @endphp
                <div class="col-6 col-md-3">
                    <a href="{{ route('products.index', ['category' => $category->id]) }}"
                        class="imp028-category-card card border-0 shadow-sm">
                        <div class="text-center p-4">
                            <div class="imp028-feature-icon {{ $meta['bg'] }} bg-opacity-10 mx-auto mb-3">
                                <i class="bi {{ $meta['icon'] }} {{ $meta['text'] }} fs-3"></i>
                            </div>
                            <div class="fw-semibold small">{{ $category->name }}</div>
                        </div>
                    </a>
                </div>
            @empty
                <div class="col-12">
                    <p class="text-muted text-center py-3">No categories available yet.</p>
                </div>
            @endforelse
        </div>

        {{-- Featured Products — IMP-037(e): dynamic from DB --}}
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h5 class="fw-bold mb-0">Featured Products</h5>
            <a href="{{ route('products.index') }}" class="btn btn-outline-secondary btn-sm">
                Explore catalog <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>
        <div class="row g-3 mb-5">
            @forelse($featuredProducts as $product)
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="card border-0 shadow-sm h-100 card-hover">
                        <div class="imp028-product-img">
                            <img src="{{ $product->image }}" alt="{{ e($product->name) }}">
                        </div>
                        <div class="card-body p-3 d-flex flex-column">
                            <span class="imp028-product-chip text-primary mb-2">
                                {{ $product->category->name ?? '&mdash;' }}
                            </span>
                            <h6 class="fw-semibold mb-2">{{ $product->name }}</h6>
                            <div class="d-flex align-items-center justify-content-between mt-auto">
                                <span class="imp028-price-tag">${{ number_format($product->price, 2) }}</span>
                                <a href="{{ route('products.show', ['product' => $product->slug]) }}"
                                    class="btn btn-primary btn-sm">View</a>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <p class="text-muted text-center py-3">No featured products available yet.</p>
                </div>
            @endforelse
        </div>

        {{-- Why Shop With Us --}}
        <div class="card border-0 shadow-sm rounded-3 mb-5">
            <div class="card-body p-4 p-md-5">
                <h5 class="fw-bold text-center mb-4">Why Shop With Us?</h5>
                <div class="row g-4 text-center">
                    <div class="col-md-4">
                        <i class="bi bi-star-fill text-warning fs-2 mb-2"></i>
                        <h6 class="fw-semibold">Top Rated Products</h6>
                        <p class="text-muted small mb-0">Every product is reviewed by real customers. Only the best make our
                            catalog.</p>
                    </div>
                    <div class="col-md-4">
                        <i class="bi bi-lightning-charge-fill text-primary fs-2 mb-2"></i>
                        <h6 class="fw-semibold">Fast Delivery</h6>
                        <p class="text-muted small mb-0">Order today, receive in days. Express shipping available at
                            checkout.</p>
                    </div>
                    <div class="col-md-4">
                        <i class="bi bi-patch-check-fill text-success fs-2 mb-2"></i>
                        <h6 class="fw-semibold">Quality Guaranteed</h6>
                        <p class="text-muted small mb-0">Not happy? We will make it right. Our 30-day return policy has you
                            covered.</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Promotional CTA --}}
        <div class="imp028-promo p-4 p-md-5 mb-4 text-center">
            <p class="text-label mb-2" style="color:rgba(255,255,255,.6);">LIMITED TIME OFFER</p>
            <h3 class="fw-bold mb-2">Start Shopping Today</h3>
            <p class="mb-4 opacity-75">Join thousands of happy customers. Create your free account and get access to
                exclusive deals.</p>
            <div class="d-flex justify-content-center gap-3 flex-wrap">
                <a href="{{ route('products.index') }}" class="btn btn-primary btn-lg fw-semibold px-4">
                    <i class="bi bi-bag me-2"></i>Browse Products
                </a>
                @guest
                    <a href="{{ route('register') }}" class="btn btn-outline-light btn-lg fw-semibold px-4">
                        <i class="bi bi-person-plus me-2"></i>Sign Up Free
                    </a>
                @endguest
            </div>
        </div>

    </div>
@endsection