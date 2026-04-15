<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartViewTest extends TestCase
{
    use RefreshDatabase;

    private function makeProduct(array $attrs = []): Product
    {
        return Product::factory()->create(array_merge(['stock' => 10, 'price' => 25.00], $attrs));
    }

    private function addToCart(Product $product, int $qty = 1): void
    {
        $this->post(route('cart.store'), [
            'product_id' => $product->id,
            'quantity'   => $qty,
        ]);
    }

    // TC-01: Cart page returns 200 for a guest
    public function test_sc002_cart_page_returns_200_for_guest(): void
    {
        $response = $this->get(route('cart.index'));
        $response->assertOk();
    }

    // TC-02: Cart page returns 200 for an authenticated user
    public function test_sc002_cart_page_returns_200_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('cart.index'));
        $response->assertOk();
    }

    // TC-03: Empty cart shows the empty-cart message
    public function test_sc002_empty_cart_shows_empty_message(): void
    {
        $response = $this->get(route('cart.index'));
        $response->assertSee('Your cart is empty');
    }

    // TC-04: Cart with items shows product name
    public function test_sc002_cart_shows_product_name(): void
    {
        $product = $this->makeProduct(['name' => 'Widget Alpha']);
        $this->addToCart($product, 2);

        $response = $this->get(route('cart.index'));
        $response->assertSee('Widget Alpha');
    }

    // TC-05: Cart shows the unit price
    public function test_sc002_cart_shows_unit_price(): void
    {
        $product = $this->makeProduct(['price' => 19.99]);
        $this->addToCart($product, 1);

        $response = $this->get(route('cart.index'));
        $response->assertSee('19.99');
    }

    // TC-06: Cart shows the quantity
    public function test_sc002_cart_shows_quantity(): void
    {
        $product = $this->makeProduct();
        $this->addToCart($product, 3);

        $response = $this->get(route('cart.index'));
        $response->assertSee('3');
    }

    // TC-07: Cart shows the correct line subtotal
    public function test_sc002_cart_shows_correct_subtotal(): void
    {
        $product = $this->makeProduct(['price' => 10.00]);
        $this->addToCart($product, 4);

        $response = $this->get(route('cart.index'));
        // subtotal = 10.00 × 4 = 40.00
        $response->assertSee('40.00');
    }

    // TC-08: Cart shows the correct order total
    public function test_sc002_cart_shows_correct_order_total(): void
    {
        $p1 = $this->makeProduct(['price' => 10.00]);
        $p2 = $this->makeProduct(['price' => 5.00]);
        $this->addToCart($p1, 2); // 20.00
        $this->addToCart($p2, 3); // 15.00 → total = 35.00

        $response = $this->get(route('cart.index'));
        $response->assertSee('Order Total');
        $response->assertSee('35.00');
    }

    // TC-09: Multiple products all appear in the cart
    public function test_sc002_multiple_products_all_appear_in_cart(): void
    {
        $p1 = $this->makeProduct(['name' => 'Product One']);
        $p2 = $this->makeProduct(['name' => 'Product Two']);
        $this->addToCart($p1);
        $this->addToCart($p2);

        $response = $this->get(route('cart.index'));
        $response->assertSee('Product One');
        $response->assertSee('Product Two');
    }

    // TC-10: Empty-cart state does NOT show the order-total line
    public function test_sc002_empty_cart_does_not_show_order_total(): void
    {
        $response = $this->get(route('cart.index'));
        $response->assertDontSee('Order Total');
    }

    // TC-11: Cart page has a "Continue Shopping" link back to products
    public function test_sc002_cart_page_has_continue_shopping_link(): void
    {
        $response = $this->get(route('cart.index'));
        $response->assertSee('Continue Shopping');
    }

    // TC-12: Cart page responds within one second
    public function test_sc002_cart_page_responds_within_one_second(): void
    {
        $product = $this->makeProduct();
        $this->addToCart($product, 2);

        $start = microtime(true);
        $this->get(route('cart.index'));
        $duration = microtime(true) - $start;

        $this->assertLessThan(1.0, $duration);
    }
}
