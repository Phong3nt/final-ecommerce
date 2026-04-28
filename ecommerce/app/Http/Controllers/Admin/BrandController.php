<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Services\IcecatImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * IMP-046 — Admin brand management (CRUD + Icecat supplier import).
 */
class BrandController extends Controller
{
    public function index(): View
    {
        $brands = Brand::withCount('products')->orderBy('name')->get();
        return view('admin.brands.index', compact('brands'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'               => ['required', 'string', 'max:100', 'unique:brands,name'],
            'logo_url'           => ['nullable', 'url', 'max:500'],
            'icecat_supplier_id' => ['nullable', 'integer', 'min:1', 'unique:brands,icecat_supplier_id'],
        ]);

        Brand::create([
            'name'               => $validated['name'],
            'slug'               => $this->uniqueSlug($validated['name']),
            'logo_url'           => $validated['logo_url'] ?? null,
            'icecat_supplier_id' => $validated['icecat_supplier_id'] ?? null,
        ]);

        return redirect()->route('admin.brands.index')
            ->with('success', 'Brand created.');
    }

    public function update(Request $request, Brand $brand): RedirectResponse
    {
        $validated = $request->validate([
            'name'               => ['required', 'string', 'max:100', 'unique:brands,name,' . $brand->id],
            'logo_url'           => ['nullable', 'url', 'max:500'],
            'icecat_supplier_id' => ['nullable', 'integer', 'min:1', 'unique:brands,icecat_supplier_id,' . $brand->id],
        ]);

        $brand->update([
            'name'               => $validated['name'],
            'slug'               => $this->uniqueSlug($validated['name'], $brand->id),
            'logo_url'           => $validated['logo_url'] ?? null,
            'icecat_supplier_id' => $validated['icecat_supplier_id'] ?? null,
        ]);

        return redirect()->route('admin.brands.index')
            ->with('success', 'Brand updated.');
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        // Nullify brand_id on all products (FK is already nullOnDelete, but be explicit)
        $brand->products()->update(['brand_id' => null]);
        $brand->delete();

        return redirect()->route('admin.brands.index')
            ->with('success', 'Brand deleted.');
    }

    /**
     * POST /admin/brands/import-from-icecat
     * Imports brands from the Icecat supplier list.
     * Returns JSON {imported, skipped, brands[]}.
     */
    public function importFromIcecat(): JsonResponse
    {
        /** @var IcecatImportService $service */
        $service = app(IcecatImportService::class);
        $result  = $service->importBrands();

        return response()->json($result);
    }

    // -------------------------------------------------------------------------

    private function uniqueSlug(string $name, ?int $existingId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i    = 1;

        while (
            Brand::where('slug', $slug)
                ->when($existingId, fn ($q) => $q->where('id', '!=', $existingId))
                ->exists()
        ) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }
}
