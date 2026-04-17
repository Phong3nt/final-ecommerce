<?php

namespace Tests\Feature;

use App\Jobs\ImportProductsCsvJob;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * PM-005 — As an admin, I want to import products via CSV so I can bulk-upload inventory.
 *
 * Acceptance criteria:
 *   - Validates CSV headers and data types
 *   - Errors reported per row
 *   - Runs as background job for large files
 */
class AdminProductImportCsvTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    }

    private function makeAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        return $admin;
    }

    private function makeUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('user');
        return $user;
    }

    private function csvFile(string $content, string $filename = 'products.csv'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($filename, $content);
    }

    private function csv(array $rows, ?array $headers = null): string
    {
        $headers = $headers ?? ['name', 'description', 'price', 'stock', 'status', 'category'];

        $lines = [implode(',', $headers)];
        foreach ($rows as $row) {
            $escaped = array_map(function ($value) {
                $value = (string) $value;
                if (str_contains($value, ',') || str_contains($value, '"')) {
                    return '"' . str_replace('"', '""', $value) . '"';
                }
                return $value;
            }, $row);

            $lines[] = implode(',', $escaped);
        }

        return implode("\n", $lines) . "\n";
    }

    // TC-01: Guest is redirected from CSV import endpoint
    public function test_pm005_guest_is_redirected_from_import_endpoint(): void
    {
        $response = $this->post(route('admin.products.import'));

        $response->assertRedirect(route('login'));
    }

    // TC-02: Non-admin gets 403 on CSV import endpoint
    public function test_pm005_non_admin_gets_403_on_import_endpoint(): void
    {
        $response = $this->actingAs($this->makeUser())
            ->post(route('admin.products.import'));

        $response->assertStatus(403);
    }

    // TC-03: Admin sees CSV import form on products index
    public function test_pm005_admin_sees_csv_import_form_on_index(): void
    {
        $response = $this->actingAs($this->makeAdmin())
            ->get(route('admin.products.index'));

        $response->assertStatus(200);
        $response->assertSee('Import Products via CSV');
        $response->assertSee('name="csv_file"', false);
    }

    // TC-04: CSV file is required
    public function test_pm005_csv_file_is_required(): void
    {
        $response = $this->actingAs($this->makeAdmin())
            ->from(route('admin.products.index'))
            ->post(route('admin.products.import'), []);

        $response->assertRedirect(route('admin.products.index'));
        $response->assertSessionHasErrors(['csv_file']);
    }

    // TC-05: Invalid CSV headers are rejected before queueing
    public function test_pm005_invalid_headers_are_rejected(): void
    {
        Storage::fake('local');
        Queue::fake();

        $csv = $this->csv([
            ['Wrong Product', '10.00', '5'],
        ], ['name', 'price', 'stock']);

        $response = $this->actingAs($this->makeAdmin())
            ->from(route('admin.products.index'))
            ->post(route('admin.products.import'), [
                'csv_file' => $this->csvFile($csv),
            ]);

        $response->assertRedirect(route('admin.products.index'));
        $response->assertSessionHasErrors(['csv_file']);
        Queue::assertNothingPushed();
        $this->assertDatabaseCount('product_imports', 0);
    }

    // TC-06: Valid CSV is stored, import record created, and background job queued
    public function test_pm005_valid_csv_is_queued_as_background_job(): void
    {
        Storage::fake('local');
        Queue::fake();

        $csv = $this->csv([
            ['CSV Product 1', 'Desc 1', '12.50', '5', 'published', ''],
        ]);

        $response = $this->actingAs($this->makeAdmin())
            ->post(route('admin.products.import'), [
                'csv_file' => $this->csvFile($csv),
            ]);

        $response->assertRedirect(route('admin.products.index'));
        $response->assertSessionHas('success');

        $import = ProductImport::first();
        $this->assertNotNull($import);
        $this->assertEquals('pending', $import->status);
        Storage::disk('local')->assertExists($import->file_path);

        Queue::assertPushed(ImportProductsCsvJob::class, function (ImportProductsCsvJob $job) use ($import) {
            return $job->productImportId === $import->id;
        });
    }

    // TC-07: Job imports valid rows and assigns existing category by name
    public function test_pm005_job_imports_valid_rows_and_category_mapping(): void
    {
        Storage::fake('local');

        $category = Category::factory()->create(['name' => 'Electronics']);
        $csv = $this->csv([
            ['Phone X', 'Smart phone', '699.99', '8', 'published', 'Electronics'],
            ['Cable', 'USB-C cable', '9.90', '120', 'draft', ''],
        ]);

        $path = 'product-imports/pm005-valid.csv';
        Storage::disk('local')->put($path, $csv);

        $import = ProductImport::create([
            'user_id' => $this->makeAdmin()->id,
            'file_path' => $path,
            'status' => 'pending',
            'total_rows' => 0,
            'success_rows' => 0,
            'failed_rows' => 0,
            'errors' => [],
        ]);

        (new ImportProductsCsvJob($import->id))->handle();

        $import->refresh();
        $this->assertEquals('completed', $import->status);
        $this->assertEquals(2, $import->total_rows);
        $this->assertEquals(2, $import->success_rows);
        $this->assertEquals(0, $import->failed_rows);

        $this->assertDatabaseHas('products', ['name' => 'Phone X', 'category_id' => $category->id]);
        $this->assertDatabaseHas('products', ['name' => 'Cable', 'status' => 'draft']);
    }

    // TC-08: Job reports data type errors per row
    public function test_pm005_job_reports_data_type_errors_per_row(): void
    {
        Storage::fake('local');

        $csv = $this->csv([
            ['Valid Item', 'Fine', '11.00', '3', 'published', ''],
            ['Invalid Item', 'Bad data', '-1', 'abc', 'unknown', ''],
        ]);

        $path = 'product-imports/pm005-invalid-types.csv';
        Storage::disk('local')->put($path, $csv);

        $import = ProductImport::create([
            'user_id' => $this->makeAdmin()->id,
            'file_path' => $path,
            'status' => 'pending',
            'total_rows' => 0,
            'success_rows' => 0,
            'failed_rows' => 0,
            'errors' => [],
        ]);

        (new ImportProductsCsvJob($import->id))->handle();

        $import->refresh();
        $this->assertEquals('completed', $import->status);
        $this->assertEquals(2, $import->total_rows);
        $this->assertEquals(1, $import->success_rows);
        $this->assertEquals(1, $import->failed_rows);

        $this->assertIsArray($import->errors);
        $this->assertEquals(3, $import->errors[0]['row']);
        $this->assertStringContainsString('price', implode(' ', $import->errors[0]['messages']));
        $this->assertStringContainsString('stock', implode(' ', $import->errors[0]['messages']));
        $this->assertStringContainsString('status', implode(' ', $import->errors[0]['messages']));
    }

    // TC-09: Job reports unknown category per row
    public function test_pm005_job_reports_unknown_category_error_per_row(): void
    {
        Storage::fake('local');

        $csv = $this->csv([
            ['Category Fail', 'Bad category', '12.00', '2', 'published', 'NotExistingCategory'],
        ]);

        $path = 'product-imports/pm005-unknown-category.csv';
        Storage::disk('local')->put($path, $csv);

        $import = ProductImport::create([
            'user_id' => $this->makeAdmin()->id,
            'file_path' => $path,
            'status' => 'pending',
            'total_rows' => 0,
            'success_rows' => 0,
            'failed_rows' => 0,
            'errors' => [],
        ]);

        (new ImportProductsCsvJob($import->id))->handle();

        $import->refresh();
        $this->assertEquals('completed', $import->status);
        $this->assertEquals(1, $import->failed_rows);
        $this->assertStringContainsString('category', implode(' ', $import->errors[0]['messages']));
    }

    // TC-10: Job marks import as failed if CSV file is missing
    public function test_pm005_job_marks_failed_when_file_missing(): void
    {
        Storage::fake('local');

        $import = ProductImport::create([
            'user_id' => $this->makeAdmin()->id,
            'file_path' => 'product-imports/not-found.csv',
            'status' => 'pending',
            'total_rows' => 0,
            'success_rows' => 0,
            'failed_rows' => 0,
            'errors' => [],
        ]);

        (new ImportProductsCsvJob($import->id))->handle();

        $import->refresh();
        $this->assertEquals('failed', $import->status);
        $this->assertStringContainsString('file not found', strtolower(implode(' ', $import->errors[0]['messages'])));
    }

    // TC-11: Products index shows per-row import errors
    public function test_pm005_index_shows_row_errors_from_import_history(): void
    {
        $admin = $this->makeAdmin();

        ProductImport::create([
            'user_id' => $admin->id,
            'file_path' => 'product-imports/pm005-errors.csv',
            'status' => 'completed',
            'total_rows' => 2,
            'success_rows' => 1,
            'failed_rows' => 1,
            'errors' => [
                ['row' => 4, 'messages' => ['price must be numeric and >= 0.01.']],
            ],
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.products.index'));

        $response->assertStatus(200);
        $response->assertSee('Import History');
        $response->assertSee('Row 4');
        $response->assertSee('price must be numeric and >= 0.01.');
    }

    // TC-12: Large CSV upload is still queued (background processing path)
    public function test_pm005_large_csv_is_queued_for_background_processing(): void
    {
        Storage::fake('local');
        Queue::fake();

        $rows = [];
        for ($i = 1; $i <= 120; $i++) {
            $rows[] = ["Bulk Product {$i}", 'Bulk import item', '19.99', '50', 'published', ''];
        }

        $csv = $this->csv($rows);

        $response = $this->actingAs($this->makeAdmin())
            ->post(route('admin.products.import'), [
                'csv_file' => $this->csvFile($csv, 'bulk-products.csv'),
            ]);

        $response->assertRedirect(route('admin.products.index'));
        Queue::assertPushed(ImportProductsCsvJob::class, 1);
    }
}
