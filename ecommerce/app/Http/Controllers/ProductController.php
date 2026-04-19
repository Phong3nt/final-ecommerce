<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->only(['category', 'min_price', 'max_price', 'min_rating', 'sort']);
        $categories = Category::orderBy('name')->get();
        $sort = $filters['sort'] ?? 'newest';

        $products = Product::published()->with('category')->filter($filters)->sort($sort)->paginate(12)->withQueryString();

        return view('products.index', compact('products', 'filters', 'categories'));
    }

    public function show(Product $product): View
    {
        $related = $product->relatedProducts(4);

        $canReview = false;
        $userReview = null;

        if (auth()->check()) {
            $userId = auth()->id();

            $hasPurchased = Order::where('user_id', $userId)
                ->whereIn('status', ['paid', 'processing', 'shipped', 'delivered'])
                ->whereHas('items', fn($q) => $q->where('product_id', $product->id))
                ->exists();

            $userReview = $product->reviews()->where('user_id', $userId)->first();
            $canReview = $hasPurchased && $userReview === null;
        }

        // RV-002: paginated reviews list + average rating
        $reviews = $product->reviews()->with('user')->latest()->paginate(5);
        $averageRating = $product->reviews()->avg('rating');

        return view('products.show', compact('product', 'related', 'canReview', 'userReview', 'reviews', 'averageRating'));
    }

    public function search(Request $request): View|RedirectResponse
    {
        $q = trim($request->input('q', ''));

        if ($q === '') {
            return redirect()->route('products.index');
        }

        $results = Product::published()->search($q)->latest()->paginate(12)->withQueryString();

        return view('products.search', compact('results', 'q'));
    }
}
