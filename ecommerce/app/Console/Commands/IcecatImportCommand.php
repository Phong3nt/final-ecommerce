<?php

namespace App\Console\Commands;

use App\Jobs\ImportProductsIcecatJob;
use App\Services\IcecatImportService;
use Illuminate\Console\Command;

class IcecatImportCommand extends Command
{
    protected $signature = 'icecat:import
                            {--category=all : Category name or "all" (e.g. Laptops)}
                            {--limit=20 : Number of EANs to fetch per category (max 50)}
                            {--dry-run : Preview EANs without writing anything to the database}';

    protected $description = 'Import products from the Icecat product content API (IMP-038)';

    public function handle(IcecatImportService $service): int
    {
        $category = $this->option('category');
        $limit    = (int) min((int) $this->option('limit'), 50);
        $dryRun   = $this->option('dry-run');

        $this->info(
            "Icecat import — category={$category} limit={$limit}"
            . ($dryRun ? ' [DRY RUN — nothing will be written]' : '')
        );

        if ($dryRun) {
            $this->warn('Dry-run: fetching EANs only, no DB writes.');
            $catName = $category === 'all' ? 'Laptops' : $category;
            $eans    = $service->fetchEans($catName, $limit);

            if (empty($eans)) {
                $this->warn("No EANs returned for category: {$catName}");
                return self::SUCCESS;
            }

            $this->table(
                ['EAN', 'Icecat ID', 'Name'],
                array_map(fn ($e) => [$e['ean'], $e['icecat_product_id'], $e['name']], $eans)
            );

            return self::SUCCESS;
        }

        // Dispatch as a queued job so it doesn't block the CLI process.
        ImportProductsIcecatJob::dispatch($category, $limit);
        $this->info('Import job dispatched. Check admin notifications for results.');

        return self::SUCCESS;
    }
}
