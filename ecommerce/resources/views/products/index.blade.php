<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products</title>
</head>

<body>
    <h1>Products</h1>

    <form action="{{ route('products.index') }}" method="GET" id="filter-form">
        <div>
            <label for="category">Category</label>
            <select name="category" id="category">
                <option value="">All Categories</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat->id }}" {{ (string) ($filters['category'] ?? '') === (string) $cat->id ? 'selected' : '' }}>
                        {{ $cat->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="min_price">Min Price</label>
            <input type="number" name="min_price" id="min_price" step="0.01" min="0"
                value="{{ $filters['min_price'] ?? '' }}">
        </div>

        <div>
            <label for="max_price">Max Price</label>
            <input type="number" name="max_price" id="max_price" step="0.01" min="0"
                value="{{ $filters['max_price'] ?? '' }}">
        </div>

        <div>
            <label for="min_rating">Min Rating</label>
            <input type="number" name="min_rating" id="min_rating" step="0.1" min="0" max="5"
                value="{{ $filters['min_rating'] ?? '' }}">
        </div>

        <button type="submit">Apply Filters</button>
        <a href="{{ route('products.index') }}">Clear Filters</a>
    </form>

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
                    @if ($product->category)
                        <p class="category">{{ $product->category->name }}</p>
                    @endif
                    @if ($product->rating !== null)
                        <p class="rating">Rating: {{ number_format($product->rating, 1) }}</p>
                    @endif
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