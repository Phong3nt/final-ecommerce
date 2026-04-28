<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class ProductController extends Controller
{
    // IMP-014: returns the current catalog cache generation counter.
    // Every Product saved/deleted event increments this, making all previously
    // cached catalog keys stale without needing tag support.
    private static function catalogVersion(): int
    {
        return (int) Cache::get('catalog_version', 0);
    }

    public function index(Request $request): View
    {
        $filters = $request->only(['category', 'min_price', 'max_price', 'min_rating', 'sort']);
        $sort    = $filters['sort'] ?? 'newest';
        $page    = (int) $request->input('page', 1);
        $version = self::catalogVersion();

        // IMP-014: cache category list (cheap but called on every page load)
        $categories = Cache::remember(
            'catalog.cats.' . $version,
            now()->addMinutes(30),
            fn () => Category::orderBy('name')->get()
        );

        // IMP-014: cache paginated product list keyed by version + page + filters
        /** @var \Illuminate\Pagination\LengthAwarePaginator $products */
        $products = Cache::remember(
            'catalog.idx.' . $version . '.' . $page . '.' . md5(json_encode($filters)),
            now()->addMinutes(5),
            fn () => Product::published()
                ->with(['category', 'brand'])
                ->filter($filters)
                ->sort($sort)
                ->paginate(12)
                ->withQueryString()
        );

        return view('products.index', compact('products', 'filters', 'categories'));
    }

    public function show(Product $product): View
    {
        $related = $product->relatedProducts(4);

        // User-specific data is intentionally NOT cached
        $canReview  = false;
        $userReview = null;

        if (auth()->check()) {
            $userId = auth()->id();

            $hasPurchased = Order::where('user_id', $userId)
                ->whereIn('status', ['paid', 'processing', 'shipped', 'delivered'])
                ->whereHas('items', fn($q) => $q->where('product_id', $product->id))
                ->exists();

            $userReview = $product->reviews()->where('user_id', $userId)->first();
            $canReview  = $hasPurchased && $userReview === null;
        }

        $reviewPage = (int) request()->input('page', 1);
        $version    = self::catalogVersion();

        // IMP-014: cache the public (non-user-specific) review data
        [$reviews, $averageRating] = Cache::remember(
            'catalog.show.' . $version . '.' . $product->id . '.r' . $reviewPage,
            now()->addMinutes(10),
            fn () => [
                $product->reviews()->with('user')->latest()->paginate(5),
                $product->reviews()->avg('rating'),
            ]
        );

        return view('products.show', compact('product', 'related', 'canReview', 'userReview', 'reviews', 'averageRating'));
    }

    public function search(Request $request): View|RedirectResponse
    {
        $q = trim($request->input('q', ''));

        if ($q === '') {
            return redirect()->route('products.index');
        }

        $page    = (int) $request->input('page', 1);
        $version = self::catalogVersion();

        // IMP-014: cache search results keyed by version + page + query hash
        /** @var \Illuminate\Pagination\LengthAwarePaginator $results */
        $results = Cache::remember(
            'catalog.srch.' . $version . '.' . $page . '.' . md5($q),
            now()->addMinutes(5),
            function () use ($q) {
                $r = Product::published()->search($q)->latest()->paginate(12);
                $r->withQueryString();
                return $r;
            }
        );

        return view('products.search', compact('results', 'q'));
    }
}
