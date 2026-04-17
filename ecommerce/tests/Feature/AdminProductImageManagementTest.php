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
 * PM-006 — As an admin, I want to manage product images so each product looks appealing.
 *
 * Acceptance criteria:
 *   - Multiple images per product
 *   - Drag-to-reorder (reorder endpoint saves new order)
 *   - One image set as thumbnail
 */
class AdminProductImageManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Storage::fake('public');
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

    private function makeProductWithImages(int $count = 3): Product
    {
        $paths = [];
        for ($i = 0; $i < $count; $i++) {
            $file = UploadedFile::fake()->image("product-{$i}.jpg");
            $paths[] = $file->store('products', 'public');
        }

        return Product::factory()->create([
            'status' => 'published',
            'images' => $paths,
            'image' => $paths[0],
        ]);
    }

    // TC-01: Guest is redirected from images page
    public function test_pm006_guest_is_redirected_from_images_page(): void
    {
        $product = $this->makeProductWithImages();

        $response = $this->get(route('admin.products.images', $product));

        $response->assertRedirect(route('login'));
    }

    // TC-02: Non-admin gets 403 on images page
    public function test_pm006_non_admin_gets_403_on_images_page(): void
    {
        $product = $this->makeProductWithImages();

        $response = $this->actingAs($this->makeUser())
            ->get(route('admin.products.images', $product));

        $response->assertForbidden();
    }

    // TC-03: Admin can view images management page
    public function test_pm006_admin_can_view_images_page(): void
    {
        $product = $this->makeProductWithImages();

        $response = $this->actingAs($this->makeAdmin())
            ->get(route('admin.products.images', $product));

        $response->assertOk();
        $response->assertSee('Manage Images');
    }

    // TC-04: Images page shows all product images (paths visible)
    public function test_pm006_images_page_shows_product_images(): void
    {
        $product = $this->makeProductWithImages(3);

        $response = $this->actingAs($this->makeAdmin())
            ->get(route('admin.products.images', $product));

        $response->assertOk();
        foreach ($product->images as $path) {
            $response->assertSee($path);
        }
        $response->assertSee('Set Thumbnail');
    }

    // TC-05: Admin can reorder images
    public function test_pm006_admin_can_reorder_images(): void
    {
        $product = $this->makeProductWithImages(3);
        $images = $product->images;
        $newOrder = [$images[2], $images[0], $images[1]];

        $response = $this->actingAs($this->makeAdmin())
            ->post(route('admin.products.images.reorder', $product), [
                'image_order' => $newOrder,
            ]);

        $response->assertRedirect(route('admin.products.images', $product));
        $product->refresh();
        $this->assertEquals($newOrder, $product->images);
    }

    // TC-06: Reorder preserves existing thumbnail when it remains in new order
    public function test_pm006_reorder_preserves_thumbnail(): void
    {
        $product = $this->makeProductWithImages(3);
        $images = $product->images;
        // thumbnail is $images[0]; reorder keeps it but in different position
        $newOrder = [$images[1], $images[2], $images[0]];

        $this->actingAs($this->makeAdmin())
            ->post(route('admin.products.images.reorder', $product), [
                'image_order' => $newOrder,
            ]);

        $product->refresh();
        $this->assertEquals($images[0], $product->image); // thumbnail unchanged
        $this->assertEquals($newOrder, $product->images);
    }

    // TC-07: Reorder silently ignores paths not in product images (security)
    public function test_pm006_reorder_ignores_foreign_paths(): void
    {
        $product = $this->makeProductWithImages(2);
        $images = $product->images;

        $this->actingAs($this->makeAdmin())
            ->post(route('admin.products.images.reorder', $product), [
                'image_order' => [$images[0], 'products/injected-path.jpg', $images[1]],
            ]);

        $product->refresh();
        $this->assertEquals([$images[0], $images[1]], $product->images);
    }

    // TC-08: Reorder requires image_order to be present
    public function test_pm006_reorder_requires_image_order(): void
    {
        $product = $this->makeProductWithImages(2);

        $response = $this->actingAs($this->makeAdmin())
            ->post(route('admin.products.images.reorder', $product), []);

        $response->assertSessionHasErrors('image_order');
    }

    // TC-09: Admin can set thumbnail by index
    public function test_pm006_admin_can_set_thumbnail(): void
    {
        $product = $this->makeProductWithImages(3);
        $images = $product->images;

        $response = $this->actingAs($this->makeAdmin())
            ->post(route('admin.products.images.thumbnail', $product), [
                'thumbnail_index' => 2,
            ]);

        $response->assertRedirect(route('admin.products.images', $product));
        $product->refresh();
        $this->assertEquals($images[2], $product->image);
    }

    // TC-10: Set thumbnail with out-of-bounds index returns validation error
    public function test_pm006_set_thumbnail_with_invalid_index_returns_error(): void
    {
        $product = $this->makeProductWithImages(2); // indices 0 and 1

        $response = $this->actingAs($this->makeAdmin())
            ->post(route('admin.products.images.thumbnail', $product), [
                'thumbnail_index' => 99,
            ]);

        $response->assertSessionHasErrors('thumbnail_index');
    }

    // TC-11: Admin can delete an image by index
    public function test_pm006_admin_can_delete_image(): void
    {
        $product = $this->makeProductWithImages(3);
        $images = $product->images;

        $response = $this->actingAs($this->makeAdmin())
            ->delete(route('admin.products.images.destroy', [$product, 1]));

        $response->assertRedirect(route('admin.products.images', $product));
        $product->refresh();
        $this->assertCount(2, $product->images);
        $this->assertNotContains($images[1], $product->images);
    }

    // TC-12: Deleting the thumbnail auto-sets the next image as thumbnail
    public function test_pm006_deleting_thumbnail_updates_thumbnail(): void
    {
        $product = $this->makeProductWithImages(3);
        $images = $product->images;
        // thumbnail is index 0
        $this->assertEquals($images[0], $product->image);

        $this->actingAs($this->makeAdmin())
            ->delete(route('admin.products.images.destroy', [$product, 0]));

        $product->refresh();
        $this->assertEquals($images[1], $product->image); // next image becomes thumbnail
    }

    // TC-13: Deleting the last image nullifies images and thumbnail
    public function test_pm006_deleting_last_image_nullifies_all(): void
    {
        $product = $this->makeProductWithImages(1);

        $this->actingAs($this->makeAdmin())
            ->delete(route('admin.products.images.destroy', [$product, 0]));

        $product->refresh();
        $this->assertNull($product->images);
        $this->assertNull($product->image);
    }
}
