<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    private function makeProduct(array $attrs = []): Product
    {
        return Product::factory()->create(array_merge(['stock' => 10, 'price' => 29.99], $attrs));
    }

    // TC-01: Guest can add a product to the session cart
    public function test_sc001_guest_can_add_product_to_cart(): void
    {
        $product = $this->makeProduct();

        $response = $this->post(route('cart.store'), [
            'product_id' => $product->id,
            'quantity'   => 1,
        ]);

        $response->assertRedirect();
        $this->assertNotNull(session('cart'));
        $this->assertArrayHasKey($product->id, session('cart'));
    }

    // TC-02: Authenticated user can add product to cart
    public function test_sc001_authenticated_user_can_add_product_to_cart(): void
    {
        $user    = User::factory()->create();
        $product = $this->makeProduct();

        $response = $this->actingAs($user)->post(route('cart.store'), [
            'product_id' => $product->id,
            'quantity'   => 2,
        ]);

        $response->assertRedirect();
        $cart = session('cart');
        $this->assertArrayHasKey($product->id, $cart);
        $this->assertEquals(2, $cart[$product->id]['quantity']);
    }

    // TC-03: Cart item stores the correct quantity
    public function test_sc001_cart_stores_correct_quantity(): void
    {
        $product = $this->makeProduct();

        $this->post(route('cart.store'), [
            'product_id' => $product->id,
            'quantity'   => 3,
        ]);

        $cart = session('cart');
        $this->assertEquals(3, $cart[$product->id]['quantity']);
    }

    // TC-04: Adding the same product twice merges quantities
    public function test_sc001_adding_same_product_twice_merges_quantities(): void
    {
        $product = $this->makeProduct(['stock' => 10]);

        $this->post(route('cart.store'), ['product_id' => $product->id, 'quantity' => 2]);
        $this->post(route('cart.store'), ['product_id' => $product->id, 'quantity' => 3]);

        $cart = session('cart');
        $this->assertEquals(5, $cart[$product->id]['quantity']);
    }

    // TC-05: Out-of-stock product cannot be added (JSON)
    public function test_sc001_out_of_stock_product_cannot_be_added(): void
    {
        $product = $this->makeProduct(['stock' => 0]);

        $response = $this->postJson(route('cart.store'), [
            'product_id' => $product->id,
            'quantity'   => 1,
        ]);

        $response->assertStatus(422)
            ->assertJson(['message' => 'Product is out of stock.']);
        $this->assertNull(session('cart'));
    }

    // TC-06: Nonexistent product_id fails validation
    public function test_sc001_nonexistent_product_id_fails_validation(): void
    {
        $response = $this->post(route('cart.store'), [
            'product_id' => 99999,
            'quantity'   => 1,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('product_id');
    }

    // TC-07: Zero quantity fails validation
    public function test_sc001_zero_quantity_fails_validation(): void
    {
        $product = $this->makeProduct();

        $response = $this->post(route('cart.store'), [
            'product_id' => $product->id,
            'quantity'   => 0,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('quantity');
    }

    // TC-08: Negative quantity fails validation
    public function test_sc001_negative_quantity_fails_validation(): void
    {
        $product = $this->makeProduct();

        $response = $this->post(route('cart.store'), [
            'product_id' => $product->id,
            'quantity'   => -1,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('quantity');
    }

    // TC-09: Requested quantity exceeding stock is capped at stock
    public function test_sc001_quantity_exceeding_stock_is_capped(): void
    {
        $product = $this->makeProduct(['stock' => 3]);

        $this->post(route('cart.store'), [
            'product_id' => $product->id,
            'quantity'   => 10,
        ]);

        $cart = session('cart');
        $this->assertEquals(3, $cart[$product->id]['quantity']);
    }

    // TC-10: AJAX request returns JSON with message and cart_count
    public function test_sc001_ajax_request_returns_json_with_cart_count(): void
    {
        $product = $this->makeProduct(['stock' => 5]);

        $response = $this->postJson(route('cart.store'), [
            'product_id' => $product->id,
            'quantity'   => 2,
        ]);

        $response->assertOk()
            ->assertJsonStructure(['message', 'cart_count'])
            ->assertJson(['message' => 'Product added to cart.', 'cart_count' => 2]);
    }

    // TC-11: Cart session item contains the expected data keys
    public function test_sc001_cart_session_item_contains_correct_data(): void
    {
        $product = $this->makeProduct(['price' => 19.99]);

        $this->post(route('cart.store'), [
            'product_id' => $product->id,
            'quantity'   => 1,
        ]);

        $item = session('cart')[$product->id];
        $this->assertEquals($product->id, $item['product_id']);
        $this->assertEquals($product->name, $item['name']);
        $this->assertEquals(19.99, $item['price']);
        $this->assertEquals(1, $item['quantity']);
        $this->assertArrayHasKey('slug', $item);
    }

    // TC-12: Add to cart completes within one second
    public function test_sc001_add_to_cart_completes_within_one_second(): void
    {
        $product = $this->makeProduct();

        $start = microtime(true);
        $this->post(route('cart.store'), ['product_id' => $product->id, 'quantity' => 1]);
        $duration = microtime(true) - $start;

        $this->assertLessThan(1.0, $duration);
    }
}
