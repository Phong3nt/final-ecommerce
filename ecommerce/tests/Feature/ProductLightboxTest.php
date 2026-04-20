<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * IMP-010 — Product image lightbox + zoom on detail page
 *
 * Verifies that the product detail page renders the Alpine.js lightbox
 * component correctly, including: main-image trigger, overlay element,
 * close button accessibility, keyboard-escape handler, zoom container,
 * thumbnail strip for multi-image products, and graceful fallback when
 * no image is present.
 */
class ProductLightboxTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'user',  'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    }

    // TC-01 (Happy): Main product image has the lightbox trigger attribute
    public function test_imp010_main_image_has_lightbox_trigger_attribute(): void
    {
        $product = Product::factory()->create([
            'slug'  => 'lightbox-product',
            'image' => 'products/sample.jpg',
        ]);

        $response = $this->get(route('products.show', 'lightbox-product'));

        $response->assertStatus(200);
        $response->assertSee('data-imp010="main-image"', false);
    }

    // TC-02 (Happy): Lightbox overlay element with correct id is present
    public function test_imp010_lightbox_overlay_element_present(): void
    {
        $product = Product::factory()->create([
            'slug'  => 'lightbox-overlay-product',
            'image' => 'products/sample.jpg',
        ]);

        $response = $this->get(route('products.show', 'lightbox-overlay-product'));

        $response->assertSee('id="imp010-lightbox"', false);
    }

    // TC-03 (Happy): Close button has aria-label for accessibility
    public function test_imp010_close_button_has_aria_label(): void
    {
        $product = Product::factory()->create([
            'slug'  => 'lightbox-close-product',
            'image' => 'products/sample.jpg',
        ]);

        $response = $this->get(route('products.show', 'lightbox-close-product'));

        $response->assertSee('aria-label="Close lightbox"', false);
        $response->assertSee('data-imp010="close-btn"', false);
    }

    // TC-04 (Accessibility): Lightbox overlay has role=dialog and aria-modal
    public function test_imp010_lightbox_has_role_dialog_and_aria_modal(): void
    {
        $product = Product::factory()->create([
            'slug'  => 'lightbox-aria-product',
            'image' => 'products/sample.jpg',
        ]);

        $response = $this->get(route('products.show', 'lightbox-aria-product'));

        $response->assertSee('role="dialog"', false);
        $response->assertSee('aria-modal="true"', false);
    }

    // TC-05 (Happy): Alpine component function imp010Lightbox defined in page script
    public function test_imp010_alpine_component_function_defined(): void
    {
        $product = Product::factory()->create([
            'slug'  => 'lightbox-fn-product',
            'image' => 'products/sample.jpg',
        ]);

        $response = $this->get(route('products.show', 'lightbox-fn-product'));

        $response->assertSee('imp010Lightbox', false);
    }

    // TC-06 (Happy): Zoom container element (imp010-zoom-wrap) present in lightbox
    public function test_imp010_zoom_container_present(): void
    {
        $product = Product::factory()->create([
            'slug'  => 'lightbox-zoom-product',
            'image' => 'products/sample.jpg',
        ]);

        $response = $this->get(route('products.show', 'lightbox-zoom-product'));

        $response->assertSee('imp010-zoom-wrap', false);
        $response->assertSee('data-imp010="lightbox-image"', false);
    }

    // TC-07 (Happy): Keyboard escape handler present in overlay element
    public function test_imp010_keyboard_escape_handler_present(): void
    {
        $product = Product::factory()->create([
            'slug'  => 'lightbox-esc-product',
            'image' => 'products/sample.jpg',
        ]);

        $response = $this->get(route('products.show', 'lightbox-esc-product'));

        $response->assertSee('@keydown.escape.window', false);
    }

    // TC-08 (Happy): x-data attribute contains imp010Lightbox invocation
    public function test_imp010_x_data_attribute_invokes_component(): void
    {
        $product = Product::factory()->create([
            'slug'  => 'lightbox-xdata-product',
            'image' => 'products/sample.jpg',
        ]);

        $response = $this->get(route('products.show', 'lightbox-xdata-product'));

        $response->assertSee('x-data="imp010Lightbox({', false);
    }

    // TC-09 (Happy): Thumbnail strip template is present in page for multi-image products
    public function test_imp010_thumbnail_strip_present_for_multi_image_product(): void
    {
        $product = Product::factory()->create([
            'slug'   => 'lightbox-multi-product',
            'image'  => 'products/main.jpg',
            'images' => ['products/img1.jpg', 'products/img2.jpg'],
        ]);

        $response = $this->get(route('products.show', 'lightbox-multi-product'));

        // Template element for thumbs strip is in source regardless of Alpine x-if
        $response->assertSee('imp010-thumbs', false);
        $response->assertSee('imp010-thumb', false);
    }

    // TC-10 (Edge): Product with no image shows "No image available" and no lightbox trigger
    public function test_imp010_no_image_product_shows_fallback_without_lightbox(): void
    {
        $product = Product::factory()->create([
            'slug'  => 'lightbox-noimg-product',
            'image' => null,
        ]);

        $response = $this->get(route('products.show', 'lightbox-noimg-product'));

        $response->assertSee('No image available');
        $response->assertDontSee('data-imp010="main-image"', false);
        $response->assertDontSee('id="imp010-lightbox"', false);
    }

    // TC-11 (Happy): CSS class imp010-main-img applied to clickable product image
    public function test_imp010_main_image_css_class_applied(): void
    {
        $product = Product::factory()->create([
            'slug'  => 'lightbox-css-product',
            'image' => 'products/sample.jpg',
        ]);

        $response = $this->get(route('products.show', 'lightbox-css-product'));

        $response->assertSee('imp010-main-img', false);
        $response->assertSee('imp010-main-wrapper', false);
    }

    // TC-12 (Performance): Detail page with lightbox responds within 2 seconds
    public function test_imp010_detail_page_with_lightbox_responds_within_two_seconds(): void
    {
        $product = Product::factory()->create([
            'slug'  => 'lightbox-perf-product',
            'image' => 'products/sample.jpg',
        ]);

        $start    = microtime(true);
        $response = $this->get(route('products.show', 'lightbox-perf-product'));
        $elapsed  = microtime(true) - $start;

        $response->assertStatus(200);
        $this->assertLessThan(2.0, $elapsed, 'Detail page with lightbox exceeded 2 seconds.');
    }
}
