<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ImportProductsCsvJob;
use App\Jobs\NotifyAdminLowStock;
use App\Models\AuditLog;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $categoryId = $request->integer('category_id') ?: null;
        /** @var \Illuminate\Pagination\LengthAwarePaginator $products */
        $products = Product::with(['category', 'brand'])
            ->when($categoryId, fn($q) => $q->where('category_id', $categoryId))
            ->latest()->paginate(20);
        $categories = Category::orderBy('name')->get();
        $imports = ProductImport::with('user')->latest()->limit(10)->get();

        // IMP-040: AJAX category filter — return JSON with rendered rows + pagination only
        if ($request->boolean('_ajax')) {
            $appends = $categoryId ? ['category_id' => $categoryId] : [];
            $products->appends($appends);

            return response()->json([
                'rows_html'       => view('admin.products._rows', compact('products'))->render(),
                'pagination_html' => $products->links()->toHtml(),
                'total'           => $products->total(),
                'page_ids'        => $products->pluck('id')->map(fn($id) => (string) $id)->values()->toArray(),
                'category_id'     => $categoryId,
            ]);
        }

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
        $brands     = Brand::orderBy('name')->get();
        return view('admin.products.create', compact('categories', 'brands'));
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
            'brand_id'    => ['nullable', 'integer', 'exists:brands,id'],
            'status' => ['required', 'in:draft,published'],
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'max:10240'],
        ]);

        $slug = $this->uniqueSlug(Str::slug($validated['name']));

        $imagePaths = [];
        if ($request->hasFile('images')) {
            $disk = config('filesystems.image_disk', 's3');
            foreach ($request->file('images') as $file) {
                $imagePaths[] = $this->processAndStoreImage($file, $disk);
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
            'brand_id'    => $validated['brand_id'] ?? null,
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

    // IMP-039: Bulk status change for multiple products
    public function bulkStatus(Request $request): RedirectResponse
    {
        $request->validate([
            'bulk_action'            => ['required', Rule::in(['published', 'draft', 'delete'])],
            'product_ids'            => ['nullable', 'array'],
            'product_ids.*'          => ['integer', 'exists:products,id'],
            'bulk_category_id'       => ['nullable', 'integer', 'exists:categories,id'],
            'select_all_in_category' => ['nullable', 'boolean'],
        ]);

        $hasIds      = !empty($request->product_ids);
        $hasCategory = $request->boolean('select_all_in_category')
                       && $request->filled('bulk_category_id');

        if (!$hasIds && !$hasCategory) {
            return back()->withErrors(['product_ids' => 'Select at least one product.']);
        }

        $action = $request->bulk_action;
        $query  = $hasCategory
            ? Product::where('category_id', (int) $request->bulk_category_id)
            : Product::whereIn('id', array_map('intval', (array) $request->product_ids));

        if ($action === 'delete') {
            $count = $query->count();
            $query->each(fn (Product $p) => $p->delete());
            $label = 'archived';
        } else {
            $count = $query->update(['status' => $action]);
            $label = "set to '" . ucfirst($action) . "'";
        }

        return back()->with('success', "{$count} product(s) {$label}.");
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
        $brands     = Brand::orderBy('name')->get();
        return view('admin.products.edit', compact('product', 'categories', 'brands'));
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
            'brand_id'    => ['nullable', 'integer', 'exists:brands,id'],
            'status' => ['required', 'in:draft,published'],
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'max:10240'],
        ]);

        $slug = $product->slug;
        if (Str::slug($validated['name']) !== $slug) {
            $slug = $this->uniqueSlug(Str::slug($validated['name']), $product->id);
        }

        $imagePaths = $product->images ?? [];
        if ($request->hasFile('images')) {
            $disk = config('filesystems.image_disk', 's3');
            foreach ($request->file('images') as $file) {
                $imagePaths[] = $this->processAndStoreImage($file, $disk);
            }
        }

        $oldValues = $product->only(['name', 'slug', 'description', 'price', 'stock', 'category_id', 'status']);
        $wasNotified = $product->low_stock_notified;
        $newThreshold = isset($validated['low_stock_threshold']) ? (int) $validated['low_stock_threshold'] : null;
        $newStock = (int) $validated['stock'];

        $product->update([
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'stock' => $newStock,
            'low_stock_threshold' => $newThreshold,
            'category_id' => $validated['category_id'] ?? null,
            'brand_id'    => $validated['brand_id'] ?? null,
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

    public function storeImages(Request $request, Product $product): RedirectResponse
    {
        $request->validate([
            'images'   => ['required', 'array', 'min:1'],
            'images.*' => ['image', 'max:10240'],
        ]);

        $disk  = config('filesystems.image_disk', 's3');
        $paths = $product->images ?? [];
        foreach ($request->file('images') as $file) {
            $paths[] = $this->processAndStoreImage($file, $disk);
        }

        $product->update([
            'images' => $paths,
            'image'  => $product->image ?? $paths[0],
        ]);

        return redirect()->route('admin.products.images', $product)
            ->with('success', 'Images uploaded successfully.');
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

    /**
     * Resize + compress an uploaded image before storing.
     *
     * - Downscales so neither dimension exceeds 1920 px (aspect ratio preserved)
     * - Converts to JPEG at 82 % quality (PNG → JPG, transparent areas → white)
     * - Falls back to plain store() when GD is unavailable or the file cannot
     *   be decoded (e.g. SVG), so uploads are never silently dropped.
     */
    private function processAndStoreImage(UploadedFile $file, string $disk): string
    {
        if (!extension_loaded('gd')) {
            return $file->store('products', $disk);
        }

        $source = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));

        if ($source === false) {
            return $file->store('products', $disk);
        }

        $origW = imagesx($source);
        $origH = imagesy($source);

        // Downscale to max 1920 px on either axis, preserve aspect ratio
        $maxDim = 1920;
        if ($origW > $maxDim || $origH > $maxDim) {
            if ($origW >= $origH) {
                $newW = $maxDim;
                $newH = (int) round($origH * ($maxDim / $origW));
            } else {
                $newH = $maxDim;
                $newW = (int) round($origW * ($maxDim / $origH));
            }
        } else {
            $newW = $origW;
            $newH = $origH;
        }

        // White canvas handles PNG transparency before JPEG conversion
        $canvas = imagecreatetruecolor($newW, $newH);
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
        imagedestroy($source);

        ob_start();
        imagejpeg($canvas, null, 82);
        $jpeg = (string) ob_get_clean();
        imagedestroy($canvas);

        // Always store with .jpg extension
        $filename = 'products/' . pathinfo($file->hashName(), PATHINFO_FILENAME) . '.jpg';
        Storage::disk($disk)->put($filename, $jpeg);

        return $filename;
    }
}
