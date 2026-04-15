<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results</title>
</head>
<body>
    <h1>Search Results</h1>

    <form action="{{ route('products.search') }}" method="GET">
        <input type="text" name="q" value="{{ $q }}" placeholder="Search products..." required>
        <button type="submit">Search</button>
    </form>

    @if ($results->isEmpty())
        <p class="no-results">No products found for &ldquo;{{ $q }}&rdquo;.</p>
    @else
        <p>Showing {{ $results->total() }} result(s) for &ldquo;{{ $q }}&rdquo;</p>

        <div class="product-grid">
            @foreach ($results as $product)
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

        {{ $results->links() }}
    @endif
</body>
</html>
