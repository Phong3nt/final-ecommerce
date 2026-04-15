<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartRemoveTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeProduct(array $attrs = []): Product
    {
        return Product::factory()->create(array_merge([
            'price' => 10.00,
            'stock' => 5,
        ], $attrs));
    }

    private function seedCart(array $products): array
    {
        $cart = [];
        foreach ($products as $product) {
            $qty = $product->_qty ?? 1;
            $cart[$product->id] = [
                'product_id' => $product->id,
                'name'       => $product->name,
                'price'      => $product->price,
                'quantity'   => $qty,
                'slug'       => $product->slug,
            ];
        }
        return $cart;
    }

    private function deleteForm(int $productId): array
    {
        return ['_method' => 'DELETE'];
    }

    // -------------------------------------------------------------------------
    // TC-01: regular form submit redirects back to cart
    // -------------------------------------------------------------------------

    /** @test */
    public function sc004_remove_redirects_to_cart(): void
    {
        $product = $this->makeProduct();
        $cart    = $this->seedCart([$product]);

        $this->withSession(['cart' => $cart])
             ->post(route('cart.destroy', $product->id), ['_method' => 'DELETE'])
             ->assertRedirect(route('cart.index'));
    }

    // -------------------------------------------------------------------------
    // TC-02: item is removed from session after DELETE
    // -------------------------------------------------------------------------

    /** @test */
    public function sc004_removes_item_from_session(): void
    {
        $product = $this->makeProduct();
        $cart    = $this->seedCart([$product]);

        $response = $this->withSession(['cart' => $cart])
                         ->post(route('cart.destroy', $product->id), ['_method' => 'DELETE']);

        $response->assertSessionMissing("cart.{$product->id}");
    }

    // -------------------------------------------------------------------------
    // TC-03: order total recalculates after remove (form mode)
    // -------------------------------------------------------------------------

    /** @test */
    public function sc004_order_total_recalculates_after_remove(): void
    {
        $p1 = $this->makeProduct(['price' => 20.00]);
        $p2 = $this->makeProduct(['price' => 30.00]);
        $cart = $this->seedCart([$p1, $p2]);
        $cart[$p1->id]['quantity'] = 1;
        $cart[$p2->id]['quantity'] = 1;

        // Remove p1; only p2 ($30) should remain
        $response = $this->withSession(['cart' => $cart])
                         ->post(route('cart.destroy', $p1->id), ['_method' => 'DELETE'])
                         ->assertRedirect(route('cart.index'));

        $remaining = $response->getSession()->get('cart', []);
        $this->assertArrayNotHasKey($p1->id, $remaining);
        $this->assertArrayHasKey($p2->id, $remaining);
    }

    // -------------------------------------------------------------------------
    // TC-04: AJAX DELETE returns JSON with cart_count and order_total
    // -------------------------------------------------------------------------

    /** @test */
    public function sc004_ajax_returns_json_with_cart_count_and_order_total(): void
    {
        $p1 = $this->makeProduct(['price' => 15.00]);
        $p2 = $this->makeProduct(['price' => 10.00]);
        $cart = $this->seedCart([$p1, $p2]);
        $cart[$p1->id]['quantity'] = 2; // subtotal: 30
        $cart[$p2->id]['quantity'] = 1; // subtotal: 10  → total before: 40

        $this->withSession(['cart' => $cart])
             ->deleteJson(route('cart.destroy', $p1->id))
             ->assertOk()
             ->assertJsonStructure(['message', 'cart_count', 'order_total'])
             ->assertJson([
                 'message'     => 'Item removed from cart.',
                 'cart_count'  => 1,
                 'order_total' => '10.00',
             ]);
    }

    // -------------------------------------------------------------------------
    // TC-05: removing last item returns cart_count=0 and order_total="0.00"
    // -------------------------------------------------------------------------

    /** @test */
    public function sc004_removing_last_item_returns_empty_cart(): void
    {
        $product = $this->makeProduct(['price' => 25.00]);
        $cart    = $this->seedCart([$product]);

        $this->withSession(['cart' => $cart])
             ->deleteJson(route('cart.destroy', $product->id))
             ->assertOk()
             ->assertJson([
                 'cart_count'  => 0,
                 'order_total' => '0.00',
             ]);
    }

    // -------------------------------------------------------------------------
    // TC-06: AJAX remove of nonexistent cart item returns 404
    // -------------------------------------------------------------------------

    /** @test */
    public function sc004_removing_nonexistent_item_returns_404_json(): void
    {
        $product = $this->makeProduct();
        // Cart is empty — product not in cart

        $this->withSession(['cart' => []])
             ->deleteJson(route('cart.destroy', $product->id))
             ->assertNotFound()
             ->assertJson(['message' => 'Item not found in cart.']);
    }

    // -------------------------------------------------------------------------
    // TC-07: form submit remove of nonexistent item redirects with error
    // -------------------------------------------------------------------------

    /** @test */
    public function sc004_removing_nonexistent_item_redirects_with_error(): void
    {
        $product = $this->makeProduct();

        $this->withSession(['cart' => []])
             ->post(route('cart.destroy', $product->id), ['_method' => 'DELETE'])
             ->assertRedirect(route('cart.index'))
             ->assertSessionHasErrors('cart');
    }

    // -------------------------------------------------------------------------
    // TC-08: guest (unauthenticated) can remove from cart
    // -------------------------------------------------------------------------

    /** @test */
    public function sc004_guest_can_remove_item_from_cart(): void
    {
        $product = $this->makeProduct();
        $cart    = $this->seedCart([$product]);

        // No actingAs — guest user
        $this->withSession(['cart' => $cart])
             ->deleteJson(route('cart.destroy', $product->id))
             ->assertOk()
             ->assertJson(['cart_count' => 0]);
    }

    // -------------------------------------------------------------------------
    // TC-09: authenticated user can remove from cart
    // -------------------------------------------------------------------------

    /** @test */
    public function sc004_authenticated_user_can_remove_item_from_cart(): void
    {
        $user    = \App\Models\User::factory()->create();
        $product = $this->makeProduct();
        $cart    = $this->seedCart([$product]);

        $this->actingAs($user)
             ->withSession(['cart' => $cart])
             ->deleteJson(route('cart.destroy', $product->id))
             ->assertOk()
             ->assertJson(['cart_count' => 0]);
    }

    // -------------------------------------------------------------------------
    // TC-10: only the targeted item is removed; other items stay
    // -------------------------------------------------------------------------

    /** @test */
    public function sc004_other_items_remain_after_remove(): void
    {
        $p1 = $this->makeProduct(['price' => 10.00]);
        $p2 = $this->makeProduct(['price' => 20.00]);
        $p3 = $this->makeProduct(['price' => 30.00]);
        $cart = $this->seedCart([$p1, $p2, $p3]);

        $response = $this->withSession(['cart' => $cart])
                         ->post(route('cart.destroy', $p2->id), ['_method' => 'DELETE'])
                         ->assertRedirect(route('cart.index'));

        $remaining = $response->getSession()->get('cart', []);
        $this->assertArrayHasKey($p1->id, $remaining);
        $this->assertArrayNotHasKey($p2->id, $remaining);
        $this->assertArrayHasKey($p3->id, $remaining);
    }

    // -------------------------------------------------------------------------
    // TC-11: successful form remove flashes success message
    // -------------------------------------------------------------------------

    /** @test */
    public function sc004_successful_remove_flashes_success_message(): void
    {
        $product = $this->makeProduct();
        $cart    = $this->seedCart([$product]);

        $this->withSession(['cart' => $cart])
             ->post(route('cart.destroy', $product->id), ['_method' => 'DELETE'])
             ->assertSessionHas('success');
    }

    // -------------------------------------------------------------------------
    // TC-12: remove completes within one second
    // -------------------------------------------------------------------------

    /** @test */
    public function sc004_remove_completes_within_one_second(): void
    {
        $product = $this->makeProduct();
        $cart    = $this->seedCart([$product]);

        $start = microtime(true);

        $this->withSession(['cart' => $cart])
             ->deleteJson(route('cart.destroy', $product->id))
             ->assertOk();

        $this->assertLessThan(1.0, microtime(true) - $start);
    }
}
