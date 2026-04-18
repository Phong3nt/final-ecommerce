<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * NF-007 — Images stored in cloud storage (S3 / compatible) not in `public/`.
 *
 * Strategy:
 *   1. Config audit  — filesystems.php has an s3 disk with required keys.
 *   2. Source audit  — ProductController and ProfileController use the configurable
 *                      image_disk, not a hardcoded 'public' disk.
 *   3. Runtime audit — image uploads resolve to the disk set by IMAGE_DISK env var.
 */
class CloudImageStorageTest extends TestCase
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
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        return $admin;
    }

    private function makeProduct(array $attrs = []): Product
    {
        return Product::factory()->create(array_merge(['status' => 'published'], $attrs));
    }

    // -------------------------------------------------------------------------
    // TC-01: config/filesystems.php has an 's3' disk configured
    // -------------------------------------------------------------------------

    public function test_nf007_tc01_s3_disk_is_configured(): void
    {
        $disks = config('filesystems.disks');
        $this->assertArrayHasKey('s3', $disks, 'filesystems.php must define an s3 disk.');
    }

    // -------------------------------------------------------------------------
    // TC-02: S3 disk driver is 's3'
    // -------------------------------------------------------------------------

    public function test_nf007_tc02_s3_disk_driver_is_s3(): void
    {
        $disk = config('filesystems.disks.s3');
        $this->assertSame('s3', $disk['driver'], "s3 disk must have driver = 's3'.");
    }

    // -------------------------------------------------------------------------
    // TC-03: S3 disk has the required AWS configuration keys
    // -------------------------------------------------------------------------

    public function test_nf007_tc03_s3_disk_has_required_aws_keys(): void
    {
        $disk = config('filesystems.disks.s3');
        foreach (['key', 'secret', 'region', 'bucket'] as $key) {
            $this->assertArrayHasKey($key, $disk, "s3 disk config must define '{$key}'.");
        }
    }

    // -------------------------------------------------------------------------
    // TC-04: filesystems.php source code defaults IMAGE_DISK to 's3'
    // -------------------------------------------------------------------------

    public function test_nf007_tc04_image_disk_defaults_to_s3_in_source(): void
    {
        $source = file_get_contents(config_path('filesystems.php'));
        $this->assertStringContainsString(
            "env('IMAGE_DISK', 's3')",
            $source,
            "filesystems.php must default IMAGE_DISK env to 's3'."
        );
    }

    // -------------------------------------------------------------------------
    // TC-05: 'image_disk' config key exists
    // -------------------------------------------------------------------------

    public function test_nf007_tc05_image_disk_config_key_exists(): void
    {
        $this->assertNotNull(
            config('filesystems.image_disk'),
            "config('filesystems.image_disk') must be defined."
        );
    }

    // -------------------------------------------------------------------------
    // TC-06: ProductController source uses config('filesystems.image_disk'), not hardcoded 'public'
    // -------------------------------------------------------------------------

    public function test_nf007_tc06_product_controller_uses_configurable_image_disk(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/Admin/ProductController.php'));

        $this->assertStringContainsString(
            "config('filesystems.image_disk'",
            $source,
            'ProductController must use config(filesystems.image_disk) for image storage.'
        );

        // Ensure no call still hardcodes the public disk for image uploads
        $this->assertStringNotContainsString(
            "store('products', 'public')",
            $source,
            "ProductController must not hardcode 'public' disk for product image uploads."
        );
    }

    // -------------------------------------------------------------------------
    // TC-07: ProfileController source uses configurable disk, not hardcoded 'public'
    // -------------------------------------------------------------------------

    public function test_nf007_tc07_profile_controller_uses_configurable_image_disk(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/ProfileController.php'));

        $this->assertStringContainsString(
            "config('filesystems.image_disk'",
            $source,
            'ProfileController must use config(filesystems.image_disk) for avatar storage.'
        );

        $this->assertStringNotContainsString(
            "store('avatars', 'public')",
            $source,
            "ProfileController must not hardcode 'public' disk for avatar uploads."
        );
    }

    // -------------------------------------------------------------------------
    // TC-08: Product store() uploads image to the image_disk (runtime)
    // -------------------------------------------------------------------------

    public function test_nf007_tc08_product_store_uploads_image_to_image_disk(): void
    {
        $disk = config('filesystems.image_disk', 's3');
        Storage::fake($disk);

        $admin    = $this->makeAdmin();
        $category = Category::factory()->create();

        $this->actingAs($admin)->post(route('admin.products.store'), [
            'name'        => 'Cloud Test Product',
            'price'       => '9.99',
            'stock'       => 10,
            'status'      => 'published',
            'category_id' => $category->id,
            'images'      => [UploadedFile::fake()->image('product.jpg')],
        ]);

        $product = Product::where('name', 'Cloud Test Product')->first();
        $this->assertNotNull($product);
        $this->assertNotEmpty($product->image);
        Storage::disk($disk)->assertExists($product->image);
    }

    // -------------------------------------------------------------------------
    // TC-09: Product update() uploads new images to the image_disk (runtime)
    // -------------------------------------------------------------------------

    public function test_nf007_tc09_product_update_uploads_image_to_image_disk(): void
    {
        $disk = config('filesystems.image_disk', 's3');
        Storage::fake($disk);

        $admin   = $this->makeAdmin();
        $product = $this->makeProduct(['stock' => 10]);

        $this->actingAs($admin)->patch(route('admin.products.update', $product), [
            'name'   => $product->name,
            'price'  => $product->price,
            'stock'  => $product->stock,
            'status' => $product->status,
            'images' => [UploadedFile::fake()->image('update.jpg')],
        ]);

        $product->refresh();
        $this->assertNotEmpty($product->image);
        Storage::disk($disk)->assertExists($product->image);
    }

    // -------------------------------------------------------------------------
    // TC-10: Avatar upload stores file on the image_disk (runtime)
    // -------------------------------------------------------------------------

    public function test_nf007_tc10_avatar_upload_stores_on_image_disk(): void
    {
        $disk = config('filesystems.image_disk', 's3');
        Storage::fake($disk);

        $user = User::factory()->create();

        $this->actingAs($user)->put(route('profile.update'), [
            'name'   => $user->name,
            'email'  => $user->email,
            'avatar' => UploadedFile::fake()->image('avatar.jpg', 100, 100)->size(500),
        ]);

        $user->refresh();
        $this->assertNotNull($user->avatar);
        Storage::disk($disk)->assertExists($user->avatar);
    }

    // -------------------------------------------------------------------------
    // TC-11: S3 disk has visibility set to 'public' (images must be publicly readable)
    // -------------------------------------------------------------------------

    public function test_nf007_tc11_s3_disk_has_public_visibility(): void
    {
        $disk = config('filesystems.disks.s3');
        $this->assertArrayHasKey('visibility', $disk, "s3 disk must declare a 'visibility' key.");
        $this->assertSame('public', $disk['visibility'], "s3 disk visibility must be 'public'.");
    }

    // -------------------------------------------------------------------------
    // TC-12: S3 disk supports path-style endpoint for S3-compatible storage (MinIO etc.)
    // -------------------------------------------------------------------------

    public function test_nf007_tc12_s3_disk_use_path_style_endpoint_is_configurable(): void
    {
        $disk = config('filesystems.disks.s3');
        $this->assertArrayHasKey(
            'use_path_style_endpoint',
            $disk,
            "s3 disk must have 'use_path_style_endpoint' for S3-compatible storage support."
        );

        $source = file_get_contents(config_path('filesystems.php'));
        $this->assertStringContainsString(
            'AWS_USE_PATH_STYLE_ENDPOINT',
            $source,
            "use_path_style_endpoint must be configurable via AWS_USE_PATH_STYLE_ENDPOINT env."
        );
    }
}
