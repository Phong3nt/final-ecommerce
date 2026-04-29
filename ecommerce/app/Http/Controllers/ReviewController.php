<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * RV-001: Submit a review for a product.
     *
     * AC:
     *   - Only users who purchased the product can review
    *   - 1–5 star rating with optional text comment
     *   - One review per product per user
     */
    public function store(Request $request, Product $product): RedirectResponse
    {
        $user = auth()->user();

        // AC: only users who purchased the product may review
        $hasPurchased = Order::where('user_id', $user->id)
            ->where('status', 'delivered')
            ->whereHas('items', fn($q) => $q->where('product_id', $product->id))
            ->exists();

        if (!$hasPurchased) {
            return redirect()->route('products.show', $product->slug)
                ->withErrors(['review' => 'You can only review products you have purchased.']);
        }

        // AC: one review per product per user
        $alreadyReviewed = Review::where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->exists();

        if ($alreadyReviewed) {
            return redirect()->route('products.show', $product->slug)
                ->withErrors(['review' => 'You have already reviewed this product.']);
        }

        $data = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:2000',
        ]);

        Review::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
        ]);
        // RV-002: keep product.rating in sync with the live average from reviews
        $product->update(['rating' => round((float) $product->reviews()->avg('rating'), 2)]);
        return redirect()->route('products.show', $product->slug)
            ->with('success', 'Your review has been submitted.');
    }
}
