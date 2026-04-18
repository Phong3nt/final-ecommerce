<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ImportProductsCsvJob;
use App\Jobs\NotifyAdminLowStock;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(): View
    {
        $categoryId = request('category_id');
        $products = Product::with('category')
            ->when($categoryId, fn($q) => $q->where('category_id', (int) $categoryId))
            ->latest()->paginate(20);
        $categories = Category::orderBy('name')->get();
        $imports = ProductImport::with('user')->latest()->limit(10)->get();

        return view('admin.products.index', compact('products', 'categories', 'imports'));
    }

    public function import(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ]);

        $expectedHeaders = ['name', 'description', 'price', 'stock', 'status', 'category'];
        $headers = $this->readCsvHeaders($validated['csv_file']->getRealPath());
        if ($headers !== $expectedHeaders) {
            return back()->withErrors([
                'csv_file' => 'Invalid CSV headers. Expected: ' . implode(',', $expectedHeaders),
            ]);
        }

        $path = $validated['csv_file']->store('product-imports');
        $import = ProductImport::create([
            'user_id' => auth()->id(),
            'file_path' => $path,
            'status' => 'pending',
            'total_rows' => 0,
            'success_rows' => 0,
            'failed_rows' => 0,
            'errors' => [],
        ]);

        ImportProductsCsvJob::dispatch($import->id);

        return redirect()->route('admin.products.index')
            ->with('success', 'CSV import queued. Progress and row errors are listed below.');
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
            'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
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
            'low_stock_threshold' => isset($validated['low_stock_threshold']) ? (int) $validated['low_stock_threshold'] : null,
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

    private function readCsvHeaders(string $path): array
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            return [];
        }

        $headers = fgetcsv($handle) ?: [];
        fclose($handle);

        return $this->normalizeHeaders($headers);
    }

    private function normalizeHeaders(array $headers): array
    {
        return array_map(fn($value) => Str::of((string) $value)->trim()->lower()->value(), $headers);
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
            'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
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

        $oldValues    = $product->only(['name', 'slug', 'description', 'price', 'stock', 'category_id', 'status']);
        $wasNotified  = $product->low_stock_notified;
        $newThreshold = isset($validated['low_stock_threshold']) ? (int) $validated['low_stock_threshold'] : null;
        $newStock     = (int) $validated['stock'];

        $product->update([
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'stock' => $newStock,
            'low_stock_threshold' => $newThreshold,
            'category_id' => $validated['category_id'] ?? null,
            'status' => $validated['status'],
            'images' => $imagePaths ?: null,
            'image' => $imagePaths[0] ?? $product->image,
        ]);

        // NT-003: Low-stock threshold notification (once per breach, reset on restock)
        if ($newThreshold !== null) {
            if ($newStock > $newThreshold) {
                $product->update(['low_stock_notified' => false]);
            } elseif ($newStock <= $newThreshold && !$wasNotified) {
                $product->refresh();
                NotifyAdminLowStock::dispatch($product);
                $product->update(['low_stock_notified' => true]);
            }
        }

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

    // PM-006: Image management

    public function images(Product $product): View
    {
        return view('admin.products.images', compact('product'));
    }

    public function reorderImages(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'image_order' => ['required', 'array'],
            'image_order.*' => ['string'],
        ]);

        $currentImages = $product->images ?? [];
        $newOrder = [];
        foreach ($validated['image_order'] as $path) {
            if (in_array($path, $currentImages, true)) {
                $newOrder[] = $path;
            }
        }

        $thumbnail = $product->image;
        $product->update([
            'images' => $newOrder ?: null,
            'image' => in_array($thumbnail, $newOrder, true) ? $thumbnail : ($newOrder[0] ?? null),
        ]);

        return redirect()->route('admin.products.images', $product)
            ->with('success', 'Image order updated.');
    }

    public function setThumbnail(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'thumbnail_index' => ['required', 'integer', 'min:0'],
        ]);

        $images = $product->images ?? [];
        $index = (int) $validated['thumbnail_index'];

        if (!isset($images[$index])) {
            return back()->withErrors(['thumbnail_index' => 'Invalid image index.']);
        }

        $product->update(['image' => $images[$index]]);

        return redirect()->route('admin.products.images', $product)
            ->with('success', 'Thumbnail updated.');
    }

    public function destroyImage(Product $product, int $index): RedirectResponse
    {
        $images = $product->images ?? [];

        if (!isset($images[$index])) {
            abort(404);
        }

        $removedPath = $images[$index];
        array_splice($images, $index, 1);

        $thumbnail = $product->image;
        if ($thumbnail === $removedPath) {
            $thumbnail = $images[0] ?? null;
        }

        $product->update([
            'images' => $images ?: null,
            'image' => $thumbnail,
        ]);

        return redirect()->route('admin.products.images', $product)
            ->with('success', 'Image removed.');
    }
}
