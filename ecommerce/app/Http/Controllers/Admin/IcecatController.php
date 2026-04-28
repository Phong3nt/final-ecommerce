<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ImportProductsIcecatJob;
use App\Jobs\SyncProductsIcecatJob;
use App\Services\IcecatImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IcecatController extends Controller
{
    /**
     * POST /admin/icecat/import
     * Dispatches ImportProductsIcecatJob for the selected categories.
     */
    public function import(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'categories'   => ['required', 'array', 'min:1'],
            'categories.*' => ['required', 'string', 'max:64'],
            'limit'        => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $limit = (int) ($validated['limit'] ?? 20);

        foreach ($validated['categories'] as $category) {
            ImportProductsIcecatJob::dispatch($category, $limit);
        }

        return response()->json([
            'message' => 'Import queued. Check Notifications for results.',
        ]);
    }

    /**
     * POST /admin/icecat/sync   (IMP-044)
     * Dispatches SyncProductsIcecatJob for the selected categories.
     * Sync updates only name/description/specs/image for existing products;
     * never touches status, stock, or price.
     */
    public function sync(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'categories'   => ['required', 'array', 'min:1'],
            'categories.*' => ['required', 'string', 'max:64'],
            'limit'        => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $limit = (int) ($validated['limit'] ?? 20);

        foreach ($validated['categories'] as $category) {
            SyncProductsIcecatJob::dispatch($category, $limit);
        }

        return response()->json([
            'message' => 'Sync queued. Check Notifications for results.',
        ]);
    }

    /**
     * POST /admin/icecat/import-by-id   (IMP-045)
     * Synchronously imports up to 20 products by Icecat Product ID (integer)
     * or EAN / product code (alphanumeric). Returns per-item JSON results.
     */
    public function importById(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'string', 'max:2000'],
        ]);

        $idsOrCodes = array_values(array_filter(
            array_map('trim', explode(',', $validated['ids'])),
            fn ($v) => $v !== ''
        ));

        if (count($idsOrCodes) === 0) {
            return response()->json(['error' => 'No valid IDs provided.'], 422);
        }

        if (count($idsOrCodes) > 20) {
            return response()->json(['error' => 'Maximum 20 IDs per request.'], 422);
        }

        foreach ($idsOrCodes as $entry) {
            if (! preg_match('/^[A-Za-z0-9\-]+$/', $entry)) {
                return response()->json(['error' => "Invalid ID or code: {$entry}"], 422);
            }
        }

        /** @var IcecatImportService $service */
        $service = app(IcecatImportService::class);
        $results = $service->importByIds($idsOrCodes);

        return response()->json(['results' => $results]);
    }
}
