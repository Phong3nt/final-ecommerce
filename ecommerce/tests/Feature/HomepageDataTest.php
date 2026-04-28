<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CouponSeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * IMP-037 — Real CRUD seed data: categories, products, users, coupons
 * and dynamic homepage widgets (Browse by Category, Featured Products).
 *
 * Test plan:
 *   TC-01  CategorySeeder creates exactly 7 categories
 *   TC-02  All 7 expected category names are present
 *   TC-03  ProductSeeder creates ≥ 20 products per category (7 × 20 = 140+)
 *   TC-04  UserSeeder creates 5 verified users with role=user
 *   TC-05  CouponSeeder creates exactly 5 coupons
 *   TC-06  All 5 expected coupon codes exist and are active
 *   TC-07  Homepage returns HTTP 200 (even with empty DB)
 *   TC-08  Homepage renders Browse by Category section
 *   TC-09  Homepage shows DB category names when categories exist
 *   TC-10  Homepage renders Featured Products section
 *   TC-11  Homepage shows DB product names when products exist
 *   TC-12  Homepage category link uses integer category_id (not a string slug)
 *   TC-13  Homepage product card shows formatted price
 *   TC-14  Homepage product card links to product show page
 *   TC-15  Homepage shows empty-state message when no categories in DB
 */
class HomepageDataTest extends TestCase
{
    use RefreshDatabase;

    // ──────────────────────────────────────────────────────────────────────
    // Seeder Tests
    // ──────────────────────────────────────────────────────────────────────

    // TC-01: CategorySeeder creates exactly 7 categories
    public function test_imp037_category_seeder_creates_seven_categories(): void
    {
        $this->seed(CategorySeeder::class);

        $this->assertSame(7, Category::count());
    }

    // TC-02: All 7 expected category names are present
    public function test_imp037_all_expected_category_names_exist(): void
    {
        $this->seed(CategorySeeder::class);

        $expected = [
            'Battery Chargers',
            'Electronics',
            'Headphones & Headsets',
            'Laptops',
            'Smartphones',
            'Smartwatches',
            'Tablets',
        ];

        foreach ($expected as $name) {
            $this->assertDatabaseHas('categories', ['name' => $name]);
        }
    }

    // TC-03: ProductSeeder creates >= 20 products per category
    public function test_imp037_product_seeder_creates_at_least_20_per_category(): void
    {
        $this->seed(CategorySeeder::class);
        $this->seed(ProductSeeder::class);

        $categoryNames = [
            'Electronics',
            'Laptops',
            'Smartphones',
            'Smartwatches',
            'Tablets',
            'Headphones & Headsets',
            'Battery Chargers',
        ];

        foreach ($categoryNames as $name) {
            $category = Category::where('name', $name)->firstOrFail();
            $count = Product::where('category_id', $category->id)->count();
            $this->assertGreaterThanOrEqual(20, $count, "Category [{$name}] should have >= 20 products, found {$count}");
        }
    }

    // TC-04: UserSeeder creates 5 verified users with role=user
    public function test_imp037_user_seeder_creates_five_verified_users(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(UserSeeder::class);

        $verifiedUsers = User::whereNotNull('email_verified_at')->get();
        $this->assertCount(5, $verifiedUsers);

        // Each user should have the 'user' role via Spatie permissions
        foreach ($verifiedUsers as $user) {
            $this->assertTrue($user->hasRole('user'), "User [{$user->email}] should have the 'user' role");
        }
    }

    // TC-05: CouponSeeder creates exactly 5 coupons
    public function test_imp037_coupon_seeder_creates_five_coupons(): void
    {
        $this->seed(CouponSeeder::class);

        $this->assertSame(5, Coupon::count());
    }

    // TC-06: All 5 expected coupon codes exist and are active
    public function test_imp037_all_expected_coupon_codes_exist_and_are_active(): void
    {
        $this->seed(CouponSeeder::class);

        $codes = ['SUMMER10', 'NEWUSER20', 'FLASH15', 'LOYALTY5', 'BUNDLE30'];

        foreach ($codes as $code) {
            $this->assertDatabaseHas('coupons', ['code' => $code, 'is_active' => true]);
        }
    }

    // ──────────────────────────────────────────────────────────────────────
    // Homepage UI Tests
    // ──────────────────────────────────────────────────────────────────────

    // TC-07: Homepage returns HTTP 200 (even with empty DB)
    public function test_imp037_homepage_returns_200_with_empty_db(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    // TC-08: Homepage renders Browse by Category section
    public function test_imp037_homepage_has_browse_by_category_heading(): void
    {
        $response = $this->get('/');

        $response->assertSee('Browse by Category');
    }

    // TC-09: Homepage shows DB category names when categories exist
    public function test_imp037_homepage_shows_db_category_names(): void
    {
        $this->seed(CategorySeeder::class);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Electronics');
        $response->assertSee('Laptops');
        $response->assertSee('Smartphones');
        $response->assertSee('Smartwatches');
        $response->assertSee('Tablets');
        $response->assertSee('Battery Chargers');
    }

    // TC-10: Homepage renders Featured Products section
    public function test_imp037_homepage_has_featured_products_heading(): void
    {
        $response = $this->get('/');

        $response->assertSee('Featured Products');
    }

    // TC-11: Homepage shows DB product names when products exist
    public function test_imp037_homepage_shows_db_product_names(): void
    {
        $this->seed(CategorySeeder::class);
        $this->seed(ProductSeeder::class);

        $response = $this->get('/');

        $response->assertStatus(200);

        // Verify at least one product from the seeded data appears on the page
        $latest = Product::with('category')
            ->where('status', 'active')
            ->where('stock', '>', 0)
            ->whereNotNull('image')
            ->latest()
            ->first();

        $this->assertNotNull($latest);
        $response->assertSee($latest->name);
    }

    // TC-12: Homepage category link uses integer category_id (not a string slug)
    public function test_imp037_homepage_category_links_use_integer_ids(): void
    {
        $this->seed(CategorySeeder::class);

        $response = $this->get('/');
        $response->assertStatus(200);

        $category = Category::first();
        // Link should contain numeric ID, not a string slug like 'electronics'
        $response->assertSee('category=' . $category->id, false);
    }

    // TC-13: Homepage product card shows formatted price
    public function test_imp037_homepage_featured_product_card_shows_price(): void
    {
        $this->seed(CategorySeeder::class);
        $this->seed(ProductSeeder::class);

        $response = $this->get('/');
        $response->assertStatus(200);

        $product = Product::where('status', 'active')
            ->where('stock', '>', 0)
            ->whereNotNull('image')
            ->latest()
            ->first();

        $formattedPrice = '$' . number_format($product->price, 2);
        $response->assertSee($formattedPrice, false);
    }

    // TC-14: Homepage product card links to product show page
    public function test_imp037_homepage_featured_product_card_has_view_link(): void
    {
        $this->seed(CategorySeeder::class);
        $this->seed(ProductSeeder::class);

        $response = $this->get('/');
        $response->assertStatus(200);

        $product = Product::where('status', 'active')
            ->where('stock', '>', 0)
            ->whereNotNull('image')
            ->latest()
            ->first();

        $response->assertSee(route('products.show', ['product' => $product->slug]), false);
    }

    // TC-15: Homepage shows empty-state message when no categories in DB
    public function test_imp037_homepage_shows_empty_state_when_no_categories(): void
    {
        // No seeder run — DB is empty (RefreshDatabase)
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('No categories available yet');
    }
}
