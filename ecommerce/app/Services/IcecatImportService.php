<?php

namespace App\Services;

use App\Models\AdminNotification;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class IcecatImportService
{
    private const BASE_URL = 'https://live.icecat.us/api';

    private const CATEGORY_MAP = [
        'Laptops'               => 151,
        'Smartphones'           => 160,
        'Smartwatches'          => 8394,
        'Tablets'               => 32,
        'Headphones & Headsets' => 168,
        'Battery Chargers'      => 2072,
        'Electronics'           => 1,
    ];

    private const CATEGORY_PRICE_RANGE = [
        'Laptops'               => [499, 1999],
        'Smartphones'           => [199, 999],
        'Smartwatches'          => [149, 599],
        'Tablets'               => [249, 899],
        'Headphones & Headsets' => [29, 399],
        'Battery Chargers'      => [19, 149],
        'Electronics'           => [49, 499],
    ];

    /**
     * Run the full import pipeline.
     *
     * @return array{attempted: int, succeeded: int, skipped: int}
     */
    public function run(string $category = 'all', int $limit = 20): array
    {
        $categories = $category === 'all'
            ? array_keys(self::CATEGORY_MAP)
            : [$category];

        $totalAttempted = 0;
        $totalSucceeded = 0;
        $totalSkipped   = 0;

        foreach ($categories as $cat) {
            $eans       = $this->fetchEans($cat, $limit);
            $rawProducts = [];

            foreach ($eans as $eanData) {
                $totalAttempted++;
                $detail = $this->fetchProductDetail($eanData['ean'] ?? '', $cat);

                if ($detail === null) {
                    $totalSkipped++;
                    continue;
                }

                $rawProducts[] = $detail;
                $totalSucceeded++;
            }

            $groups = $this->groupByFamily($rawProducts);
            $this->upsertGroups($groups, $cat);
        }

        $summary = [
            'attempted' => $totalAttempted,
            'succeeded' => $totalSucceeded,
            'skipped'   => $totalSkipped,
        ];

        AdminNotification::create([
            'order_id' => null,
            'message'  => "Icecat import complete: {$totalSucceeded}/{$totalAttempted} products imported, {$totalSkipped} skipped.",
        ]);

        return $summary;
    }

    /**
     * Step 0 — Fetch EANs for a category from the Icecat Search API.
     *
     * @return array<int, array{ean: string, icecat_product_id: string, name: string}>
     */
    public function fetchEans(string $categoryName, int $limit = 20): array
    {
        $catId    = self::CATEGORY_MAP[$categoryName] ?? 1;
        $response = $this->apiGet([
            'Language'   => 'EN',
            'Content'    => 'Product',
            'Brand'      => '',
            'CategoryId' => $catId,
            'Offset'     => 0,
            'Limit'      => $limit,
        ]);

        if (! $response->successful()) {
            Log::channel('icecat')->warning(
                "[SKIP] category={$categoryName} reason=search_api_failed status={$response->status()}"
            );
            // Fallback: keyword search
            $response = $this->apiGet([
                'Language'   => 'EN',
                'Content'    => 'Product',
                'FullSearch' => $categoryName,
                'Limit'      => $limit,
            ]);
        }

        if (! $response->successful()) {
            Log::channel('icecat')->warning("[SKIP] category={$categoryName} reason=fallback_search_failed");
            return [];
        }

        $body  = $response->json();
        $items = $body['data'] ?? $body['items'] ?? $body['products'] ?? [];

        if (! is_array($items)) {
            return [];
        }

        $result = [];
        foreach ($items as $item) {
            $ean = $item['EAN'] ?? ($item['Eans'][0] ?? '');
            if (empty($ean)) {
                continue;
            }
            $result[] = [
                'ean'               => $ean,
                'icecat_product_id' => $item['Prod_id'] ?? ($item['ProductID'] ?? ''),
                'name'              => $item['Title'] ?? ($item['ShortSummaryDescription'] ?? ''),
            ];
        }

        return $result;
    }

    /**
     * Step 1 — Fetch full product details for a single EAN.
     */
    public function fetchProductDetail(string $ean, string $categoryName = 'Electronics'): ?array
    {
        if (empty($ean)) {
            Log::channel('icecat')->info('[SKIP] EAN= reason=empty_ean');
            return null;
        }

        $response = $this->apiGet([
            'Language' => 'EN',
            'GTIN'     => $ean,
        ]);

        if ($response->status() === 404) {
            Log::channel('icecat')->info("[SKIP] EAN={$ean} reason=404_not_found");
            return null;
        }

        if (! $response->successful()) {
            Log::channel('icecat')->warning(
                "[SKIP] EAN={$ean} reason=api_error status={$response->status()}"
            );
            return null;
        }

        $body    = $response->json();
        $product = $body['data'] ?? $body;

        $title = $product['GeneralInfo']['Title']
            ?? ($product['Title'] ?? null);

        if (empty($title)) {
            Log::channel('icecat')->info("[SKIP] EAN={$ean} reason=missing_title");
            return null;
        }

        $description = $product['GeneralInfo']['SummaryDescription']['LongSummaryDescription']
            ?? ($product['GeneralInfo']['SummaryDescription']['ShortSummaryDescription']
                ?? ($product['LongDesc']
                    ?? ($product['ShortDesc'] ?? '')));

        $image = $product['Gallery'][0]['Pic']
            ?? ($product['HighImg']
                ?? ($product['LowImg'] ?? null));

        if ($image !== null && ! $this->isValidIcecatImageUrl($image)) {
            $image = null;
        }

        $specs  = $this->extractSpecs($product);
        $family = $product['GeneralInfo']['ProductFamily']['Value']
            ?? ($product['Series'] ?? '');

        $categoryFromApi = $product['GeneralInfo']['Category']['Name']['Value']
            ?? $categoryName;

        return [
            'ean'            => $ean,
            'name'           => $title,
            'description'    => $description,
            'image'          => $image,
            'brand'          => $product['GeneralInfo']['Brand'] ?? ($product['Brand']['Name'] ?? ''),
            'family'         => $family,
            'category_name'  => $categoryFromApi,
            'color'          => $specs['color'],
            'storage'        => $specs['storage'],
            'spec_processor' => $specs['processor'],
            'spec_display'   => $specs['display'],
            'spec_weight'    => $specs['weight'],
            'price'          => $this->resolvePrice($product, $categoryName),
        ];
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function extractSpecs(array $product): array
    {
        $specs = [
            'color'     => null,
            'storage'   => null,
            'processor' => null,
            'display'   => null,
            'weight'    => null,
        ];

        $featuresGroups = $product['FeaturesGroups'] ?? [];

        foreach ($featuresGroups as $group) {
            foreach ($group['Features'] ?? [] as $feature) {
                $name  = strtolower($feature['Feature']['Name']['Value'] ?? '');
                $value = $feature['LocalValue']['Value'] ?? ($feature['Value'] ?? '');

                if (str_contains($name, 'color') || str_contains($name, 'colour')) {
                    $specs['color'] = $value;
                } elseif (
                    str_contains($name, 'storage')
                    || str_contains($name, 'memory')
                    || str_contains($name, 'ssd')
                    || str_contains($name, 'hdd')
                ) {
                    $specs['storage'] = $value;
                } elseif (
                    str_contains($name, 'processor')
                    || str_contains($name, 'cpu')
                    || str_contains($name, 'chip')
                ) {
                    $specs['processor'] = $value;
                } elseif (
                    str_contains($name, 'display')
                    || str_contains($name, 'screen')
                    || str_contains($name, 'diagonal')
                ) {
                    $specs['display'] = $value;
                } elseif (str_contains($name, 'weight') || str_contains($name, 'mass')) {
                    $specs['weight'] = $value;
                }
            }
        }

        return $specs;
    }

    private function resolvePrice(array $product, string $categoryName): float
    {
        $msrp = $product['MSRP'] ?? ($product['SuggestedRetailPrice'] ?? null);

        if (is_numeric($msrp) && $msrp > 0) {
            return (float) $msrp;
        }

        $range = self::CATEGORY_PRICE_RANGE[$categoryName] ?? [49, 499];
        return (float) rand($range[0] * 100, $range[1] * 100) / 100;
    }

    /** Step 2 — Group records by ProductFamily into parent-child sets. */
    private function groupByFamily(array $rawProducts): array
    {
        $groups = [];

        foreach ($rawProducts as $product) {
            $key            = ! empty($product['family']) ? $product['family'] : ('__solo__' . $product['ean']);
            $groups[$key][] = $product;
        }

        return $groups;
    }

    /** Step 3 — Upsert product groups into the DB. */
    private function upsertGroups(array $groups, string $fallbackCategoryName): void
    {
        foreach ($groups as $familyProducts) {
            $first        = $familyProducts[0];
            $categoryName = ! empty($first['category_name']) ? $first['category_name'] : $fallbackCategoryName;

            $category = Category::firstOrCreate(
                ['name' => $categoryName],
                ['slug' => Str::slug($categoryName)]
            );

            $parentName = $this->stripVariantSuffix($first['name']);

            // Step 3: duplicate check
            $existing = Product::where('name', $parentName)
                ->where('category_id', $category->id)
                ->first();

            // Step 4: operation presets applied in productData
            $productData = [
                'name'                => $parentName,
                'slug'                => $this->uniqueSlug($parentName, $existing?->id),
                'description'         => $first['description'],
                'image'               => $first['image'],
                'category_id'         => $category->id,
                'price'               => $first['price'],
                'stock'               => rand(50, 100),
                'status'              => 'draft',
                'spec_processor'      => $first['spec_processor'],
                'spec_display'        => $first['spec_display'],
                'spec_weight'         => $first['spec_weight'],
                'is_icecat_locked'    => true,
                'import_source'       => 'icecat',
                'sku'                 => $first['ean'],
                'low_stock_threshold' => 10,
                'low_stock_notified'  => false,
            ];

            if ($existing) {
                $existing->update($productData);
            } else {
                Product::create($productData);
            }
        }
    }

    /** Strip common color/storage variant suffixes from product names. */
    private function stripVariantSuffix(string $name): string
    {
        return trim(
            preg_replace(
                '/\s*[-–]\s*([\w\s]+\d+\s*[GT]B|black|white|silver|gold|blue|red|green)\s*$/i',
                '',
                $name
            )
        );
    }

    private function uniqueSlug(string $name, ?int $existingId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i    = 1;

        while (
            Product::where('slug', $slug)
                ->when($existingId, fn ($q) => $q->where('id', '!=', $existingId))
                ->exists()
        ) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }

    /**
     * Only accept image URLs from Icecat-owned domains over HTTPS.
     * Security: prevents storing arbitrary external URLs.
     */
    private function isValidIcecatImageUrl(string $url): bool
    {
        return str_starts_with($url, 'https://')
            && (str_contains($url, 'icecat.us') || str_contains($url, 'icecat.biz'));
    }

    private function apiGet(array $params): \Illuminate\Http\Client\Response
    {
        $params['UserName'] = config('services.icecat.username');

        return Http::withBasicAuth(
            config('services.icecat.username'),
            config('services.icecat.password')
        )->timeout(30)->get(self::BASE_URL, $params);
    }
}
