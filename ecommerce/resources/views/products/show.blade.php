<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $product->name }}</title>
    <meta name="description" content="{{ Str::limit($product->description, 160) }}">
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

            {{-- Add to cart placeholder (SC-001) --}}
            <button class="add-to-cart" disabled>Add to Cart</button>
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
</body>

</html>
