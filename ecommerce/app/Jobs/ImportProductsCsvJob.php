<?php

namespace App\Jobs;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportProductsCsvJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $productImportId)
    {
    }

    public function handle(): void
    {
        $import = ProductImport::find($this->productImportId);
        if (!$import) {
            return;
        }

        $import->update([
            'status' => 'processing',
            'started_at' => now(),
            'errors' => [],
        ]);

        try {
            if (!Storage::disk('local')->exists($import->file_path)) {
                $import->update([
                    'status' => 'failed',
                    'errors' => [['row' => null, 'messages' => ['CSV file not found in storage.']]],
                    'finished_at' => now(),
                ]);
                return;
            }

            $path = Storage::path($import->file_path);
            $handle = fopen($path, 'r');
            if ($handle === false) {
                $import->update([
                    'status' => 'failed',
                    'errors' => [['row' => null, 'messages' => ['Unable to read CSV file.']]],
                    'finished_at' => now(),
                ]);
                return;
            }

            $expectedHeaders = ['name', 'description', 'price', 'stock', 'status', 'category'];
            $headers = $this->normalizeHeaders(fgetcsv($handle) ?: []);
            if ($headers !== $expectedHeaders) {
                fclose($handle);
                $import->update([
                    'status' => 'failed',
                    'errors' => [
                        [
                            'row' => 1,
                            'messages' => ['Invalid CSV headers. Expected: ' . implode(',', $expectedHeaders)],
                        ]
                    ],
                    'finished_at' => now(),
                ]);
                return;
            }

            $totalRows = 0;
            $successRows = 0;
            $failedRows = 0;
            $errors = [];
            $rowNumber = 1;

            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;

                if ($this->isEmptyRow($row)) {
                    continue;
                }

                $totalRows++;
                $data = $this->mapRow($headers, $row);
                $rowErrors = $this->validateRow($data);

                $categoryId = null;
                $categoryName = trim((string) ($data['category'] ?? ''));
                if ($categoryName !== '') {
                    $category = Category::query()
                        ->whereRaw('LOWER(name) = ?', [Str::lower($categoryName)])
                        ->first();
                    if (!$category) {
                        $rowErrors[] = 'category must reference an existing category name.';
                    } else {
                        $categoryId = $category->id;
                    }
                }

                if (!empty($rowErrors)) {
                    $failedRows++;
                    $errors[] = [
                        'row' => $rowNumber,
                        'messages' => $rowErrors,
                    ];
                    continue;
                }

                $name = trim((string) $data['name']);
                $baseSlug = Str::slug($name) ?: 'product';

                Product::create([
                    'name' => $name,
                    'slug' => $this->uniqueSlug($baseSlug),
                    'sku' => $this->uniqueSku(),
                    'description' => trim((string) ($data['description'] ?? '')) ?: null,
                    'price' => (float) $data['price'],
                    'stock' => (int) $data['stock'],
                    'category_id' => $categoryId,
                    'status' => Str::lower(trim((string) $data['status'])),
                    'images' => null,
                    'image' => null,
                ]);

                $successRows++;
            }

            fclose($handle);

            $import->update([
                'status' => 'completed',
                'total_rows' => $totalRows,
                'success_rows' => $successRows,
                'failed_rows' => $failedRows,
                'errors' => $errors,
                'finished_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $import->update([
                'status' => 'failed',
                'errors' => [['row' => null, 'messages' => ['Unexpected import failure: ' . $e->getMessage()]]],
                'finished_at' => now(),
            ]);
        }
    }

    private function normalizeHeaders(array $headers): array
    {
        return array_map(fn($value) => Str::of((string) $value)->trim()->lower()->value(), $headers);
    }

    private function mapRow(array $headers, array $row): array
    {
        $row = array_pad($row, count($headers), null);
        $mapped = array_combine($headers, array_slice($row, 0, count($headers)));

        return $mapped === false ? [] : $mapped;
    }

    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function validateRow(array $data): array
    {
        $errors = [];

        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            $errors[] = 'name is required.';
        } elseif (mb_strlen($name) > 255) {
            $errors[] = 'name must not exceed 255 characters.';
        }

        $priceRaw = trim((string) ($data['price'] ?? ''));
        if ($priceRaw === '' || !is_numeric($priceRaw) || (float) $priceRaw < 0.01) {
            $errors[] = 'price must be numeric and >= 0.01.';
        }

        $stockRaw = trim((string) ($data['stock'] ?? ''));
        if ($stockRaw === '' || filter_var($stockRaw, FILTER_VALIDATE_INT) === false || (int) $stockRaw < 0) {
            $errors[] = 'stock must be an integer >= 0.';
        }

        $status = Str::lower(trim((string) ($data['status'] ?? '')));
        if (!in_array($status, ['draft', 'published'], true)) {
            $errors[] = 'status must be draft or published.';
        }

        return $errors;
    }

    private function uniqueSlug(string $base): string
    {
        $slug = $base;
        $count = 1;

        while (Product::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $count++;
        }

        return $slug;
    }

    private function uniqueSku(): string
    {
        do {
            $sku = 'CSV-' . Str::upper(Str::random(10));
        } while (Product::where('sku', $sku)->exists());

        return $sku;
    }
}
