<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * RV-001 — As a user, I want to leave a review and star rating on a purchased product
 * so others benefit from my experience.
 *
 * Acceptance criteria:
 *   - Only users who purchased the product can review
 *   - 1–5 star rating + text comment
 *   - One review per product per user
 */
class ProductReviewTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeUser(array $overrides = []): User
    {
        return User::factory()->create($overrides);
    }

    private function makeProduct(): Product
    {
        return Product::factory()->create(['status' => 'published', 'stock' => 10]);
    }

    /** Create a paid order that contains the given product for the given user. */
    private function purchaseProduct(User $user, Product $product): Order
    {
        $order = Order::factory()->paid()->create(['user_id' => $user->id]);
        OrderItem::factory()->create([
            'order_id'   => $order->id,
            'product_id' => $product->id,
        ]);

        return $order;
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'rating'  => 4,
            'comment' => 'Great product, highly recommend!',
        ], $overrides);
    }

    // -------------------------------------------------------------------------
    // TC-01: Security — Guest is redirected to login when submitting a review
    // -------------------------------------------------------------------------

    /** @test */
    public function test_rv001_guest_is_redirected_to_login_when_submitting_review(): void
    {
        $product = $this->makeProduct();

        $response = $this->post(
            route('reviews.store', $product->slug),
            $this->validPayload()
        );

        $response->assertRedirect(route('login'));
        $this->assertDatabaseCount('product_reviews', 0);
    }

    // -------------------------------------------------------------------------
    // TC-02: Security — User who has NOT purchased gets an error
    // -------------------------------------------------------------------------

    /** @test */
    public function test_rv001_unpurchased_user_cannot_submit_review(): void
    {
        $user    = $this->makeUser();
        $product = $this->makeProduct();

        $response = $this->actingAs($user)->post(
            route('reviews.store', $product->slug),
            $this->validPayload()
        );

        $response->assertRedirect(route('products.show', $product->slug));
        $response->assertSessionHasErrors('review');
        $this->assertDatabaseCount('product_reviews', 0);
    }

    // -------------------------------------------------------------------------
    // TC-03: Happy — User who purchased can submit a review (stored in DB)
    // -------------------------------------------------------------------------

    /** @test */
    public function test_rv001_purchased_user_can_submit_review(): void
    {
        $user    = $this->makeUser();
        $product = $this->makeProduct();
        $this->purchaseProduct($user, $product);

        $response = $this->actingAs($user)->post(
            route('reviews.store', $product->slug),
            $this->validPayload(['rating' => 5, 'comment' => 'Excellent!'])
        );

        $response->assertRedirect(route('products.show', $product->slug));
        $this->assertDatabaseHas('product_reviews', [
            'user_id'    => $user->id,
            'product_id' => $product->id,
            'rating'     => 5,
            'comment'    => 'Excellent!',
        ]);
    }

    // -------------------------------------------------------------------------
    // TC-04: Edge — User cannot submit a second review for the same product
    // -------------------------------------------------------------------------

    /** @test */
    public function test_rv001_user_cannot_submit_second_review_for_same_product(): void
    {
        $user    = $this->makeUser();
        $product = $this->makeProduct();
        $this->purchaseProduct($user, $product);

        // First review
        $this->actingAs($user)->post(
            route('reviews.store', $product->slug),
            $this->validPayload(['rating' => 3, 'comment' => 'Okay product.'])
        );

        $this->assertDatabaseCount('product_reviews', 1);

        // Attempt second review
        $response = $this->actingAs($user)->post(
            route('reviews.store', $product->slug),
            $this->validPayload(['rating' => 5, 'comment' => 'Changing my mind!'])
        );

        $response->assertRedirect(route('products.show', $product->slug));
        $response->assertSessionHasErrors('review');
        $this->assertDatabaseCount('product_reviews', 1);
    }

    // -------------------------------------------------------------------------
    // TC-05: Happy — Review form shown on product page for eligible purchaser
    // -------------------------------------------------------------------------

    /** @test */
    public function test_rv001_review_form_shown_for_eligible_purchaser(): void
    {
        $user    = $this->makeUser();
        $product = $this->makeProduct();
        $this->purchaseProduct($user, $product);

        $response = $this->actingAs($user)->get(route('products.show', $product->slug));

        $response->assertStatus(200);
        $response->assertSee('Leave a Review');
        $response->assertSee('/products/' . $product->slug . '/reviews', false);
    }

    // -------------------------------------------------------------------------
    // TC-06: Security — Review form hidden for non-purchaser
    // -------------------------------------------------------------------------

    /** @test */
    public function test_rv001_review_form_hidden_for_non_purchaser(): void
    {
        $user    = $this->makeUser();
        $product = $this->makeProduct();

        // User has no purchase for this product
        $response = $this->actingAs($user)->get(route('products.show', $product->slug));

        $response->assertStatus(200);
        $response->assertDontSee('Leave a Review');
    }

    // -------------------------------------------------------------------------
    // TC-07: Edge — Rating of 0 fails validation
    // -------------------------------------------------------------------------

    /** @test */
    public function test_rv001_rating_below_minimum_fails_validation(): void
    {
        $user    = $this->makeUser();
        $product = $this->makeProduct();
        $this->purchaseProduct($user, $product);

        $response = $this->actingAs($user)->post(
            route('reviews.store', $product->slug),
            $this->validPayload(['rating' => 0])
        );

        $response->assertSessionHasErrors('rating');
        $this->assertDatabaseCount('product_reviews', 0);
    }

    // -------------------------------------------------------------------------
    // TC-08: Edge — Rating of 6 fails validation
    // -------------------------------------------------------------------------

    /** @test */
    public function test_rv001_rating_above_maximum_fails_validation(): void
    {
        $user    = $this->makeUser();
        $product = $this->makeProduct();
        $this->purchaseProduct($user, $product);

        $response = $this->actingAs($user)->post(
            route('reviews.store', $product->slug),
            $this->validPayload(['rating' => 6])
        );

        $response->assertSessionHasErrors('rating');
        $this->assertDatabaseCount('product_reviews', 0);
    }

    // -------------------------------------------------------------------------
    // TC-09: Edge — Comment is required
    // -------------------------------------------------------------------------

    /** @test */
    public function test_rv001_comment_is_required(): void
    {
        $user    = $this->makeUser();
        $product = $this->makeProduct();
        $this->purchaseProduct($user, $product);

        $response = $this->actingAs($user)->post(
            route('reviews.store', $product->slug),
            $this->validPayload(['comment' => ''])
        );

        $response->assertSessionHasErrors('comment');
        $this->assertDatabaseCount('product_reviews', 0);
    }

    // -------------------------------------------------------------------------
    // TC-10: Happy — Review submission redirects to product page with success flash
    // -------------------------------------------------------------------------

    /** @test */
    public function test_rv001_successful_review_redirects_with_success_flash(): void
    {
        $user    = $this->makeUser();
        $product = $this->makeProduct();
        $this->purchaseProduct($user, $product);

        $response = $this->actingAs($user)->post(
            route('reviews.store', $product->slug),
            $this->validPayload()
        );

        $response->assertRedirect(route('products.show', $product->slug));
        $response->assertSessionHas('success', 'Your review has been submitted.');
    }

    // -------------------------------------------------------------------------
    // TC-11: Happy — Different users can each review the same product
    // -------------------------------------------------------------------------

    /** @test */
    public function test_rv001_different_users_can_each_review_same_product(): void
    {
        $userA   = $this->makeUser();
        $userB   = $this->makeUser();
        $product = $this->makeProduct();

        $this->purchaseProduct($userA, $product);
        $this->purchaseProduct($userB, $product);

        $this->actingAs($userA)->post(
            route('reviews.store', $product->slug),
            $this->validPayload(['rating' => 4, 'comment' => 'Good from A'])
        );

        $this->actingAs($userB)->post(
            route('reviews.store', $product->slug),
            $this->validPayload(['rating' => 5, 'comment' => 'Great from B'])
        );

        $this->assertDatabaseCount('product_reviews', 2);
        $this->assertDatabaseHas('product_reviews', ['user_id' => $userA->id, 'product_id' => $product->id]);
        $this->assertDatabaseHas('product_reviews', ['user_id' => $userB->id, 'product_id' => $product->id]);
    }

    // -------------------------------------------------------------------------
    // TC-12: Happy — Reviewer's name and rating shown on product detail page
    // -------------------------------------------------------------------------

    /** @test */
    public function test_rv001_reviewer_name_and_rating_shown_on_product_detail_page(): void
    {
        $user    = $this->makeUser(['name' => 'Alice Tester']);
        $product = $this->makeProduct();
        $this->purchaseProduct($user, $product);

        // Submit the review
        $this->actingAs($user)->post(
            route('reviews.store', $product->slug),
            $this->validPayload(['rating' => 5, 'comment' => 'Brilliant!'])
        );

        // Visit product page as the same user — their review should be shown
        $response = $this->actingAs($user)->get(route('products.show', $product->slug));

        $response->assertStatus(200);
        $response->assertSee('Alice Tester');
        $response->assertSee('5');
    }
}
