<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\IcecatImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * IMP-045 — Import products by Icecat Product ID or EAN/product code.
 *
 * TC-01  importByIds creates product with status=draft
 * TC-02  importByIds sets import_source = 'icecat'
 * TC-03  importByIds returns 'already_exists' when EAN already in DB
 * TC-04  importByIds returns 'failed' when Icecat returns 404
 * TC-05  fetchProductDetailByIcecatId calls ICECAT-ADD-ID param
 * TC-06  fetchProductDetailByIcecatId returns null on 404
 * TC-07  fetchProductDetailByIcecatId returns null when title missing
 * TC-08  fetchProductDetailByIcecatId rejects non-Icecat image URL
 * TC-09  POST /admin/icecat/import-by-id requires auth
 * TC-10  POST /admin/icecat/import-by-id requires admin role
 * TC-11  POST /admin/icecat/import-by-id validates ids required
 * TC-12  POST /admin/icecat/import-by-id rejects more than 20 IDs
 * TC-13  POST /admin/icecat/import-by-id rejects invalid characters
 * TC-14  POST /admin/icecat/import-by-id returns per-item results
 * TC-15  importByIds with numeric ID uses ICECAT-ADD-ID param (service level)
 * TC-16  admin products index page shows Import by ID button
 */
class IcecatImportByIdTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'user',  'guard_name' => 'web']);
    }

    private function makeAdmin(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('admin');
        return $user;
    }

    private function makeUser(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('user');
        return $user;
    }

    /** Fake a product detail API response (same structure used in IcecatImportTest). */
    private function fakeDetailResponse(
        string $title = 'Test Product X',
        string $image = 'https://images.icecat.us/img/test.jpg'
    ): array {
        return [
            'data' => [
                'GeneralInfo' => [
                    'Title'  => $title,
                    'Brand'  => 'TestBrand',
                    'SummaryDescription' => [
                        'LongSummaryDescription' => 'A test product description.',
                    ],
                    'Category'      => ['Name' => ['Value' => 'Electronics']],
                    'ProductFamily' => ['Value' => 'TestFamily'],
                ],
                'HighImg'        => $image,
                'FeaturesGroups' => [
                    [
                        'Features' => [
                            ['Feature' => ['Name' => ['Value' => 'Processor']], 'LocalValue' => ['Value' => 'ARM A55']],
                            ['Feature' => ['Name' => ['Value' => 'Weight']],    'LocalValue' => ['Value' => '250 g']],
                        ],
                    ],
                ],
            ],
        ];
    }

    // ── TC-01 ────────────────────────────────────────────────────────────────

    /** TC-01: importByIds creates a product with status=draft. */
    public function test_imp045_tc01_import_by_ids_creates_draft_product(): void
    {
        Http::fake([
            'live.icecat.biz/*' => Http::response($this->fakeDetailResponse(), 200),
        ]);

        $service = new IcecatImportService();
        $results = $service->importByIds(['5901234123457']);

        $this->assertCount(1, $results);
        $this->assertSame('imported', $results[0]['status']);
        $this->assertDatabaseHas('products', ['name' => 'Test Product X', 'status' => 'draft']);
    }

    // ── TC-02 ────────────────────────────────────────────────────────────────

    /** TC-02: importByIds sets import_source = 'icecat'. */
    public function test_imp045_tc02_import_by_ids_sets_import_source(): void
    {
        Http::fake([
            'live.icecat.biz/*' => Http::response($this->fakeDetailResponse(), 200),
        ]);

        (new IcecatImportService())->importByIds(['5901234123457']);

        $this->assertDatabaseHas('products', [
            'name'          => 'Test Product X',
            'import_source' => 'icecat',
        ]);
    }

    // ── TC-03 ────────────────────────────────────────────────────────────────

    /** TC-03: importByIds returns 'already_exists' when EAN is already in DB. */
    public function test_imp045_tc03_already_exists_when_sku_matches(): void
    {
        Product::factory()->create(['sku' => '5901234123457', 'name' => 'Existing Product']);

        Http::fake([
            'live.icecat.biz/*' => Http::response($this->fakeDetailResponse(), 200),
        ]);

        $service = new IcecatImportService();
        $results = $service->importByIds(['5901234123457']);

        $this->assertSame('already_exists', $results[0]['status']);
        $this->assertSame('Existing Product', $results[0]['name']);
        $this->assertCount(1, Product::all());
    }

    // ── TC-04 ────────────────────────────────────────────────────────────────

    /** TC-04: importByIds returns 'failed' when Icecat API returns 404. */
    public function test_imp045_tc04_failed_when_api_returns_404(): void
    {
        Http::fake([
            'live.icecat.biz/*' => Http::response([], 404),
        ]);

        $service = new IcecatImportService();
        $results = $service->importByIds(['0000000000000']);

        $this->assertSame('failed', $results[0]['status']);
        $this->assertStringContainsString('not found', strtolower($results[0]['error'] ?? ''));
    }

    // ── TC-05 ────────────────────────────────────────────────────────────────

    /** TC-05: fetchProductDetailByIcecatId uses ICECAT-ADD-ID in the API request. */
    public function test_imp045_tc05_fetch_by_icecat_id_uses_correct_param(): void
    {
        Http::fake([
            'live.icecat.biz/*' => Http::response($this->fakeDetailResponse('Gadget Pro'), 200),
        ]);

        $service = new IcecatImportService();
        $detail  = $service->fetchProductDetailByIcecatId(42322695);

        $this->assertNotNull($detail);
        $this->assertSame('Gadget Pro', $detail['name']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'ICECAT-ADD-ID=42322695');
        });
    }

    // ── TC-06 ────────────────────────────────────────────────────────────────

    /** TC-06: fetchProductDetailByIcecatId returns null on 404. */
    public function test_imp045_tc06_fetch_by_id_returns_null_on_404(): void
    {
        Http::fake([
            'live.icecat.biz/*' => Http::response([], 404),
        ]);

        $detail = (new IcecatImportService())->fetchProductDetailByIcecatId(99999);

        $this->assertNull($detail);
    }

    // ── TC-07 ────────────────────────────────────────────────────────────────

    /** TC-07: fetchProductDetailByIcecatId returns null when title is absent. */
    public function test_imp045_tc07_fetch_by_id_returns_null_when_no_title(): void
    {
        Http::fake([
            'live.icecat.biz/*' => Http::response(['data' => ['GeneralInfo' => []]], 200),
        ]);

        $detail = (new IcecatImportService())->fetchProductDetailByIcecatId(12345);

        $this->assertNull($detail);
    }

    // ── TC-08 ────────────────────────────────────────────────────────────────

    /** TC-08: fetchProductDetailByIcecatId rejects non-Icecat image URLs. */
    public function test_imp045_tc08_fetch_by_id_rejects_non_icecat_image(): void
    {
        Http::fake([
            'live.icecat.biz/*' => Http::response(
                $this->fakeDetailResponse(image: 'https://evil.com/img/hack.jpg'),
                200
            ),
        ]);

        $detail = (new IcecatImportService())->fetchProductDetailByIcecatId(12345);

        $this->assertNotNull($detail);
        $this->assertNull($detail['image']);
    }

    // ── TC-09 ────────────────────────────────────────────────────────────────

    /** TC-09: Unauthenticated user is redirected from the import-by-id endpoint. */
    public function test_imp045_tc09_guest_is_redirected(): void
    {
        $response = $this->postJson(route('admin.icecat.import-by-id'), ['ids' => '12345']);

        $response->assertUnauthorized();
    }

    // ── TC-10 ────────────────────────────────────────────────────────────────

    /** TC-10: Non-admin user gets 403 on the import-by-id endpoint. */
    public function test_imp045_tc10_non_admin_gets_403(): void
    {
        $response = $this->actingAs($this->makeUser())
            ->postJson(route('admin.icecat.import-by-id'), ['ids' => '12345']);

        $response->assertForbidden();
    }

    // ── TC-11 ────────────────────────────────────────────────────────────────

    /** TC-11: ids field is required. */
    public function test_imp045_tc11_ids_field_is_required(): void
    {
        $response = $this->actingAs($this->makeAdmin())
            ->postJson(route('admin.icecat.import-by-id'), []);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('ids');
    }

    // ── TC-12 ────────────────────────────────────────────────────────────────

    /** TC-12: More than 20 IDs returns a 422 error. */
    public function test_imp045_tc12_rejects_more_than_20_ids(): void
    {
        $ids = implode(',', range(1, 21));

        $response = $this->actingAs($this->makeAdmin())
            ->postJson(route('admin.icecat.import-by-id'), ['ids' => $ids]);

        $response->assertUnprocessable();
        $response->assertJson(['error' => 'Maximum 20 IDs per request.']);
    }

    // ── TC-13 ────────────────────────────────────────────────────────────────

    /** TC-13: An ID with special characters (e.g. spaces) returns a 422 error. */
    public function test_imp045_tc13_rejects_ids_with_invalid_chars(): void
    {
        $response = $this->actingAs($this->makeAdmin())
            ->postJson(route('admin.icecat.import-by-id'), ['ids' => 'valid, bad id!']);

        $response->assertUnprocessable();
        $response->assertJsonPath('error', fn ($msg) => str_contains($msg, 'Invalid'));
    }

    // ── TC-14 ────────────────────────────────────────────────────────────────

    /** TC-14: POST /admin/icecat/import-by-id returns per-item JSON results. */
    public function test_imp045_tc14_endpoint_returns_per_item_results(): void
    {
        Http::fake([
            'live.icecat.biz/*' => Http::response($this->fakeDetailResponse(), 200),
        ]);

        $response = $this->actingAs($this->makeAdmin())
            ->postJson(route('admin.icecat.import-by-id'), ['ids' => '5901234123457']);

        $response->assertOk();
        $response->assertJsonStructure([
            'results' => [
                ['input', 'name', 'status', 'error'],
            ],
        ]);
        $response->assertJsonPath('results.0.status', 'imported');
    }

    // ── TC-15 ────────────────────────────────────────────────────────────────

    /** TC-15: Numeric ID triggers fetchProductDetailByIcecatId via ICECAT-ADD-ID. */
    public function test_imp045_tc15_numeric_id_uses_icecat_add_id_param(): void
    {
        Http::fake([
            'live.icecat.biz/*' => Http::response($this->fakeDetailResponse(), 200),
        ]);

        (new IcecatImportService())->importByIds(['42322695']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'ICECAT-ADD-ID=42322695');
        });
    }

    // ── TC-16 ────────────────────────────────────────────────────────────────

    /** TC-16: Admin products index page shows Import by ID button. */
    public function test_imp045_tc16_index_page_shows_import_by_id_button(): void
    {
        $response = $this->actingAs($this->makeAdmin())
            ->get(route('admin.products.index'));

        $response->assertOk();
        $response->assertSee('Import by ID');
        $response->assertSee('icecatImportByIdModal');
    }
}
