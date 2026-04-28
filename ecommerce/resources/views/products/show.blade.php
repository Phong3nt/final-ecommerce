@extends('layouts.app')

@section('title', $product->name)

@push('styles')
    <style>
        .product-main-image {
            max-width: 100%;
            height: auto;
            border-radius: .5rem;
        }

        .no-image {
            color: #6c757d;
            font-style: italic;
        }

        .sku,
        .category,
        .rating {
            margin: .25rem 0;
            font-size: .9rem;
            color: #555;
        }

        .stock-status {
            margin: .5rem 0;
            font-weight: 600;
        }

        .price {
            font-size: 1.75rem;
            font-weight: 700;
            margin: .5rem 0;
        }

        .description {
            margin-top: 1rem;
        }

        .alert-success {
            color: #0f5132;
            background: #d1e7dd;
            border: 1px solid #badbcc;
            padding: .6rem 1rem;
            border-radius: .375rem;
            margin-bottom: .75rem;
        }

        .alert-error {
            color: #842029;
            background: #f8d7da;
            border: 1px solid #f5c2c7;
            padding: .6rem 1rem;
            border-radius: .375rem;
            margin-bottom: .75rem;
        }

        .qty-input {
            width: 4.5rem;
            display: inline-block;
        }

        .add-to-cart {
            padding: .5rem 1.25rem;
            background: #212529;
            color: #fff;
            border: none;
            border-radius: .375rem;
            cursor: pointer;
            font-size: .95rem;
        }

        .add-to-cart:disabled {
            opacity: .5;
            cursor: not-allowed;
        }

        .cart-badge {
            display: inline-block;
            margin-left: .5rem;
            font-size: .85rem;
            color: #0f5132;
        }

        .product-detail {
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
            margin-top: 1.5rem;
        }

        .product-images {
            flex: 0 0 360px;
            max-width: 100%;
        }

        .product-info {
            flex: 1;
            min-width: 220px;
        }

        .related-products {
            margin-top: 2rem;
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .product-card {
            border: 1px solid #dee2e6;
            border-radius: .5rem;
            padding: 1rem;
        }

        .product-card img {
            max-width: 100%;
            border-radius: .375rem;
            margin-bottom: .5rem;
        }

        .product-reviews {
            margin-top: 2.5rem;
        }

        .reviews-list .review-item {
            border-bottom: 1px solid #dee2e6;
            padding: .75rem 0;
        }

        .review-rating,
        .review-comment,
        .review-author {
            margin: .25rem 0;
        }

        .review-author {
            color: #6c757d;
            font-size: .875rem;
        }

        .review-form {
            margin-top: 1.5rem;
            padding: 1.25rem;
            background: #f8f9fa;
            border-radius: .5rem;
        }

        .review-form h3 {
            margin-bottom: 1rem;
        }

        .review-form div {
            margin-bottom: .75rem;
        }

        .average-rating {
            font-size: 1.1rem;
            margin-bottom: 1rem;
        }

        .no-reviews {
            color: #6c757d;
        }

        .pagination {
            margin-top: 1rem;
        }

        /* IMP-012: Interactive star rating */
        .imp012-star {
            font-size: 1.5rem;
            color: #d1d5db;
            line-height: 1;
            cursor: default;
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
            font-size: 1.8rem;
        }

        .imp012-star-input .imp012-star:hover,
        .imp012-star-input .imp012-star:focus {
            transform: scale(1.15);
            outline: none;
        }

        .imp012-star-hint {
            display: inline-block;
            margin-left: .5rem;
            font-size: .85rem;
            color: #6b7280;
            vertical-align: middle;
        }

        .imp012-avg {
            display: flex;
            align-items: center;
            gap: .35rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }

        .imp012-avg .imp012-star {
            font-size: 1.3rem;
        }

        .imp012-avg-text {
            font-size: 1rem;
            font-weight: 600;
            color: #111827;
        }

        .imp012-review-stars .imp012-star {
            font-size: 1rem;
        }

        /* IMP-007: Add-to-cart button micro-interactions */
        .atc-spinner {
            display: inline-block;
            width: 13px;
            height: 13px;
            border: 2px solid rgba(255, 255, 255, 0.35);
            border-top-color: #fff;
            border-radius: 50%;
            animation: imp007spin 0.55s linear infinite;
            vertical-align: text-bottom;
        }

        @keyframes imp007spin {
            to {
                transform: rotate(360deg);
            }
        }

        .add-to-cart.atc-success {
            background: #198754;
            transition: background 0.25s ease;
        }

        .add-to-cart.atc-error {
            background: #dc3545;
            animation: imp007shake 0.35s ease;
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

        /* IMP-010: Lightbox + zoom gallery */
        .imp010-main-wrapper {
            position: relative;
            cursor: zoom-in;
            overflow: hidden;
            border-radius: .5rem;
        }

        .imp010-main-img {
            max-width: 100%;
            height: auto;
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
            border-color: #212529;
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
            transform: scale(1);
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
    </style>
@endpush

@section('content')
    @include('partials.toast')
    <!-- IMP-005: Bootstrap navbar with cart drawer trigger -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container-xl">
            <a class="navbar-brand fw-bold" href="{{ route('products.index') }}">ShopApp</a>
            <div class="ms-auto d-flex align-items-center gap-2">
                <a class="btn btn-outline-light btn-sm" href="{{ route('products.index') }}"
                    aria-label="Back to catalog">&larr; Catalog</a>
                @php $__cartCountShow = array_sum(array_column(session('cart', []), 'quantity')); @endphp
                <button class="btn btn-outline-light btn-sm position-relative" type="button" data-bs-toggle="offcanvas"
                    data-bs-target="#cartDrawer" aria-controls="cartDrawer" id="cart-drawer-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="currentColor" viewBox="0 0 16 16"
                        aria-hidden="true">
                        <path
                            d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .49.598l-1 5a.5.5 0 0 1-.465.401l-9.397.948L4.043 12H13a.5.5 0 0 1 0 1H4a.5.5 0 0 1-.465-.686L5.28 8.643 3.055 3.75 2.61 2H.5a.5.5 0 0 1-.5-.5zM3.226 4l.893 4.462 9.144-.925.79-3.537H3.226zM5.5 13a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3zm-2 1.5a2 2 0 1 1 4 0 2 2 0 0 1-4 0zm9-2a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3zm-2 1.5a2 2 0 1 1 4 0 2 2 0 0 1-4 0z" />
                    </svg>
                    <span class="ms-1">Cart</span>
                    @if($__cartCountShow > 0)
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                            id="cart-badge-count" style="font-size:.6rem;">{{ $__cartCountShow }}</span>
                    @else
                        <span
                            class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger visually-hidden"
                            id="cart-badge-count" style="font-size:.6rem;">0</span>
                    @endif
                </button>
            </div>
        </div>
    </nav>
    <div class="container-xl py-4">

        <article class="product-detail">
            {{-- IMP-010: Lightbox + zoom gallery --}}
            <div class="product-images" x-data="imp010Lightbox({
                             mainImage: @json($product->imageUrl),
                             images: @json($product->imagesUrls),
                             alt: @json($product->name)
                         })">
                @if ($product->image)
                    <div class="imp010-main-wrapper">
                        <img src="{{ $product->imageUrl }}" :src="currentImage" alt="{{ $product->name }}" :alt="alt"
                            class="product-main-image imp010-main-img" @click="openLightbox(currentImage)"
                            data-imp010="main-image">
                    </div>
                    <template x-if="allImages.length > 1">
                        <div class="imp010-thumbs" data-imp010="thumbs">
                            <template x-for="(url, i) in allImages" :key="i">
                                <img :src="url" :alt="alt" class="imp010-thumb"
                                    :class="{ 'imp010-thumb-active': url === currentImage }" @click="currentImage = url"
                                    data-imp010="thumb">
                            </template>
                        </div>
                    </template>
                @else
                    <p class="no-image">No image available</p>
                @endif

                {{-- IMP-010: Lightbox overlay (hidden until triggered) --}}
                @if ($product->image)
                    <div class="imp010-lightbox-overlay" id="imp010-lightbox" x-show="lightboxOpen"
                        @click.self="lightboxOpen = false" @keydown.escape.window="lightboxOpen = false" role="dialog"
                        aria-modal="true" aria-label="Product image lightbox" style="display:none;">
                        <div class="imp010-lightbox-content" @click.stop>
                            <div x-data="{ zoomed: false }" @click="zoomed = !zoomed" class="imp010-zoom-wrap"
                                :class="{ 'imp010-zoomed': zoomed }">
                                <img :src="lightboxImage" :alt="alt" class="imp010-lightbox-img"
                                    :class="{ 'imp010-img-zoomed': zoomed }" data-imp010="lightbox-image">
                            </div>
                            <button @click="lightboxOpen = false" class="imp010-close-btn" aria-label="Close lightbox"
                                data-imp010="close-btn">
                                &times;
                            </button>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Core details --}}
            <div class="product-info">
                <h1>{{ $product->name }}</h1>

                @if ($product->sku)
                    <p class="sku">SKU: {{ $product->sku }}</p>
                @endif

                <p class="price">${{ number_format($product->price, 2) }}</p>

                @if ($product->category)
                    <p class="category">Category: {{ $product->category->name }}</p>
                @endif

                @if ($product->rating !== null)
                    <p class="rating">Rating: {{ number_format($product->rating, 1) }} / 5</p>
                @endif

                <p class="stock-status">
                    @if ($product->stock > 0)
                        In Stock ({{ $product->stock }} available)
                    @else
                        Out of Stock
                    @endif
                </p>

                <div class="description">
                    <h2>Description</h2>
                    <p>{{ $product->description }}</p>
                </div>

                {{-- SC-001: Add to Cart --}}
                @if ($errors->has('quantity'))
                    <p class="alert-error">{{ $errors->first('quantity') }}</p>
                @endif

                @if ($product->stock > 0)
                    <div id="add-to-cart-wrapper" x-data="imp007AddToCart({
                                                                 productId: {{ $product->id }},
                                                                 productName: @json($product->name),
                                                                 productPrice: {{ (float) $product->price }},
                                                                 productSlug: @json($product->slug),
                                                                 cartStoreUrl: '{{ route('cart.store') }}'
                                                             })">
                        <form id="add-to-cart-form" action="{{ route('cart.store') }}" method="POST"
                            x-on:submit.prevent="submit">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                            <label for="quantity">Qty:</label>
                            <input type="number" id="quantity" name="quantity" min="1" max="{{ $product->stock }}"
                                class="qty-input" x-model.number="quantity">
                            <button type="submit" class="add-to-cart" :disabled="loading"
                                :class="{ 'atc-success': success, 'atc-error': hasError }">
                                <span x-show="!loading && !success && !hasError">Add to Cart</span>
                                <span x-show="loading" style="display:none"><span class="atc-spinner"></span> Adding…</span>
                                <span x-show="success" style="display:none">✓ Added</span>
                                <span x-show="hasError" style="display:none">Try Again</span>
                            </button>
                        </form>
                        <span id="cart-badge" class="cart-badge" x-text="badgeText"></span>
                    </div>
                @else
                    <button class="add-to-cart" disabled>Add to Cart</button>
                @endif
            </div>
        </article>

        {{-- Related products --}}
        @if ($related->isNotEmpty())
            <section class="related-products">
                <h2>Related Products</h2>
                <div class="product-grid">
                    @foreach ($related as $item)
                        <div class="product-card">
                            @if ($item->image)
                                <img src="{{ $item->imageUrl }}" alt="{{ $item->name }}">
                            @endif
                            <h3>
                                @if ($item->slug)
                                    <a href="{{ route('products.show', $item->slug) }}">{{ $item->name }}</a>
                                @else
                                    {{ $item->name }}
                                @endif
                            </h3>
                            <p class="price">${{ number_format($item->price, 2) }}</p>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- RV-001 / RV-002: Product Reviews --}}
        <section class="product-reviews">
            <h2>Customer Reviews</h2>

            {{-- RV-002: Average rating shown prominently — IMP-012: visual stars --}}
            @if ($averageRating !== null)
                <div class="imp012-avg" data-imp012="average-rating"
                    aria-label="Average rating: {{ number_format($averageRating, 1) }} out of 5">
                    @for ($__s = 1; $__s <= 5; $__s++)
                        <span class="imp012-star {{ $__s <= round($averageRating) ? 'imp012-star--filled' : '' }}"
                            aria-hidden="true">&#9733;</span>
                    @endfor
                    <span class="imp012-avg-text" data-imp012="avg-text">
                        Average Rating: {{ number_format($averageRating, 1) }} / 5
                        ({{ $reviews->total() }} review{{ $reviews->total() === 1 ? '' : 's' }})
                    </span>
                </div>
            @endif

            {{-- RV-002: Paginated reviews list --}}
            @if ($reviews->isEmpty())
                <p class="no-reviews">No reviews yet.</p>
            @else
                <div class="reviews-list">
                    @foreach ($reviews as $review)
                        <div class="review-item">
                            {{-- IMP-012: visual star display for each review --}}
                            <div class="imp012-review-stars" data-imp012="review-stars"
                                aria-label="{{ $review->rating }} out of 5 stars">
                                @for ($__s = 1; $__s <= 5; $__s++)
                                    <span class="imp012-star {{ $__s <= $review->rating ? 'imp012-star--filled' : '' }}"
                                        aria-hidden="true">&#9733;</span>
                                @endfor
                                <span class="visually-hidden">Rating: {{ $review->rating }} / 5</span>
                            </div>
                            <p class="review-comment">{{ $review->comment }}</p>
                            <p class="review-author">— {{ $review->user->name }}</p>
                        </div>
                    @endforeach
                </div>
                {{ $reviews->links() }}
            @endif

            {{-- RV-001: Review submission form — only for eligible purchasers --}}
            @if ($canReview)
                <div class="review-form">
                    <h3>Leave a Review</h3>

                    @if ($errors->has('rating') || $errors->has('comment'))
                        <ul class="alert-error">
                            @foreach ($errors->get('rating') as $msg)
                                <li>{{ $msg }}</li>
                            @endforeach
                            @foreach ($errors->get('comment') as $msg)
                                <li>{{ $msg }}</li>
                            @endforeach
                        </ul>
                    @endif

                    <form action="{{ route('reviews.store', $product->slug) }}" method="POST">
                        @csrf
                        {{-- IMP-012: Alpine.js interactive star input replaces <select> --}}
                            <div>
                                <label>Rating:</label>
                                <div class="imp012-star-input"
                                    x-data="imp012StarRating({ value: {{ (int) old('rating', 0) }} })" data-imp012="star-input">
                                    <input type="hidden" name="rating" :value="rating" data-imp012="rating-input">
                                    <template x-for="i in [1,2,3,4,5]" :key="i">
                                        <button type="button" class="imp012-star"
                                            :class="{ 'imp012-star--filled': i <= effective() }" @click="set(i)"
                                            @mouseenter="hover(i)" @mouseleave="leave()" :aria-label="'Rate ' + i + ' out of 5'"
                                            :aria-pressed="rating === i ? 'true' : 'false'" data-imp012="star-btn">
                                            &#9733;
                                        </button>
                                    </template>
                                    <span class="imp012-star-hint" x-text="rating ? rating + ' / 5' : 'Select a rating'"
                                        data-imp012="star-hint"></span>
                                </div>
                            </div>
                            <div>
                                <label for="comment">Comment:</label>
                                <textarea id="comment" name="comment" rows="4" required>{{ old('comment') }}</textarea>
                            </div>
                            <button type="submit">Submit Review</button>
                    </form>
                </div>
            @endif
        </section>
    </div>{{-- /.container-xl --}}

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
                $__dcShow = session('cart', []);
                $__dsShow = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $__dcShow));
            @endphp
            <div id="drawer-empty-state"
                class="{{ empty($__dcShow) ? '' : 'd-none' }} flex-grow-1 d-flex flex-column align-items-center justify-content-center text-center px-4 py-5">
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
            <div id="drawer-has-items" class="{{ empty($__dcShow) ? 'd-none' : '' }} d-flex flex-column flex-grow-1"
                style="min-height:0;">
                <div class="flex-grow-1 overflow-auto px-3 pt-3" id="drawer-items-list">
                    @foreach($__dcShow as $__showItem)
                        <div class="d-flex align-items-start gap-3 pb-3 mb-3 border-bottom">
                            <div class="flex-grow-1" style="min-width:0;">
                                <div class="fw-semibold lh-sm mb-1" style="font-size:.88rem;">
                                    @if($__showItem['slug'])
                                        <a href="{{ route('products.show', $__showItem['slug']) }}"
                                            class="text-dark text-decoration-none">
                                            {{ $__showItem['name'] }}
                                        </a>
                                    @else
                                        {{ $__showItem['name'] }}
                                    @endif
                                </div>
                                <div class="text-muted" style="font-size:.78rem;">
                                    ${{ number_format($__showItem['price'], 2) }} &times; {{ $__showItem['quantity'] }}
                                </div>
                            </div>
                            <span class="fw-semibold text-nowrap" style="font-size:.88rem;">
                                ${{ number_format($__showItem['price'] * $__showItem['quantity'], 2) }}
                            </span>
                        </div>
                    @endforeach
                </div>
                <div class="p-3 border-top" id="drawer-footer">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted small fw-semibold text-uppercase"
                            style="letter-spacing:.04em;">Subtotal</span>
                        <span class="fw-bold fs-6" id="drawer-subtotal">${{ number_format($__dsShow, 2) }}</span>
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
            var el = document.getElementById('cart-badge-count');
            if (!el) return;
            el.textContent = count;
            if (count > 0) {
                el.classList.remove('visually-hidden');
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