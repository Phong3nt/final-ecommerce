<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartUpdateTest extends TestCase
{
    use RefreshDatabase;

    private function makeProduct(array $attrs = []): Product
    {
        return Product::factory()->create(array_merge(['stock' => 10, 'price' => 20.00], $attrs));
    }

    /** Seed session cart with a product at the given quantity. */
    private function seedCart(Product $product, int $qty = 2): void
    {
        $this->post(route('cart.store'), [
            'product_id' => $product->id,
            'quantity'   => $qty,
        ]);
    }

    // TC-01: Update returns redirect to cart for form submit
    public function test_sc003_update_redirects_to_cart(): void
    {
        $product = $this->makeProduct();
        $this->seedCart($product, 2);

        $response = $this->patch(route('cart.update', $product->id), ['quantity' => 3]);
        $response->assertRedirect(route('cart.index'));
    }

    // TC-02: Updated quantity is saved in the session
    public function test_sc003_update_saves_new_quantity_in_session(): void
    {
        $product = $this->makeProduct();
        $this->seedCart($product, 2);

        $this->patch(route('cart.update', $product->id), ['quantity' => 5]);

        $this->assertEquals(5, session('cart')[$product->id]['quantity']);
    }

    // TC-03: Quantity exceeding stock is capped at stock
    public function test_sc003_quantity_exceeding_stock_is_capped(): void
    {
        $product = $this->makeProduct(['stock' => 4]);
        $this->seedCart($product, 2);

        $this->patch(route('cart.update', $product->id), ['quantity' => 99]);

        $this->assertEquals(4, session('cart')[$product->id]['quantity']);
    }

    // TC-04: AJAX update returns JSON with updated subtotal and order_total
    public function test_sc003_ajax_returns_json_with_subtotal_and_order_total(): void
    {
        $product = $this->makeProduct(['price' => 10.00]);
        $this->seedCart($product, 1);

        $response = $this->patchJson(route('cart.update', $product->id), ['quantity' => 3]);

        $response->assertOk()
            ->assertJsonStructure(['message', 'quantity', 'subtotal', 'order_total'])
            ->assertJson([
                'message'     => 'Cart updated.',
                'quantity'    => 3,
                'subtotal'    => '30.00',
                'order_total' => '30.00',
            ]);
    }

    // TC-05: AJAX shows correct order_total across multiple items
    public function test_sc003_ajax_order_total_recalculates_across_items(): void
    {
        $p1 = $this->makeProduct(['price' => 10.00]);
        $p2 = $this->makeProduct(['price' => 5.00]);
        $this->seedCart($p1, 2); // 20.00
        $this->seedCart($p2, 2); // 10.00  → total 30.00

        // update p1 qty to 3 → p1=30, p2=10, total=40
        $response = $this->patchJson(route('cart.update', $p1->id), ['quantity' => 3]);

        $response->assertJson([
            'subtotal'    => '30.00',
            'order_total' => '40.00',
        ]);
    }

    // TC-06: Updating an item not in cart returns 404 JSON
    public function test_sc003_updating_nonexistent_cart_item_returns_404(): void
    {
        $product = $this->makeProduct();
        // do NOT seed cart

        $response = $this->patchJson(route('cart.update', $product->id), ['quantity' => 1]);

        $response->assertStatus(404)
            ->assertJson(['message' => 'Item not found in cart.']);
    }

    // TC-07: Zero quantity fails validation
    public function test_sc003_zero_quantity_fails_validation(): void
    {
        $product = $this->makeProduct();
        $this->seedCart($product, 2);

        $response = $this->patch(route('cart.update', $product->id), ['quantity' => 0]);
        $response->assertRedirect();
        $response->assertSessionHasErrors('quantity');
    }

    // TC-08: Negative quantity fails validation
    public function test_sc003_negative_quantity_fails_validation(): void
    {
        $product = $this->makeProduct();
        $this->seedCart($product, 2);

        $response = $this->patch(route('cart.update', $product->id), ['quantity' => -1]);
        $response->assertRedirect();
        $response->assertSessionHasErrors('quantity');
    }

    // TC-09: Missing quantity fails validation
    public function test_sc003_missing_quantity_fails_validation(): void
    {
        $product = $this->makeProduct();
        $this->seedCart($product, 2);

        $response = $this->patch(route('cart.update', $product->id), []);
        $response->assertRedirect();
        $response->assertSessionHasErrors('quantity');
    }

    // TC-10: AJAX update with qty=1 (minimum boundary) succeeds
    public function test_sc003_minimum_quantity_one_is_accepted(): void
    {
        $product = $this->makeProduct(['price' => 15.00]);
        $this->seedCart($product, 5);

        $response = $this->patchJson(route('cart.update', $product->id), ['quantity' => 1]);

        $response->assertOk()->assertJson(['quantity' => 1, 'subtotal' => '15.00']);
    }

    // TC-11: Successful form update flashes success message
    public function test_sc003_successful_update_flashes_success_message(): void
    {
        $product = $this->makeProduct();
        $this->seedCart($product, 1);

        $response = $this->patch(route('cart.update', $product->id), ['quantity' => 2]);
        $response->assertSessionHas('success');
    }

    // TC-12: Cart update completes within one second
    public function test_sc003_update_completes_within_one_second(): void
    {
        $product = $this->makeProduct();
        $this->seedCart($product, 2);

        $start = microtime(true);
        $this->patchJson(route('cart.update', $product->id), ['quantity' => 3]);
        $duration = microtime(true) - $start;

        $this->assertLessThan(1.0, $duration);
    }
}
