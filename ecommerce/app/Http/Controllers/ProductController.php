<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(): View
    {
        $products = Product::latest()->paginate(12);

        return view('products.index', compact('products'));
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
