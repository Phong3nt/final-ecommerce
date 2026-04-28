<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * IMP-007 — Alpine.js micro-interactions on all cart actions.
 *
 * Covers: SC-001 (add-to-cart), SC-002 (view cart), SC-003 (qty update), SC-004 (remove)
 * Scope: [UIUX_MODE] — verifies Alpine.js CDN, x-data attributes, and IMP-007 CSS are
 *        present in rendered HTML. No controller/model changes.
 */
class AlpineCartMicroInteractionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    }

    // ─────────────────────────────────────────────────────────────────────
    // TC01 / TC02: Alpine.js CDN included on both pages
    // ─────────────────────────────────────────────────────────────────────

    /** @test */
    public function imp007_tc01_product_show_page_includes_alpinejs_cdn(): void
    {
        $product = Product::factory()->create(['status' => 'published', 'stock' => 5]);

        $response = $this->get(route('products.show', $product->slug));

        $response->assertStatus(200);
        $response->assertSee('alpinejs@3.14.1', false);
        $response->assertSee('defer', false);
    }

    /** @test */
    public function imp007_tc02_cart_page_includes_alpinejs_cdn(): void
    {
        $product = Product::factory()->create(['status' => 'published', 'stock' => 5, 'price' => 10.00]);
        $cart = [
            $product->id => ['product_id' => $product->id, 'name' => $product->name, 'price' => 10.00, 'quantity' => 1, 'slug' => $product->slug],
        ];

        $response = $this->withSession(['cart' => $cart])->get(route('cart.index'));

        $response->assertStatus(200);
        $response->assertSee('alpinejs@3.14.1', false);
    }

    // ─────────────────────────────────────────────────────────────────────
    // TC03 / TC04: Alpine x-data wrappers present on target elements
    // ─────────────────────────────────────────────────────────────────────

    /** @test */
    public function imp007_tc03_add_to_cart_wrapper_has_imp007_x_data(): void
    {
        $product = Product::factory()->create(['status' => 'published', 'stock' => 3]);

        $response = $this->get(route('products.show', $product->slug));

        $response->assertStatus(200);
        $response->assertSee('imp007AddToCart', false);
    }

    /** @test */
    public function imp007_tc04_cart_rows_have_imp007_cart_row_x_data(): void
    {
        $product = Product::factory()->create(['status' => 'published', 'stock' => 5, 'price' => 10.00]);
        $cart = [
            $product->id => ['product_id' => $product->id, 'name' => $product->name, 'price' => 10.00, 'quantity' => 2, 'slug' => $product->slug],
        ];

        $response = $this->withSession(['cart' => $cart])->get(route('cart.index'));

        $response->assertStatus(200);
        $response->assertSee('imp007CartRow', false);
    }

    // ─────────────────────────────────────────────────────────────────────
    // TC05 / TC06: Alpine submit handlers on qty and remove forms
    // ─────────────────────────────────────────────────────────────────────

    /** @test */
    public function imp007_tc05_qty_update_form_has_alpine_submit_handler(): void
    {
        $product = Product::factory()->create(['status' => 'published', 'stock' => 5, 'price' => 10.00]);
        $cart = [
            $product->id => ['product_id' => $product->id, 'name' => $product->name, 'price' => 10.00, 'quantity' => 1, 'slug' => $product->slug],
        ];

        $response = $this->withSession(['cart' => $cart])->get(route('cart.index'));

        $response->assertStatus(200);
        $response->assertSee('updateQty', false);
        $response->assertSee('x-on:submit.prevent', false);
    }

    /** @test */
    public function imp007_tc06_remove_form_has_alpine_submit_handler(): void
    {
        $product = Product::factory()->create(['status' => 'published', 'stock' => 5, 'price' => 10.00]);
        $cart = [
            $product->id => ['product_id' => $product->id, 'name' => $product->name, 'price' => 10.00, 'quantity' => 1, 'slug' => $product->slug],
        ];

        $response = $this->withSession(['cart' => $cart])->get(route('cart.index'));

        $response->assertStatus(200);
        $response->assertSee('removeItem', false);
    }

    // ─────────────────────────────────────────────────────────────────────
    // TC07: Toast notification area present on cart page
    // ─────────────────────────────────────────────────────────────────────

    /** @test */
    public function imp007_tc07_cart_page_has_toast_notification_area(): void
    {
        $response = $this->get(route('cart.index'));

        $response->assertStatus(200);
        $response->assertSee('imp007ToastManager', false);
        $response->assertSee('imp007-toast-area', false);
    }

    // ─────────────────────────────────────────────────────────────────────
    // TC08 / TC09: IMP-007 CSS present in rendered pages
    // ─────────────────────────────────────────────────────────────────────

    /** @test */
    public function imp007_tc08_cart_page_includes_imp007_spinner_css(): void
    {
        $response = $this->get(route('cart.index'));

        $response->assertStatus(200);
        $response->assertSee('imp007-spinner', false);
        $response->assertSee('imp007-removing', false);
    }

    /** @test */
    public function imp007_tc09_show_page_includes_atc_spinner_css(): void
    {
        $product = Product::factory()->create(['status' => 'published', 'stock' => 2]);

        $response = $this->get(route('products.show', $product->slug));

        $response->assertStatus(200);
        $response->assertSee('atc-spinner', false);
        $response->assertSee('atc-success', false);
    }

    // ─────────────────────────────────────────────────────────────────────
    // TC10: SC-001 add-to-cart AJAX endpoint regression (SC-001)
    // ─────────────────────────────────────────────────────────────────────

    /** @test */
    public function imp007_tc10_add_to_cart_ajax_returns_cart_count(): void
    {
        $product = Product::factory()->create(['status' => 'published', 'stock' => 5, 'price' => 10.00]);

        $response = $this->postJson(route('cart.store'), [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['cart_count']);
        $this->assertIsInt($response->json('cart_count'));
    }
}
