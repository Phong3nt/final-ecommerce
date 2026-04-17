<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(): View
    {
        $products = Product::with('category')->latest()->paginate(20);
        return view('admin.products.index', compact('products'));
    }

    public function create(): View
    {
        $categories = Category::orderBy('name')->get();
        return view('admin.products.create', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0.01'],
            'stock' => ['required', 'integer', 'min:0'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'status' => ['required', 'in:draft,published'],
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'max:2048'],
        ]);

        $slug = $this->uniqueSlug(Str::slug($validated['name']));

        $imagePaths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $imagePaths[] = $file->store('products', 'public');
            }
        }

        Product::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'stock' => (int) $validated['stock'],
            'category_id' => $validated['category_id'] ?? null,
            'status' => $validated['status'],
            'images' => $imagePaths ?: null,
            'image' => $imagePaths[0] ?? null,
        ]);

        return redirect()->route('admin.products.index')
            ->with('success', 'Product created successfully.');
    }

    private function uniqueSlug(string $base, ?int $excludeId = null): string
    {
        $slug = $base;
        $count = 1;
        while (Product::where('slug', $slug)->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))->exists()) {
            $slug = $base . '-' . $count++;
        }
        return $slug;
    }

    public function edit(Product $product): View
    {
        $categories = Category::orderBy('name')->get();
        return view('admin.products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0.01'],
            'stock' => ['required', 'integer', 'min:0'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'status' => ['required', 'in:draft,published'],
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'max:2048'],
        ]);

        $slug = $product->slug;
        if (Str::slug($validated['name']) !== $slug) {
            $slug = $this->uniqueSlug(Str::slug($validated['name']), $product->id);
        }

        $imagePaths = $product->images ?? [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $imagePaths[] = $file->store('products', 'public');
            }
        }

        $oldValues = $product->only(['name', 'slug', 'description', 'price', 'stock', 'category_id', 'status']);

        $product->update([
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'stock' => (int) $validated['stock'],
            'category_id' => $validated['category_id'] ?? null,
            'status' => $validated['status'],
            'images' => $imagePaths ?: null,
            'image' => $imagePaths[0] ?? $product->image,
        ]);

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'product.updated',
            'subject_type' => 'Product',
            'subject_id' => $product->id,
            'old_values' => $oldValues,
            'new_values' => $product->fresh()->only(['name', 'slug', 'description', 'price', 'stock', 'category_id', 'status']),
        ]);

        return redirect()->route('admin.products.index')
            ->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'product.deleted',
            'subject_type' => 'Product',
            'subject_id' => $product->id,
            'old_values' => $product->only(['name', 'slug', 'status']),
            'new_values' => ['deleted_at' => now()->toDateTimeString()],
        ]);

        $product->delete();

        return redirect()->route('admin.products.index')
            ->with('success', 'Product archived successfully.');
    }
}
