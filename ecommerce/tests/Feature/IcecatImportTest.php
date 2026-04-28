<?php

namespace Tests\Feature;

use App\Jobs\ImportProductsIcecatJob;
use App\Models\AdminNotification;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\IcecatImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * IMP-038 — Icecat API auto-import pipeline.
 *
 * TC-01  IcecatImportService.fetchEans parses API response
 * TC-02  IcecatImportService.fetchProductDetail maps fields correctly
 * TC-03  fetchProductDetail returns null for 404
 * TC-04  fetchProductDetail returns null for missing title
 * TC-05  Image URL validation rejects non-Icecat domains
 * TC-06  Image URL validation accepts valid Icecat domains
 * TC-07  service.run creates Product with draft status
 * TC-08  service.run sets import_source = 'icecat'
 * TC-09  service.run sets is_icecat_locked = true
 * TC-10  service.run stock is between 50 and 100
 * TC-11  service.run creates AdminNotification on completion
 * TC-12  service.run deduplicates — updates existing product instead of inserting
 * TC-13  ImportProductsIcecatJob implements ShouldQueue
 * TC-14  Admin POST /admin/icecat/import dispatches job(s)
 * TC-15  Admin POST /admin/icecat/import requires auth + admin role
 * TC-16  Admin POST /admin/icecat/import validates categories required
 * TC-17  Admin POST /admin/icecat/import validates limit max 50
 * TC-18  artisan icecat:import command dispatches ImportProductsIcecatJob
 */
