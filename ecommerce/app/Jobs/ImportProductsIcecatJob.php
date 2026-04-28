<?php

namespace App\Jobs;

use App\Services\IcecatImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ImportProductsIcecatJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $category = 'all',
        public readonly int    $limit    = 20,
    ) {}

    public function handle(IcecatImportService $service): void
    {
        $service->run($this->category, $this->limit);
    }
}
