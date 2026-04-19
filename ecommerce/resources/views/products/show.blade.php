<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $product->name }}</title>
    <meta name="description" content="{{ Str::limit($product->description, 160) }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <!-- IMP-007: Alpine.js micro-interactions -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
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
    </style>
</head>

<body class="bg-light">
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
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="currentColor"
                        viewBox="0 0 16 16" aria-hidden="true">
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
            {{-- Image gallery --}}
            <div class="product-images">
                @if ($product->image)
                    <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}"
                        class="product-main-image">
                @else
                    <p class="no-image">No image available</p>
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
                @if (session('success'))
                    <p class="alert-success">{{ session('success') }}</p>
                @endif

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
                                <img src="{{ asset('storage/' . $item->image) }}" alt="{{ $item->name }}">
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

            {{-- RV-002: Average rating shown prominently --}}
            @if ($averageRating !== null)
                <p class="average-rating">
                    <strong>Average Rating: {{ number_format($averageRating, 1) }} / 5</strong>
                    ({{ $reviews->total() }} review{{ $reviews->total() === 1 ? '' : 's' }})
                </p>
            @endif

            {{-- RV-002: Paginated reviews list --}}
            @if ($reviews->isEmpty())
                <p class="no-reviews">No reviews yet.</p>
            @else
                <div class="reviews-list">
                    @foreach ($reviews as $review)
                        <div class="review-item">
                            <p class="review-rating">Rating: {{ $review->rating }} / 5</p>
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
                        <div>
                            <label for="rating">Rating (1–5):</label>
                            <select id="rating" name="rating" required>
                                <option value="">Select…</option>
                                @for ($i = 1; $i <= 5; $i++)
                                    <option value="{{ $i }}" {{ old('rating') == $i ? 'selected' : '' }}>{{ $i }}</option>
                                @endfor
                            </select>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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
    </script>
</body>

</html>