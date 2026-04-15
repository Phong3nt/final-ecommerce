<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products</title>
</head>

<body>
    <h1>Products</h1>

    @if ($products->isEmpty())
        <p>No products available.</p>
    @else
        <div class="product-grid">
            @foreach ($products as $product)
                <div class="product-card">
                    @if ($product->image)
                        <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}">
                    @endif
                    <h2>{{ $product->name }}</h2>
                    <p class="price">${{ number_format($product->price, 2) }}</p>
                    <p class="stock-status">
                        @if ($product->stock > 0)
                            In Stock
                        @else
                            Out of Stock
                        @endif
                    </p>
                </div>
            @endforeach
        </div>

        {{ $products->links() }}
    @endif
</body>

</html>