class IcecatImportTest extends TestCase
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

    // ── Helpers ──────────────────────────────────────────────────────────────

    /** Fake a category search API response. */
    private function fakeSearchResponse(array $items = []): array
    {
        return ['data' => $items ?: [['EAN' => '5901234123457', 'Prod_id' => 'p1', 'Title' => 'Test Laptop']]];
    }

    /** Fake a product detail API response for a given EAN. */
    private function fakeDetailResponse(
        string $title = 'Test Laptop Pro',
        string $brand = 'TestBrand',
        string $family = 'ProLine',
        string $image = 'https://images.icecat.us/img/test.jpg'
    ): array {
        return [
            'data' => [
                'GeneralInfo' => [
                    'Title'  => $title,
                    'Brand'  => $brand,
                    'SummaryDescription' => [
                        'LongSummaryDescription' => 'Long description text.',
                    ],
                    'Category' => ['Name' => ['Value' => 'Laptops']],
                    'ProductFamily' => ['Value' => $family],
                ],
                'HighImg'       => $image,
                'FeaturesGroups' => [
                    [
                        'Features' => [
                            ['Feature' => ['Name' => ['Value' => 'Processor']], 'LocalValue' => ['Value' => 'Intel Core i7']],
                            ['Feature' => ['Name' => ['Value' => 'Display size']], 'LocalValue' => ['Value' => '15.6"']],
                            ['Feature' => ['Name' => ['Value' => 'Weight']], 'LocalValue' => ['Value' => '1.8 kg']],
                            ['Feature' => ['Name' => ['Value' => 'Storage']], 'LocalValue' => ['Value' => '512 GB SSD']],
                            ['Feature' => ['Name' => ['Value' => 'Color']], 'LocalValue' => ['Value' => 'Silver']],
                        ],
                    ],
                ],
            ],
        ];
    }

    // ── TC-01 ────────────────────────────────────────────────────────────────

    /** TC-01: fetchEans returns parsed EAN list from API response. */
    public function test_imp038_tc01_fetch_eans_parses_response(): void
    {
        Http::fake([
            'live.icecat.biz/*' => Http::response($this->fakeSearchResponse(), 200),
        ]);

        $service = new IcecatImportService();
        $eans    = $service->fetchEans('Laptops', 5);

        $this->assertNotEmpty($eans);
        $this->assertArrayHasKey('ean', $eans[0]);
        $this->assertSame('5901234123457', $eans[0]['ean']);
    }

    // ── TC-02 ────────────────────────────────────────────────────────────────

    /** TC-02: fetchProductDetail maps Icecat response to expected field structure. */
    public function test_imp038_tc02_fetch_product_detail_maps_fields(): void
    {
        Http::fake([
            'live.icecat.biz/*' => Http::response($this->fakeDetailResponse(), 200),
        ]);

        $service = new IcecatImportService();
        $detail  = $service->fetchProductDetail('5901234123457', 'Laptops');

        $this->assertNotNull($detail);
        $this->assertSame('Test Laptop Pro', $detail['name']);
        $this->assertSame('Intel Core i7', $detail['spec_processor']);
        $this->assertSame('15.6"', $detail['spec_display']);
        $this->assertSame('1.8 kg', $detail['spec_weight']);
        $this->assertSame('Silver', $detail['color']);
        $this->assertSame('512 GB SSD', $detail['storage']);
        $this->assertSame('https://images.icecat.us/img/test.jpg', $detail['image']);
    }

    // ── TC-03 ────────────────────────────────────────────────────────────────

    /** TC-03: fetchProductDetail returns null when API returns 404. */
    public function test_imp038_tc03_fetch_product_detail_returns_null_on_404(): void
    {
        Http::fake([
            'live.icecat.biz/*' => Http::response([], 404),
        ]);

        $service = new IcecatImportService();
        $detail  = $service->fetchProductDetail('0000000000000', 'Laptops');

        $this->assertNull($detail);
    }

    // ── TC-04 ────────────────────────────────────────────────────────────────

    /** TC-04: fetchProductDetail returns null when title is missing. */
    public function test_imp038_tc04_fetch_product_detail_null_when_title_missing(): void
    {
        Http::fake([
            'live.icecat.biz/*' => Http::response(['data' => ['GeneralInfo' => []]], 200),
        ]);

        $service = new IcecatImportService();
        $detail  = $service->fetchProductDetail('1234567890123', 'Laptops');

        $this->assertNull($detail);
    }

    // ── TC-05 ────────────────────────────────────────────────────────────────

    /** TC-05: Image URLs from non-Icecat domains are rejected (set to null). */
    public function test_imp038_tc05_rejects_non_icecat_image_url(): void
    {
        $badImage = 'https://evil.com/img/malware.jpg';

        Http::fake([
            'live.icecat.biz/*' => Http::response($this->fakeDetailResponse(image: $badImage), 200),
        ]);

        $service = new IcecatImportService();
        $detail  = $service->fetchProductDetail('5901234123457', 'Laptops');

        $this->assertNotNull($detail);
        $this->assertNull($detail['image'], 'Non-Icecat image URL must be rejected');
    }

    // ── TC-06 ────────────────────────────────────────────────────────────────

    /** TC-06: Valid Icecat image URLs (icecat.biz domain) are accepted. */
    public function test_imp038_tc06_accepts_valid_icecat_image_url(): void
    {
        $validImage = 'https://images.icecat.biz/img/product.jpg';

        Http::fake([
            'live.icecat.biz/*' => Http::response($this->fakeDetailResponse(image: $validImage), 200),
        ]);

        $service = new IcecatImportService();
        $detail  = $service->fetchProductDetail('5901234123457', 'Laptops');

        $this->assertNotNull($detail);
        $this->assertSame($validImage, $detail['image']);
    }

    // ── TC-07 ────────────────────────────────────────────────────────────────

    /** TC-07: service.run creates a Product with status='draft'. */
    public function test_imp038_tc07_run_creates_product_with_draft_status(): void
    {
        Http::fake([
            'live.icecat.biz/*' => Http::sequence()
                ->push($this->fakeSearchResponse(), 200)
                ->push($this->fakeDetailResponse(), 200),
        ]);

        $service = new IcecatImportService();
        $service->run('Laptops', 1);

        $this->assertDatabaseHas('products', [
            'name'   => 'Test Laptop Pro',
            'status' => 'draft',
        ]);
    }

    // ── TC-08 ────────────────────────────────────────────────────────────────

    /** TC-08: Imported product has import_source = 'icecat'. */
    public function test_imp038_tc08_run_sets_import_source_icecat(): void
    {
        Http::fake([
            'live.icecat.biz/*' => Http::sequence()
                ->push($this->fakeSearchResponse(), 200)
                ->push($this->fakeDetailResponse(), 200),
        ]);

        (new IcecatImportService())->run('Laptops', 1);

        $product = Product::where('name', 'Test Laptop Pro')->first();
        $this->assertNotNull($product);
        $this->assertSame('icecat', $product->import_source);
    }

    // ── TC-09 ────────────────────────────────────────────────────────────────

    /** TC-09: Imported product has is_icecat_locked = true. */
    public function test_imp038_tc09_run_sets_is_icecat_locked(): void
    {
        Http::fake([
            'live.icecat.biz/*' => Http::sequence()
                ->push($this->fakeSearchResponse(), 200)
                ->push($this->fakeDetailResponse(), 200),
        ]);

        (new IcecatImportService())->run('Laptops', 1);

        $product = Product::where('name', 'Test Laptop Pro')->first();
        $this->assertNotNull($product);
        $this->assertTrue($product->is_icecat_locked);
    }

    // ── TC-10 ────────────────────────────────────────────────────────────────

    /** TC-10: Imported product stock is between 50 and 100. */
    public function test_imp038_tc10_run_stock_between_50_and_100(): void
    {
        Http::fake([
            'live.icecat.biz/*' => Http::sequence()
                ->push($this->fakeSearchResponse(), 200)
                ->push($this->fakeDetailResponse(), 200),
        ]);

        (new IcecatImportService())->run('Laptops', 1);

        $product = Product::where('name', 'Test Laptop Pro')->first();
        $this->assertNotNull($product);
        $this->assertGreaterThanOrEqual(50, $product->stock);
        $this->assertLessThanOrEqual(100, $product->stock);
    }

    // ── TC-11 ────────────────────────────────────────────────────────────────

    /** TC-11: service.run creates an AdminNotification on completion. */
    public function test_imp038_tc11_run_creates_admin_notification(): void
    {
        Http::fake([
            'live.icecat.biz/*' => Http::sequence()
                ->push($this->fakeSearchResponse(), 200)
                ->push($this->fakeDetailResponse(), 200),
        ]);

        (new IcecatImportService())->run('Laptops', 1);

        $notification = AdminNotification::where('order_id', null)
            ->where('message', 'like', '%Icecat import complete%')
            ->first();

        $this->assertNotNull($notification);
        $this->assertStringContainsString('Icecat import complete', $notification->message);
    }

    // ── TC-12 ────────────────────────────────────────────────────────────────

    /** TC-12: Running import twice for the same product updates rather than duplicating. */
    public function test_imp038_tc12_run_deduplicates_products(): void
    {
        Http::fake([
            'live.icecat.biz/*' => Http::sequence()
                ->push($this->fakeSearchResponse(), 200)
                ->push($this->fakeDetailResponse(), 200)
                ->push($this->fakeSearchResponse(), 200)
                ->push($this->fakeDetailResponse(), 200),
        ]);

        $service = new IcecatImportService();
        $service->run('Laptops', 1);
        $service->run('Laptops', 1);

        $this->assertSame(1, Product::where('name', 'Test Laptop Pro')->count());
    }

    // ── TC-13 ────────────────────────────────────────────────────────────────

    /** TC-13: ImportProductsIcecatJob implements ShouldQueue. */
    public function test_imp038_tc13_job_implements_should_queue(): void
    {
        $this->assertInstanceOf(
            \Illuminate\Contracts\Queue\ShouldQueue::class,
            new ImportProductsIcecatJob()
        );
    }

    // ── TC-14 ────────────────────────────────────────────────────────────────

    /** TC-14: POST /admin/icecat/import as admin dispatches the job. */
    public function test_imp038_tc14_admin_post_dispatches_job(): void
    {
        Queue::fake();
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->postJson(route('admin.icecat.import'), [
                'categories' => ['Laptops', 'Tablets'],
                'limit'      => 10,
            ])
            ->assertStatus(200)
            ->assertJsonFragment(['message' => 'Import queued. Check Notifications for results.']);

        Queue::assertPushed(ImportProductsIcecatJob::class, 2);
    }

    // ── TC-15 ────────────────────────────────────────────────────────────────

    /** TC-15: Guest and regular user cannot access the Icecat import endpoint. */
    public function test_imp038_tc15_non_admin_cannot_trigger_import(): void
    {
        Queue::fake();

        // Guest — postJson sets Accept:application/json so auth middleware returns 401 (not 302)
        $this->postJson(route('admin.icecat.import'), ['categories' => ['Laptops'], 'limit' => 5])
            ->assertStatus(401);

        // Regular user
        $user = $this->makeUser();
        $this->actingAs($user)
            ->postJson(route('admin.icecat.import'), ['categories' => ['Laptops'], 'limit' => 5])
            ->assertStatus(403);

        Queue::assertNothingPushed();
    }

    // ── TC-16 ────────────────────────────────────────────────────────────────

    /** TC-16: categories field is required; empty array returns 422. */
    public function test_imp038_tc16_validates_categories_required(): void
    {
        Queue::fake();
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->postJson(route('admin.icecat.import'), ['categories' => [], 'limit' => 10])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['categories']);
    }

    // ── TC-17 ────────────────────────────────────────────────────────────────

    /** TC-17: limit > 50 is rejected with 422. */
    public function test_imp038_tc17_validates_limit_max_50(): void
    {
        Queue::fake();
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->postJson(route('admin.icecat.import'), ['categories' => ['Laptops'], 'limit' => 99])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['limit']);
    }

    // ── TC-18 ────────────────────────────────────────────────────────────────

    /** TC-18: php artisan icecat:import dispatches ImportProductsIcecatJob. */
    public function test_imp038_tc18_artisan_command_dispatches_job(): void
    {
        Queue::fake();

        $this->artisan('icecat:import', ['--category' => 'Laptops', '--limit' => '5'])
            ->assertExitCode(0);

        Queue::assertPushed(ImportProductsIcecatJob::class, function ($job) {
            return $job->category === 'Laptops' && $job->limit === 5;
        });
    }

    // ── TC-19 (IMP-043) ──────────────────────────────────────────────────────

    /** TC-19: Re-import skips a published product entirely — status and stock unchanged. */
    public function test_imp043_tc19_skips_published_product_on_reimport(): void
    {
        $category = Category::firstOrCreate(['name' => 'Laptops'], ['slug' => 'laptops']);
        Product::factory()->create([
            'name'          => 'Test Laptop Pro',
            'sku'           => '5901234123457',
            'status'        => 'published',
            'stock'         => 42,
            'category_id'   => $category->id,
            'import_source' => 'icecat',
        ]);

        Http::fake([
            'live.icecat.biz/*' => Http::sequence()
                ->push($this->fakeSearchResponse(), 200)
                ->push($this->fakeDetailResponse(), 200),
        ]);

        $result = (new IcecatImportService())->run('Laptops', 1);

        // Product must not be overwritten
        $product = Product::where('sku', '5901234123457')->first();
        $this->assertNotNull($product);
        $this->assertSame('published', $product->status);
        $this->assertSame(42, $product->stock);

        // Report must reflect the skip
        $this->assertSame(1, $result['skipped_published_count']);
    }

    // ── TC-20 (IMP-043) ──────────────────────────────────────────────────────

    /** TC-20: Re-import updates draft product fields but preserves existing stock. */
    public function test_imp043_tc20_updates_draft_but_preserves_stock(): void
    {
        $category = Category::firstOrCreate(['name' => 'Laptops'], ['slug' => 'laptops']);
        Product::factory()->create([
            'name'          => 'Test Laptop Pro',
            'sku'           => '5901234123457',
            'status'        => 'draft',
            'stock'         => 77,
            'category_id'   => $category->id,
            'import_source' => 'icecat',
        ]);

        Http::fake([
            'live.icecat.biz/*' => Http::sequence()
                ->push($this->fakeSearchResponse(), 200)
                ->push($this->fakeDetailResponse(), 200),
        ]);

        $result = (new IcecatImportService())->run('Laptops', 1);

        // Fields should be updated
        $product = Product::where('sku', '5901234123457')->first();
        $this->assertNotNull($product);
        $this->assertSame('icecat', $product->import_source);

        // Stock must be preserved
        $this->assertSame(77, $product->stock);

        // Only one product — no duplicate created
        $this->assertSame(1, Product::where('sku', '5901234123457')->count());

        // Report must reflect the update
        $this->assertSame(1, $result['updated_draft_count']);
    }

    // ── TC-21 (IMP-043) ──────────────────────────────────────────────────────

    /** TC-21: Import creates a new product for an EAN not present in the DB. */
    public function test_imp043_tc21_creates_new_product_for_unknown_ean(): void
    {
        // Confirm no product with this EAN exists yet
        $this->assertDatabaseMissing('products', ['sku' => '5901234123457']);

        Http::fake([
            'live.icecat.biz/*' => Http::sequence()
                ->push($this->fakeSearchResponse(), 200)
                ->push($this->fakeDetailResponse(), 200),
        ]);

        $result = (new IcecatImportService())->run('Laptops', 1);

        $this->assertDatabaseHas('products', ['sku' => '5901234123457', 'status' => 'draft']);

        // Report must reflect the creation
        $this->assertSame(1, $result['new_count']);
    }

    // ── TC-22 (IMP-043) ──────────────────────────────────────────────────────

    /**
     * TC-22: run() summary reports correct new / updated_draft / skipped_published counts.
     *
     * Setup: one published + one draft product already in DB, one completely new EAN in import.
     */
    public function test_imp043_tc22_summary_reports_correct_counts(): void
    {
        $category = Category::firstOrCreate(['name' => 'Laptops'], ['slug' => 'laptops']);

        // Published — should be skipped
        Product::factory()->create([
            'name'        => 'Pub Laptop',
            'sku'         => 'EAN-PUBLISHED',
            'status'      => 'published',
            'stock'       => 10,
            'category_id' => $category->id,
        ]);

        // Draft — should be updated, stock preserved
        Product::factory()->create([
            'name'        => 'Draft Laptop',
            'sku'         => 'EAN-DRAFT',
            'status'      => 'draft',
            'stock'       => 55,
            'category_id' => $category->id,
        ]);

        // Fake API: three EAN search results, three detail responses
        $searchItems = [
            ['EAN' => 'EAN-PUBLISHED', 'Prod_id' => 'p1', 'Title' => 'Pub Laptop'],
            ['EAN' => 'EAN-DRAFT',     'Prod_id' => 'p2', 'Title' => 'Draft Laptop'],
            ['EAN' => 'EAN-NEW',       'Prod_id' => 'p3', 'Title' => 'New Laptop'],
        ];

        Http::fake([
            'live.icecat.biz/*' => Http::sequence()
                ->push(['data' => $searchItems], 200)
                ->push($this->fakeDetailResponse('Pub Laptop',   'HP',  'PubLine',   'https://images.icecat.us/img/p1.jpg'), 200)
                ->push($this->fakeDetailResponse('Draft Laptop', 'HP',  'DraftLine', 'https://images.icecat.us/img/p2.jpg'), 200)
                ->push($this->fakeDetailResponse('New Laptop',   'HP',  'NewLine',   'https://images.icecat.us/img/p3.jpg'), 200),
        ]);

        $result = (new IcecatImportService())->run('Laptops', 3);

        $this->assertSame(1, $result['new_count'],               'new_count mismatch');
        $this->assertSame(1, $result['updated_draft_count'],     'updated_draft_count mismatch');
        $this->assertSame(1, $result['skipped_published_count'], 'skipped_published_count mismatch');

        // Draft product stock must still be 55
        $draft = Product::where('sku', 'EAN-DRAFT')->first();
        $this->assertSame(55, $draft->stock);
    }
}
