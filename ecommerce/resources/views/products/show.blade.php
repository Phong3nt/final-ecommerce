@extends('layouts.app')

@section('title', $product->name . ' — ShopName')

{{-- Toasts are provided by layouts.app via @include('partials.toast') --}}

    @push('styles')
        <style>
            /* IMP-007: Add-to-cart micro-interactions */
            .atc-spinner {
                display: inline-block;
                width: 13px;
                height: 13px;
                border: 2px solid rgba(255, 255, 255, .35);
                border-top-color: #fff;
                border-radius: 50%;
                animation: imp007spin .55s linear infinite;
                vertical-align: text-bottom;
            }

            @keyframes imp007spin {
                to {
                    transform: rotate(360deg);
                }
            }

            .btn-atc.atc-success {
                background: #198754;
                border-color: #198754;
            }

            .btn-atc.atc-error {
                animation: imp007shake .35s ease;
                background: #dc3545;
                border-color: #dc3545;
            }

            @keyframes imp007shake {

                0%,
                100% {
                    transform: translateX(0);
                }

                25%,
                75% {
                    transform: translateX(-4px);
                }

                50% {
                    transform: translateX(4px);
                }
            }

            /* IMP-010: Image gallery / lightbox */
            .imp010-main-wrapper {
                position: relative;
                cursor: zoom-in;
                overflow: hidden;
                border-radius: .5rem;
                background: #f8f9fa;
            }

            .imp010-main-img {
                width: 100%;
                max-height: 420px;
                object-fit: contain;
                display: block;
                transition: opacity .15s ease;
            }

            .imp010-main-img:hover {
                opacity: .88;
            }

            .imp010-thumbs {
                display: flex;
                gap: .5rem;
                flex-wrap: wrap;
                margin-top: .75rem;
            }

            .imp010-thumb {
                width: 64px;
                height: 64px;
                object-fit: cover;
                border-radius: .375rem;
                border: 2px solid #dee2e6;
                cursor: pointer;
                transition: border-color .15s ease;
            }

            .imp010-thumb:hover,
            .imp010-thumb-active {
                border-color: #0d6efd;
            }

            .imp010-lightbox-overlay {
                position: fixed;
                inset: 0;
                background: rgba(0, 0, 0, .85);
                z-index: 1055;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .imp010-lightbox-content {
                position: relative;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .imp010-zoom-wrap {
                overflow: hidden;
                max-width: 90vw;
                max-height: 85vh;
                cursor: zoom-in;
            }

            .imp010-zoom-wrap.imp010-zoomed {
                cursor: zoom-out;
            }

            .imp010-lightbox-img {
                max-width: 90vw;
                max-height: 85vh;
                display: block;
                transition: transform .25s ease;
                transform-origin: center center;
            }

            .imp010-lightbox-img.imp010-img-zoomed {
                transform: scale(2);
            }

            .imp010-close-btn {
                position: absolute;
                top: -2.5rem;
                right: 0;
                background: rgba(255, 255, 255, .15);
                border: none;
                color: #fff;
                border-radius: 50%;
                width: 2rem;
                height: 2rem;
                font-size: 1.2rem;
                line-height: 1;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .imp010-close-btn:hover {
                background: rgba(255, 255, 255, .3);
            }

            /* IMP-012: Star rating */
            .imp012-star {
                color: #d1d5db;
                line-height: 1;
                display: inline-block;
                transition: color .1s, transform .1s;
            }

            .imp012-star--filled {
                color: #f59e0b;
            }

            .imp012-star-input .imp012-star {
                background: none;
                border: none;
                padding: 0 .1rem;
                cursor: pointer;
                font-size: 1.6rem;
            }

            .imp012-star-input .imp012-star:hover,
            .imp012-star-input .imp012-star:focus {
                transform: scale(1.15);
                outline: none;
            }

            /* Review card hover */
            .review-card {
                transition: box-shadow .15s ease;
            }

            .review-card:hover {
                box-shadow: 0 4px 16px rgba(0, 0, 0, .07) !important;
            }
        </style>
    @endpush

    @section('content')

        {{-- Breadcrumb ─────────────────────────────────────────────────────── --}}
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('products.index') }}" class="text-decoration-none">Shop</a></li>
                @if ($product->category)
                    <li class="breadcrumb-item">
                        <a href="{{ route('products.index', ['category' => $product->category->id]) }}"
                            class="text-decoration-none">{{ $product->category->name }}</a>
                    </li>
                @endif
                <li class="breadcrumb-item active" aria-current="page">{{ $product->name }}</li>
            </ol>
        </nav>

        {{-- Product Detail ──────────────────────────────────────────────────── --}}
        <div class="row g-4 mb-5">

            {{-- IMP-010: Image gallery (left column) ──────────────────────── --}}
            <div class="col-lg-5" x-data="imp010Lightbox({
                     mainImage: @json($product->imageUrl),
                     images:    @json($product->imagesUrls),
                     alt:       @json($product->name)
                 })">

                @if ($product->image)
                    <div class="imp010-main-wrapper border">
                        <img :src="currentImage || @json($product->imageUrl)" src="{{ $product->imageUrl }}"
                            alt="{{ $product->name }}" class="imp010-main-img" @click="openLightbox(currentImage)"
                            onerror="this.src='https://placehold.co/640x480/f8f9fa/adb5bd?text=No+Image'" data-imp010="main-image">
                    </div>

                    {{-- Thumbnails --}}
                    <template x-if="allImages.length > 1">
                        <div class="imp010-thumbs" data-imp010="thumbs">
                            <template x-for="(url, i) in allImages" :key="i">
                                <img :src="url" :alt="alt" class="imp010-thumb"
                                    :class="{ 'imp010-thumb-active': url === currentImage }" @click="currentImage = url"
                                    data-imp010="thumb">
                            </template>
                        </div>
                    </template>

                    {{-- Lightbox overlay --}}
                    <div id="imp010-lightbox" class="imp010-lightbox-overlay" x-show="lightboxOpen" x-cloak @click.self="lightboxOpen = false"
                        @keydown.escape.window="lightboxOpen = false" role="dialog" aria-modal="true"
                        aria-label="Product image lightbox">
                        <div class="imp010-lightbox-content" @click.stop>
                            <div x-data="{ zoomed: false }" @click="zoomed = !zoomed" class="imp010-zoom-wrap"
                                :class="{ 'imp010-zoomed': zoomed }">
                                <img :src="lightboxImage" :alt="alt" class="imp010-lightbox-img"
                                    :class="{ 'imp010-img-zoomed': zoomed }" data-imp010="lightbox-image">
                            </div>
                            <button @click="lightboxOpen = false" class="imp010-close-btn" aria-label="Close lightbox"
                                data-imp010="close-btn">&times;</button>
                        </div>
                    </div>

                @else
                    <div class="border rounded d-flex align-items-center justify-content-center bg-light" style="height:380px;">
                        <div class="text-center text-muted">
                            <i class="bi bi-image fs-1 d-block mb-2"></i>
                            <span class="small">No image available</span>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Product info (right column) ─────────────────────────────── --}}
            <div class="col-lg-7">

                <h1 class="h2 fw-bold mb-1">{{ $product->name }}</h1>

                {{-- Average rating (shown prominently below title) --}}
                @php($displayRating = $averageRating ?? $product->rating)
                @if ($displayRating !== null)
                    <div class="d-flex align-items-center gap-2 mb-2" data-imp012="average-rating"
                        aria-label="Average rating: {{ number_format($displayRating, 1) }} out of 5">
                        <span class="visually-hidden">Average Rating: {{ number_format($displayRating, 1) }}</span>
                        @for ($s = 1; $s <= 5; $s++)
                            <i class="bi {{ $s <= round($displayRating) ? 'bi-star-fill text-warning' : 'bi-star text-secondary' }}"
                                style="font-size:1.1rem;" aria-hidden="true"></i>
                        @endfor
                        <span class="fw-semibold small" data-imp012="avg-text">{{ number_format($displayRating, 1) }}</span>
                        <a href="#product-reviews" class="text-muted text-decoration-none small">
                            ({{ $reviews->total() }} review{{ $reviews->total() === 1 ? '' : 's' }})
                        </a>
                    </div>
                @else
                    <p class="text-muted small mb-2">No reviews yet</p>
                @endif

                {{-- Price --}}
                <p class="fs-2 fw-bold text-dark mb-3">${{ number_format($product->price, 2) }}</p>

                {{-- Meta badges --}}
                <div class="d-flex gap-2 flex-wrap mb-3">
                    @if ($product->category)
                        <span class="badge bg-light text-dark border">
                            <i class="bi bi-tag me-1"></i>{{ $product->category->name }}
                        </span>
                    @endif
                    @if ($product->brand)
                        <span class="badge bg-light text-dark border">
                            <i class="bi bi-building me-1"></i>{{ $product->brand->name }}
                        </span>
                    @endif
                    @if ($product->sku)
                        <span class="badge bg-light text-dark border">SKU: {{ $product->sku }}</span>
                    @endif
                </div>

                {{-- Stock status --}}
                <div class="mb-3">
                    @if ($product->stock > 0)
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                            <i class="bi bi-check-circle me-1"></i>In Stock
                            <span class="opacity-75">({{ $product->stock }} available)</span>
                        </span>
                    @else
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                            <i class="bi bi-x-circle me-1"></i>Out of Stock
                        </span>
                    @endif
                </div>

                {{-- Description --}}
                @if ($product->description)
                    <p class="text-muted mb-4" style="line-height:1.7;">{{ $product->description }}</p>
                @endif

                <hr class="mb-4">

                {{-- SC-001: Add to Cart ──────────────────────────────── --}}
                @if ($errors->has('quantity'))
                    <div class="alert alert-danger py-2 small mb-3">{{ $errors->first('quantity') }}</div>
                @endif

                @if ($product->stock > 0)
                    <div id="add-to-cart-wrapper" x-data="imp007AddToCart({
                                 productId:    {{ $product->id }},
                                 productName:  @json($product->name),
                                 productPrice: {{ (float) $product->price }},
                                 productSlug:  @json($product->slug),
                                 cartStoreUrl: '{{ route('cart.store') }}'
                             })">
                        <form id="add-to-cart-form" action="{{ route('cart.store') }}" method="POST" @submit.prevent="submit">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                {{-- Qty stepper --}}
                                <div class="input-group" style="width:120px;">
                                    <button type="button" class="btn btn-outline-secondary" @click="quantity > 1 && quantity--"
                                        aria-label="Decrease quantity">
                                        <i class="bi bi-dash"></i>
                                    </button>
                                    <input type="number" id="quantity" name="quantity" class="form-control text-center fw-semibold"
                                        min="1" max="{{ $product->stock }}" x-model.number="quantity"
                                        style="-moz-appearance:textfield;">
                                    <button type="button" class="btn btn-outline-secondary"
                                        @click="quantity < {{ $product->stock }} && quantity++" aria-label="Increase quantity">
                                        <i class="bi bi-plus"></i>
                                    </button>
                                </div>

                                {{-- Add to Cart button --}}
                                <button type="submit" class="btn btn-dark btn-lg px-4 flex-grow-1 btn-atc" :disabled="loading"
                                    :class="{ 'atc-success': success, 'atc-error': hasError }" id="add-to-cart-btn">
                                    <span x-show="!loading && !success && !hasError">
                                        <i class="bi bi-cart-plus me-2"></i>Add to Cart
                                    </span>
                                    <span x-show="loading" style="display:none">
                                        <span class="atc-spinner"></span> Adding…
                                    </span>
                                    <span x-show="success" style="display:none">
                                        <i class="bi bi-check-lg me-1"></i>Added!
                                    </span>
                                    <span x-show="hasError" style="display:none">
                                        <i class="bi bi-exclamation-triangle me-1"></i>Try Again
                                    </span>
                                </button>

                                {{-- View Cart drawer trigger --}}
                                <button type="button" class="btn btn-outline-secondary btn-lg" data-bs-toggle="offcanvas"
                                    data-bs-target="#cartDrawer" aria-label="View cart">
                                    <i class="bi bi-bag"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                @else
                    <button class="btn btn-secondary btn-lg px-4 w-100" disabled>
                        <i class="bi bi-x-circle me-2"></i>Out of Stock
                    </button>
                @endif

            </div>{{-- /.col-lg-7 --}}
        </div>{{-- /.row --}}

        {{-- Related Products ─────────────────────────────────────────────── --}}
        @if ($related->isNotEmpty())
            <section class="mb-5">
                <h2 class="h5 fw-bold mb-3 text-label">Related Products</h2>
                <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 g-3">
                    @foreach ($related as $item)
                        <div class="col">
                            <a href="{{ $item->slug ? route('products.show', $item->slug) : '#' }}"
                                class="card h-100 text-decoration-none text-dark border-0 shadow-sm card-hover">
                                @if ($item->image)
                                    <img src="{{ $item->imageUrl }}" alt="{{ $item->name }}" class="card-img-top"
                                        style="height:160px;object-fit:cover;" onerror="this.style.display='none'">
                                @else
                                    <div class="bg-light d-flex align-items-center justify-content-center" style="height:160px;">
                                        <i class="bi bi-image text-muted fs-2"></i>
                                    </div>
                                @endif
                                <div class="card-body p-3">
                                    <p class="card-text fw-semibold small mb-1 lh-sm">{{ $item->name }}</p>
                                    <p class="card-text fw-bold text-dark mb-0">${{ number_format($item->price, 2) }}</p>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- RV-001 / RV-002: Customer Reviews ──────────────────────────────── --}}
        <section id="product-reviews" class="mb-5">
            <h2 class="h5 fw-bold mb-4 text-label">Customer Reviews</h2>

            {{-- RV-002: Average rating summary --}}
            @if ($averageRating !== null)
                <div class="card border-0 bg-light rounded-3 p-3 mb-4 d-flex flex-row align-items-center gap-3"
                    data-imp012="average-rating">
                    <div class="text-center px-2">
                        <div class="text-muted small fw-semibold text-uppercase">Average Rating</div>
                        <div class="display-5 fw-bold lh-1">{{ number_format($averageRating, 1) }}</div>
                        <div class="text-muted small">out of 5</div>
                    </div>
                    <div>
                        <div class="d-flex gap-1 mb-1">
                            @for ($s = 1; $s <= 5; $s++)
                                <i class="bi {{ $s <= round($averageRating) ? 'bi-star-fill text-warning' : 'bi-star text-secondary' }} fs-5"
                                    aria-hidden="true"></i>
                            @endfor
                        </div>
                        <div class="text-muted small">
                            <span class="visually-hidden">Average Rating: {{ number_format($averageRating, 1) }}</span>
                            Based on {{ $reviews->total() }} review{{ $reviews->total() === 1 ? '' : 's' }}
                        </div>
                    </div>
                </div>
            @endif

            {{-- User's existing review --}}
            @if ($userReview)
                <div class="alert alert-success d-flex gap-3 align-items-start mb-4">
                    <i class="bi bi-check-circle-fill fs-5 mt-1 flex-shrink-0"></i>
                    <div>
                        <p class="fw-semibold mb-1">Your Review</p>
                        <div class="d-flex gap-1 mb-1">
                            @for ($s = 1; $s <= 5; $s++)
                                <i class="bi {{ $s <= $userReview->rating ? 'bi-star-fill text-warning' : 'bi-star text-secondary' }} small"
                                    aria-hidden="true"></i>
                            @endfor
                        </div>
                        @if (filled($userReview->comment))
                            <p class="mb-0 small">{{ $userReview->comment }}</p>
                        @else
                            <p class="mb-0 small text-muted fst-italic">Rating submitted without a written review.</p>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Reviews list --}}
            @if ($reviews->isEmpty())
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-chat-square-text fs-1 d-block mb-2"></i>
                    <p class="mb-0">No reviews yet. Be the first to review this product!</p>
                </div>
            @else
                <div class="vstack gap-3 mb-4">
                    @foreach ($reviews as $review)
                        <div class="card review-card border shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle bg-primary text-white d-flex align-items-center
                                                            justify-content-center fw-bold flex-shrink-0"
                                            style="width:36px;height:36px;font-size:.8rem;">
                                            {{ strtoupper(substr($review->user->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <p class="mb-0 fw-semibold small">{{ $review->user->name }}</p>
                                            <p class="mb-0 text-muted" style="font-size:.75rem;">
                                                {{ $review->created_at->diffForHumans() }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-1" aria-label="{{ $review->rating }} out of 5 stars"
                                        data-imp012="review-stars">
                                        @for ($s = 1; $s <= 5; $s++)
                                            <i class="bi {{ $s <= $review->rating ? 'bi-star-fill text-warning' : 'bi-star text-secondary' }} small"
                                                aria-hidden="true"></i>
                                        @endfor
                                        <span class="visually-hidden">Rating: {{ $review->rating }} / 5</span>
                                    </div>
                                </div>
                                @if (filled($review->comment))
                                    <p class="mb-0 text-dark" style="line-height:1.6;">{{ $review->comment }}</p>
                                @else
                                    <p class="mb-0 text-muted fst-italic" style="line-height:1.6;">No written review provided.</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
                {{ $reviews->links() }}
            @endif

            {{-- RV-001: Review form — only for users with a delivered order --}}
            @if ($canReview)
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-header bg-white border-bottom fw-semibold py-3">
                        <i class="bi bi-pencil-square me-2 text-primary"></i>Leave a Review
                    </div>
                    <div class="card-body p-4">

                        @if ($errors->has('rating') || $errors->has('comment') || $errors->has('review'))
                            <div class="alert alert-danger small py-2">
                                @foreach ($errors->get('rating') as $msg)<p class="mb-0">{{ $msg }}</p>@endforeach
                                @foreach ($errors->get('comment') as $msg)<p class="mb-0">{{ $msg }}</p>@endforeach
                                @foreach ($errors->get('review') as $msg)<p class="mb-0">{{ $msg }}</p>@endforeach
                            </div>
                        @endif

                        <form action="{{ route('reviews.store', $product->slug) }}" method="POST">
                            @csrf

                            {{-- IMP-012: interactive star rating --}}
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Rating <span class="text-danger">*</span></label>
                                <div class="imp012-star-input d-flex align-items-center gap-1"
                                    x-data="imp012StarRating({ value: {{ (int) old('rating', 0) }} })" data-imp012="star-input">
                                    <input type="hidden" name="rating" :value="rating" data-imp012="rating-input">
                                    <template x-for="i in [1,2,3,4,5]" :key="i">
                                        <button type="button" class="imp012-star"
                                            :class="{ 'imp012-star--filled': i <= effective() }" @click="set(i)"
                                            @mouseenter="hover(i)" @mouseleave="leave()" :aria-label="'Rate ' + i + ' out of 5'"
                                            :aria-pressed="rating === i ? 'true' : 'false'" data-imp012="star-btn">&#9733;</button>
                                    </template>
                                    <span class="text-muted small ms-2" x-text="rating ? rating + ' / 5' : 'Select a rating'"
                                        data-imp012="star-hint"></span>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="comment" class="form-label fw-semibold">
                                    Comment <span class="text-muted fw-normal">(optional)</span>
                                </label>
                                <textarea id="comment" name="comment" rows="4" class="form-control"
                                    placeholder="Share your experience with this product…">{{ old('comment') }}</textarea>
                            </div>

                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-send me-2"></i>Submit Review
                            </button>
                        </form>
                    </div>
                </div>
            @elseif (auth()->check() && !$userReview)
                <div class="alert alert-info border-0 shadow-sm mt-4 small">
                    <i class="bi bi-info-circle me-2"></i>
                    Only customers who have <strong>received</strong> this product (order status: delivered) can leave a review.
                </div>
            @elseif (!auth()->check())
                <div class="alert alert-light border mt-4 small">
                    <i class="bi bi-person-circle me-2"></i>
                    <a href="{{ route('login') }}" class="fw-semibold">Sign in</a> to leave a review
                    (purchase and delivery required).
                </div>
            @endif
        </section>

        {{-- IMP-005: Off-canvas cart drawer ────────────────────────────────── --}}
        <div class="offcanvas offcanvas-end" tabindex="-1" id="cartDrawer" aria-labelledby="cartDrawerLabel"
            style="width:min(380px,100vw);">
            <div class="offcanvas-header border-bottom py-3">
                <h5 class="offcanvas-title fw-bold d-flex align-items-center gap-2 mb-0" id="cartDrawerLabel">
                    <i class="bi bi-bag fs-5"></i> Your Cart
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body p-0 d-flex flex-column" style="min-height:0;">
                <div id="drawer-empty-state"
                    class="{{ empty(session('cart', [])) ? '' : 'd-none' }} flex-grow-1 d-flex flex-column align-items-center justify-content-center text-center px-4 py-5">
                    <i class="bi bi-cart3 fs-1 text-secondary mb-3"></i>
                    <p class="fw-semibold text-dark mb-1">Your cart is empty</p>
                    <p class="text-muted small mb-3">Add items to get started.</p>
                    <button type="button" class="btn btn-primary btn-sm" data-bs-dismiss="offcanvas">Continue Shopping</button>
                </div>
                <div id="drawer-has-items" class="{{ empty(session('cart', [])) ? 'd-none' : '' }} d-flex flex-column flex-grow-1"
                    style="min-height:0;">
                    <div class="flex-grow-1 overflow-auto px-3 pt-3" id="drawer-items-list">
                        @foreach (session('cart', []) as $drawerItem)
                            <div class="d-flex align-items-start gap-3 pb-3 mb-3 border-bottom">
                                <div class="flex-grow-1" style="min-width:0;">
                                    <div class="fw-semibold lh-sm mb-1" style="font-size:.88rem;">
                                        @if ($drawerItem['slug'])
                                            <a href="{{ route('products.show', $drawerItem['slug']) }}"
                                                class="text-dark text-decoration-none">{{ $drawerItem['name'] }}</a>
                                        @else
                                            {{ $drawerItem['name'] }}
                                        @endif
                                    </div>
                                    <div class="text-muted" style="font-size:.78rem;">
                                        ${{ number_format($drawerItem['price'], 2) }} &times; {{ $drawerItem['quantity'] }}
                                    </div>
                                </div>
                                <span class="fw-semibold text-nowrap" style="font-size:.88rem;">
                                    ${{ number_format($drawerItem['price'] * $drawerItem['quantity'], 2) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                    <div class="p-3 border-top" id="drawer-footer">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted small fw-semibold text-uppercase"
                                style="letter-spacing:.04em;">Subtotal</span>
                            <span class="fw-bold fs-6" id="drawer-subtotal">
                                ${{ number_format(collect(session('cart', []))->sum(fn ($item) => $item['price'] * $item['quantity']), 2) }}
                            </span>
                        </div>
                        <a href="{{ route('cart.index') }}" class="btn btn-outline-dark w-100 mb-2 btn-sm">
                            View Full Cart
                        </a>
                        @auth
                            <a href="{{ route('checkout.index') }}" class="btn btn-dark w-100 btn-sm">Checkout &rarr;</a>
                        @else
                            <a href="{{ route('checkout.guest.index') }}" class="btn btn-dark w-100 btn-sm mb-1">Checkout as
                                Guest</a>
                            <a href="{{ route('login') }}" class="btn btn-outline-secondary w-100 btn-sm">Sign In to Checkout</a>
                        @endauth
                    </div>
                </div>
            </div>
        </div>

    @endsection

@push('scripts')
    <script>
        /* IMP-007: Alpine.js component for add-to-cart micro-interactions */
        document.addEventListener('alpine:init', () => {
            Alpine.data('imp007AddToCart', (config) => ({
                loading: false,
                success: false,
                hasError: false,
                badgeText: '',
                quantity: 1,
                async submit() {
                    if (this.loading) return;
                    this.loading = true;
                    this.success = false;
                    this.hasError = false;
                    const token = document.querySelector('meta[name="csrf-token"]').content;
                    try {
                        const res = await fetch(config.cartStoreUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': token,
                            },
                            body: JSON.stringify({ product_id: config.productId, quantity: this.quantity }),
                        });
                        const json = await res.json();
                        this.loading = false;
                        if (res.ok) {
                            this.success = true;
                            if (json.cart_count !== undefined) {
                                this.badgeText = json.cart_count + ' item(s) in cart';
                                imp005UpdateBadge(json.cart_count);
                                imp005OpenDrawer(
                                    { name: config.productName, price: config.productPrice, slug: config.productSlug },
                                    this.quantity
                                );
                            }
                            setTimeout(() => { this.success = false; }, 2000);
                        } else {
                            this.hasError = true;
                            setTimeout(() => { this.hasError = false; }, 2000);
                        }
                    } catch (_e) {
                        this.loading = false;
                        this.hasError = true;
                        setTimeout(() => { this.hasError = false; }, 2000);
                    }
                },
            }));
        });

        function imp005UpdateBadge(count) {
            var el = document.getElementById('navbar-cart-badge');
            if (!el) return;
            el.textContent = count > 0 ? count : '';
            if (count > 0) {
                el.classList.remove('d-none');
            } else {
                el.classList.add('d-none');
            }
        }

        function imp005OpenDrawer(product, qty) {
            var drawerEl = document.getElementById('cartDrawer');
            if (!drawerEl) return;
            // Transition empty state → has-items if needed
            var emptyState = document.getElementById('drawer-empty-state');
            var hasItems = document.getElementById('drawer-has-items');
            var itemsList = document.getElementById('drawer-items-list');
            if (emptyState) emptyState.classList.add('d-none');
            if (hasItems) hasItems.classList.remove('d-none');
            // Inject "just added" confirmation banner
            if (itemsList) {
                var prev = itemsList.querySelector('.imp005-added-banner');
                if (prev) prev.remove();
                var banner = document.createElement('div');
                banner.className = 'alert alert-success alert-dismissible d-flex align-items-center gap-2 py-2 px-3 mb-3 small imp005-added-banner';
                banner.innerHTML =
                    '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" flex-shrink="0" viewBox="0 0 16 16" aria-hidden="true">' +
                    '<path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>' +
                    '</svg>' +
                    '<span><strong>' + imp005EscHtml(product.name) + '</strong> &mdash; ' + qty + ' added</span>' +
                    '<button type="button" class="btn-close ms-auto" style="font-size:.6rem;" data-bs-dismiss="alert" aria-label="Close"></button>';
                itemsList.prepend(banner);
            }
            // Open Bootstrap offcanvas
            if (typeof bootstrap !== 'undefined' && bootstrap.Offcanvas) {
                bootstrap.Offcanvas.getOrCreateInstance(drawerEl).show();
            }
        }

        function imp005EscHtml(str) {
            var d = document.createElement('div');
            d.appendChild(document.createTextNode(String(str)));
            return d.innerHTML;
        }

        /* IMP-010: Lightbox + zoom Alpine component */
        function imp010Lightbox({ mainImage, images, alt }) {
            var allImgs = [];
            if (mainImage) allImgs.push(mainImage);
            if (Array.isArray(images)) {
                images.forEach(function (u) {
                    if (u && allImgs.indexOf(u) === -1) allImgs.push(u);
                });
            }
            return {
                currentImage: mainImage || '',
                allImages: allImgs,
                lightboxOpen: false,
                lightboxImage: '',
                alt: alt,
                openLightbox: function (url) {
                    this.lightboxImage = url;
                    this.lightboxOpen = true;
                },
            };
        }
        /* IMP-012: Interactive star rating Alpine component */
        function imp012StarRating({ value }) {
            return {
                rating: value || 0,
                hovered: 0,
                set(val) { this.rating = val; },
                hover(val) { this.hovered = val; },
                leave() { this.hovered = 0; },
                effective() { return this.hovered || this.rating; },
            };
        }
    </script>
@endpush
