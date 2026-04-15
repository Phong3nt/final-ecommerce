<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $product->name }}</title>
    <meta name="description" content="{{ Str::limit($product->description, 160) }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>

<body>
    <a href="{{ route('products.index') }}">&larr; Back to Products</a>

    <article class="product-detail">
        {{-- Image gallery --}}
        <div class="product-images">
            @if ($product->image)
                <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" class="product-main-image">
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
                <form id="add-to-cart-form" action="{{ route('cart.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <label for="quantity">Qty:</label>
                    <input type="number" id="quantity" name="quantity" value="1" min="1" max="{{ $product->stock }}" class="qty-input">
                    <button type="submit" class="add-to-cart">Add to Cart</button>
                </form>
                <span id="cart-badge" class="cart-badge"></span>
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
<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('add-to-cart-form');
    if (!form) return;
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var token = form.querySelector('[name="_token"]').value;
        var productId = parseInt(form.querySelector('[name="product_id"]').value);
        var quantity  = parseInt(form.querySelector('[name="quantity"]').value);
        fetch(form.action, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token,
            },
            body: JSON.stringify({ product_id: productId, quantity: quantity }),
        })
        .then(function (res) { return res.json(); })
        .then(function (json) {
            var badge = document.getElementById('cart-badge');
            if (badge && json.cart_count !== undefined) {
                badge.textContent = json.cart_count + ' item(s) in cart';
            }
        })
        .catch(function () {});
    });
});
</script>
</body>

</html>
