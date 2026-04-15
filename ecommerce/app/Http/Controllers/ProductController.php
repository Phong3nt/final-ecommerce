<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->only(['category', 'min_price', 'max_price', 'min_rating']);
        $categories = Category::orderBy('name')->get();

        $products = Product::filter($filters)->latest()->paginate(12)->withQueryString();

        return view('products.index', compact('products', 'filters', 'categories'));
    }

    public function search(Request $request): View|RedirectResponse
    {
        $q = trim($request->input('q', ''));

        if ($q === '') {
            return redirect()->route('products.index');
        }

        $results = Product::search($q)->latest()->paginate(12)->withQueryString();

        return view('products.search', compact('results', 'q'));
    }
}
