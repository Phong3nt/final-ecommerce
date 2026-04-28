<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * IMP-012 — Interactive star rating input (Alpine.js, no reload)
 *
 * Verifies that the product detail page renders the Alpine.js interactive
 * star rating input widget and the visual star displays correctly:
 * star-input container, hidden rating input, star buttons with aria attributes,
 * star hint text, per-review star display, average-rating star display,
 * removal of old <select> dropdown, and Alpine component function definition.
 */
class InteractiveStarRatingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'user',  'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    }

    private function makeUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('user');
        return $user;
    }

    private function makeProduct(): Product
    {
        return Product::factory()->create([
            'slug'   => 'star-rating-product-' . uniqid(),
            'stock'  => 10,
            'status' => 'published',
        ]);
    }

    /** Create a delivered order that links user → product so $canReview = true. */
    private function purchaseProduct(User $user, Product $product): void
    {
        $order = Order::factory()->paid()->create(['user_id' => $user->id]);
        OrderItem::factory()->create([
            'order_id'   => $order->id,
            'product_id' => $product->id,
        ]);
    }

    // TC-01 (Happy): Star input container is present for eligible reviewer
    public function test_imp012_star_input_container_present_for_eligible_user(): void
    {
        $user    = $this->makeUser();
        $product = $this->makeProduct();
        $this->purchaseProduct($user, $product);

        $response = $this->actingAs($user)->get(route('products.show', $product->slug));

        $response->assertStatus(200);
        $response->assertSee('data-imp012="star-input"', false);
    }

    // TC-02 (Happy): Hidden rating input present inside star widget
    public function test_imp012_hidden_rating_input_present(): void
    {
        $user    = $this->makeUser();
        $product = $this->makeProduct();
        $this->purchaseProduct($user, $product);

        $response = $this->actingAs($user)->get(route('products.show', $product->slug));

        $response->assertSee('data-imp012="rating-input"', false);
    }

    // TC-03 (Happy): Star buttons rendered via Alpine x-for template
    public function test_imp012_star_buttons_rendered_in_widget(): void
    {
        $user    = $this->makeUser();
        $product = $this->makeProduct();
        $this->purchaseProduct($user, $product);

        $response = $this->actingAs($user)->get(route('products.show', $product->slug));

        $response->assertSee('data-imp012="star-btn"', false);
    }

    // TC-04 (Accessibility): Star buttons have aria-label attributes
    public function test_imp012_star_buttons_have_aria_labels(): void
    {
        $user    = $this->makeUser();
        $product = $this->makeProduct();
        $this->purchaseProduct($user, $product);

        $response = $this->actingAs($user)->get(route('products.show', $product->slug));

        // Alpine renders the aria-label expression in the DOM
        $response->assertSee("'Rate ' + i + ' out of 5'", false);
    }

    // TC-05 (Happy): Star hint text element present inside star widget
    public function test_imp012_star_hint_element_present(): void
    {
        $user    = $this->makeUser();
        $product = $this->makeProduct();
        $this->purchaseProduct($user, $product);

        $response = $this->actingAs($user)->get(route('products.show', $product->slug));

        $response->assertSee('data-imp012="star-hint"', false);
    }

    // TC-06 (Happy): Old <select id="rating"> is no longer present (replaced by star input)
    public function test_imp012_old_select_dropdown_is_removed(): void
    {
        $user    = $this->makeUser();
        $product = $this->makeProduct();
        $this->purchaseProduct($user, $product);

        $response = $this->actingAs($user)->get(route('products.show', $product->slug));

        $response->assertDontSee('<select id="rating"', false);
        $response->assertDontSee('Rating (1–5):', false);
    }

    // TC-07 (Happy): Per-review star display rendered for existing reviews
    public function test_imp012_review_stars_displayed_for_existing_reviews(): void
    {
        $reviewer = $this->makeUser();
        $product  = $this->makeProduct();
        $this->purchaseProduct($reviewer, $product);

        Review::create([
            'user_id'    => $reviewer->id,
            'product_id' => $product->id,
            'rating'     => 4,
            'comment'    => 'Very good!',
        ]);

        $viewer   = $this->makeUser();
        $response = $this->actingAs($viewer)->get(route('products.show', $product->slug));

        $response->assertSee('data-imp012="review-stars"', false);
    }

    // TC-08 (Happy): Average rating star display present when reviews exist
    public function test_imp012_average_rating_star_display_present(): void
    {
        $reviewer = $this->makeUser();
        $product  = $this->makeProduct();
        $this->purchaseProduct($reviewer, $product);

        Review::create([
            'user_id'    => $reviewer->id,
            'product_id' => $product->id,
            'rating'     => 5,
            'comment'    => 'Excellent!',
        ]);
        $product->update(['rating' => 5.00]);

        $viewer   = $this->makeUser();
        $response = $this->actingAs($viewer)->get(route('products.show', $product->slug));

        $response->assertSee('data-imp012="average-rating"', false);
        $response->assertSee('data-imp012="avg-text"', false);
    }

    // TC-09 (Happy): Alpine component function imp012StarRating is defined in script
    public function test_imp012_alpine_component_function_defined(): void
    {
        $user    = $this->makeUser();
        $product = $this->makeProduct();
        $this->purchaseProduct($user, $product);

        $response = $this->actingAs($user)->get(route('products.show', $product->slug));

        $response->assertSee('imp012StarRating', false);
    }

    // TC-10 (Edge): Star input NOT present when user cannot review (non-purchaser)
    public function test_imp012_star_input_absent_for_non_purchaser(): void
    {
        $product = $this->makeProduct();
        $user    = $this->makeUser(); // no purchase

        $response = $this->actingAs($user)->get(route('products.show', $product->slug));

        $response->assertStatus(200);
        $response->assertDontSee('data-imp012="star-input"', false);
    }

    // TC-11 (Edge): Star input NOT present when user already reviewed the product
    public function test_imp012_star_input_absent_after_user_already_reviewed(): void
    {
        $user    = $this->makeUser();
        $product = $this->makeProduct();
        $this->purchaseProduct($user, $product);

        Review::create([
            'user_id'    => $user->id,
            'product_id' => $product->id,
            'rating'     => 3,
            'comment'    => 'Good.',
        ]);

        $response = $this->actingAs($user)->get(route('products.show', $product->slug));

        $response->assertStatus(200);
        $response->assertDontSee('data-imp012="star-input"', false);
    }

    // TC-12 (Performance): Product detail with star rating responds within 2 seconds
    public function test_imp012_product_detail_with_star_rating_responds_within_two_seconds(): void
    {
        $user    = $this->makeUser();
        $product = $this->makeProduct();
        $this->purchaseProduct($user, $product);

        $start    = microtime(true);
        $response = $this->actingAs($user)->get(route('products.show', $product->slug));
        $elapsed  = microtime(true) - $start;

        $response->assertStatus(200);
        $this->assertLessThan(2.0, $elapsed, 'Product detail with star rating exceeded 2 seconds.');
    }
}
