<?php

namespace App\Jobs;

use App\Services\IcecatImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * IMP-044 — Sync existing products from Icecat.
 * Dispatched by IcecatController::sync() and processed by IcecatImportService::sync().
 */
class SyncProductsIcecatJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $category = 'all',
        public readonly int    $limit    = 20,
    ) {}

    public function handle(IcecatImportService $service): void
    {
        $service->sync($this->category, $this->limit);
    }
}
