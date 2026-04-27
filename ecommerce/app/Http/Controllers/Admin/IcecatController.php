<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ImportProductsIcecatJob;
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
}
