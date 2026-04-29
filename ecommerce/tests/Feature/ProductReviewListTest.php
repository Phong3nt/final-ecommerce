<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * RV-002 — As a user, I want to see reviews on a product page so I can make informed decisions.
 *
 * Acceptance criteria:
 *   - Average rating shown prominently
 *   - Reviews paginated (5/page)
 */
class ProductReviewListTest extends TestCase
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

    /**
     * Insert a review directly — bypasses purchase gate so view-layer tests stay fast.
     * Uses explicit created_at for deterministic ordering in pagination tests.
     */
    private function createReview(
        Product $product,
        int $rating = 4,
        string $comment = 'Great product!',
        ?Carbon $createdAt = null
    ): Review {
        $user   = User::factory()->create();
        $review = new Review([
            'user_id'    => $user->id,
            'product_id' => $product->id,
            'rating'     => $rating,
            'comment'    => $comment,
        ]);
        if ($createdAt !== null) {
            $review->created_at = $createdAt;
            $review->updated_at = $createdAt;
        }
        $review->save();

        return $review;
    }

    // -------------------------------------------------------------------------
    // TC-01: Reviews section heading shown on product page
    // -------------------------------------------------------------------------

    /** @test */
    public function test_rv002_reviews_section_shown_on_product_page(): void
    {
        $product = $this->makeProduct();

        $response = $this->get(route('products.show', $product->slug));

        $response->assertStatus(200);
        $response->assertSee('Customer Reviews');
    }

    // -------------------------------------------------------------------------
    // TC-02: Product with no reviews shows "No reviews yet"
    // -------------------------------------------------------------------------

    /** @test */
    public function test_rv002_product_with_no_reviews_shows_no_reviews_message(): void
    {
        $product = $this->makeProduct();

        $response = $this->get(route('products.show', $product->slug));

        $response->assertStatus(200);
        $response->assertSee('No reviews yet');
    }

    // -------------------------------------------------------------------------
    // TC-03: Average rating shown prominently when reviews exist
    // -------------------------------------------------------------------------

    /** @test */
    public function test_rv002_average_rating_shown_prominently_when_reviews_exist(): void
    {
        $product = $this->makeProduct();
        $this->createReview($product, rating: 4, comment: 'Good product.');

        $response = $this->get(route('products.show', $product->slug));

        $response->assertStatus(200);
        $response->assertSee('Average Rating');
    }

    // -------------------------------------------------------------------------
    // TC-04: Average rating calculated correctly (3 + 5 = 4.0 average)
    // -------------------------------------------------------------------------

    /** @test */
    public function test_rv002_average_rating_calculated_correctly(): void
    {
        $product = $this->makeProduct();
        $this->createReview($product, rating: 3, comment: 'Average experience.');
        $this->createReview($product, rating: 5, comment: 'Excellent experience.');

        $response = $this->get(route('products.show', $product->slug));

        $response->assertSee('Average Rating: 4.0');
    }

    // -------------------------------------------------------------------------
    // TC-05: Each review shows reviewer name
    // -------------------------------------------------------------------------

    /** @test */
    public function test_rv002_review_shows_reviewer_name(): void
    {
        $product = $this->makeProduct();
        $user    = $this->makeUser(['name' => 'Bob Reviewer']);

        $review = new Review([
            'user_id'    => $user->id,
            'product_id' => $product->id,
            'rating'     => 5,
            'comment'    => 'Love this product.',
        ]);
        $review->save();

        $response = $this->get(route('products.show', $product->slug));

        $response->assertSee('Bob Reviewer');
    }

    // -------------------------------------------------------------------------
    // TC-06: Each review shows the star rating
    // -------------------------------------------------------------------------

    /** @test */
    public function test_rv002_review_shows_star_rating(): void
    {
        $product = $this->makeProduct();
        $this->createReview($product, rating: 4, comment: 'Solid product.');

        $response = $this->get(route('products.show', $product->slug));

        // "Rating: 4 / 5" is rendered for each review in the list
        $response->assertSee('Rating: 4 / 5');
    }

    // -------------------------------------------------------------------------
    // TC-07: Each review shows the comment text
    // -------------------------------------------------------------------------

    /** @test */
    public function test_rv002_review_shows_comment_text(): void
    {
        $product = $this->makeProduct();
        $this->createReview($product, rating: 3, comment: 'Absolutely fantastic quality here.');

        $response = $this->get(route('products.show', $product->slug));

        $response->assertSee('Absolutely fantastic quality here.');
    }

    // -------------------------------------------------------------------------
    // TC-08: Reviews paginated 5/page — oldest review not on page 1
    // -------------------------------------------------------------------------

    /** @test */
    public function test_rv002_reviews_paginated_five_per_page(): void
    {
        $product = $this->makeProduct();

        // Oldest review — will appear on page 2 (latest() ordering, created 60 min ago)
        $this->createReview($product, 2, 'Oldest review goes to page two', Carbon::now()->subMinutes(60));

        // 5 newer reviews — will fill page 1
        for ($i = 1; $i <= 5; $i++) {
            $this->createReview($product, 5, "Recent review {$i}", Carbon::now()->subMinutes($i));
        }

        $response = $this->get(route('products.show', $product->slug));

        $response->assertStatus(200);
        // Page 1 shows the 5 newest — the oldest must NOT be here
        $response->assertDontSee('Oldest review goes to page two');
    }

    // -------------------------------------------------------------------------
    // TC-09: Page 2 shows the remaining (oldest) review
    // -------------------------------------------------------------------------

    /** @test */
    public function test_rv002_page_two_shows_remaining_review(): void
    {
        $product = $this->makeProduct();

        // Oldest review — will appear on page 2
        $this->createReview($product, 2, 'Oldest review goes to page two', Carbon::now()->subMinutes(60));

        // 5 newer reviews — fill page 1
        for ($i = 1; $i <= 5; $i++) {
            $this->createReview($product, 5, "Recent review {$i}", Carbon::now()->subMinutes($i));
        }

        $response = $this->get(route('products.show', $product->slug) . '?page=2');

        $response->assertStatus(200);
        $response->assertSee('Oldest review goes to page two');
    }

    // -------------------------------------------------------------------------
    // TC-10: Guest can view reviews without authentication
    // -------------------------------------------------------------------------

    /** @test */
    public function test_rv002_guest_can_see_reviews(): void
    {
        $product = $this->makeProduct();
        $this->createReview($product, rating: 4, comment: 'Great value for money.');

        // Unauthenticated request
        $response = $this->get(route('products.show', $product->slug));

        $response->assertStatus(200);
        $response->assertSee('Great value for money.');
    }

    // -------------------------------------------------------------------------
    // TC-11: Single review — average equals that review's rating
    // -------------------------------------------------------------------------

    /** @test */
    public function test_rv002_single_review_average_equals_review_rating(): void
    {
        $product = $this->makeProduct();
        $this->createReview($product, rating: 4, comment: 'Solo reviewer here.');

        $response = $this->get(route('products.show', $product->slug));

        $response->assertSee('Average Rating: 4.0');
    }

    // -------------------------------------------------------------------------
    // TC-12: product.rating updated after review submission via HTTP
    // -------------------------------------------------------------------------

    /** @test */
    public function test_rv002_product_rating_updated_after_review_submission(): void
    {
        $user    = User::factory()->create();
        $product = $this->makeProduct();

        // Create a delivered order with this product
        $order = \App\Models\Order::factory()->delivered()->create(['user_id' => $user->id]);
        \App\Models\OrderItem::factory()->create([
            'order_id'   => $order->id,
            'product_id' => $product->id,
        ]);

        $this->actingAs($user)->post(
            route('reviews.store', $product->slug),
            ['rating' => 5, 'comment' => 'Five stars all the way!']
        );

        $this->assertDatabaseHas('products', [
            'id'     => $product->id,
            'rating' => 5.00,
        ]);
    }
}
