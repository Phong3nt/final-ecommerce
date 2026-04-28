<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * IMP-015: DB-backed cart persistence for authenticated users.
 *
 * Coverage: SC-001, SC-002, SC-003, SC-004
 */
class CartDbPersistenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'user',  'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    }

    private function makeProduct(array $attrs = []): Product
    {
        return Product::factory()->create(array_merge(['stock' => 10, 'price' => 19.99], $attrs));
    }

    // ---------------------------------------------------------------
    // Happy-path tests
    // ---------------------------------------------------------------

    /** IMP-015-01: Authenticated user adding a product persists a DB record. */
    public function test_imp015_auth_add_to_cart_creates_db_record(): void
    {
        $user    = User::factory()->create();
        $product = $this->makeProduct();

        $this->actingAs($user)->post(route('cart.store'), [
            'product_id' => $product->id,
            'quantity'   => 2,
        ]);

        $this->assertDatabaseHas('cart_items', [
            'user_id'    => $user->id,
            'product_id' => $product->id,
            'quantity'   => 2,
        ]);
    }

    /** IMP-015-02: Adding the same product twice merges quantities in DB. */
    public function test_imp015_auth_add_same_product_twice_updates_db_qty(): void
    {
        $user    = User::factory()->create();
        $product = $this->makeProduct(['stock' => 10]);

        $this->actingAs($user)->post(route('cart.store'), ['product_id' => $product->id, 'quantity' => 2]);
        $this->actingAs($user)->post(route('cart.store'), ['product_id' => $product->id, 'quantity' => 3]);

        $this->assertDatabaseHas('cart_items', [
            'user_id'    => $user->id,
            'product_id' => $product->id,
            'quantity'   => 5,
        ]);
        $this->assertEquals(1, CartItem::where('user_id', $user->id)->count());
    }

    /** IMP-015-03: Updating quantity via PATCH also updates the DB record. */
    public function test_imp015_auth_update_cart_updates_db_record(): void
    {
        $user    = User::factory()->create();
        $product = $this->makeProduct(['stock' => 10]);

        // Seed the cart
        $this->actingAs($user)->post(route('cart.store'), ['product_id' => $product->id, 'quantity' => 2]);

        // Update quantity
        $this->actingAs($user)->patch(route('cart.update', $product->id), ['quantity' => 7]);

        $this->assertDatabaseHas('cart_items', [
            'user_id'    => $user->id,
            'product_id' => $product->id,
            'quantity'   => 7,
        ]);
    }

    /** IMP-015-04: Removing an item via DELETE also deletes the DB record. */
    public function test_imp015_auth_remove_cart_deletes_db_record(): void
    {
        $user    = User::factory()->create();
        $product = $this->makeProduct();

        $this->actingAs($user)->post(route('cart.store'), ['product_id' => $product->id, 'quantity' => 1]);
        $this->actingAs($user)->delete(route('cart.destroy', $product->id));

        $this->assertDatabaseMissing('cart_items', [
            'user_id'    => $user->id,
            'product_id' => $product->id,
        ]);
    }

    // ---------------------------------------------------------------
    // Edge-case tests
    // ---------------------------------------------------------------

    /** IMP-015-05: Guest adding a product does NOT create a DB record. */
    public function test_imp015_guest_add_does_not_create_db_record(): void
    {
        $product = $this->makeProduct();

        $this->post(route('cart.store'), ['product_id' => $product->id, 'quantity' => 1]);

        $this->assertDatabaseMissing('cart_items', ['product_id' => $product->id]);
    }

    /**
     * IMP-015-06: Auth user visiting the cart with empty session but DB items
     * has their session hydrated from the DB (cross-device restore).
     */
    public function test_imp015_cart_index_hydrates_session_from_db(): void
    {
        $user    = User::factory()->create();
        $product = $this->makeProduct();

        // Pre-seed DB cart directly (simulates another device)
        CartItem::create([
            'user_id'    => $user->id,
            'product_id' => $product->id,
            'quantity'   => 4,
        ]);

        // Visit cart with a fresh session (no cart in session)
        $response = $this->actingAs($user)->get(route('cart.index'));

        $response->assertStatus(200);
        $cart = session('cart');
        $this->assertArrayHasKey($product->id, $cart);
        $this->assertEquals(4, $cart[$product->id]['quantity']);
    }

    /**
     * IMP-015-07: Auth user visiting the cart with session items (just-logged-in)
     * pushes those items into the DB.
     */
    public function test_imp015_cart_index_merges_session_cart_into_db(): void
    {
        $user    = User::factory()->create();
        $product = $this->makeProduct();

        // Simulate a guest cart already in session
        $this->actingAs($user)
            ->withSession(['cart' => [
                $product->id => [
                    'product_id' => $product->id,
                    'name'       => $product->name,
                    'price'      => (float) $product->price,
                    'quantity'   => 3,
                    'slug'       => $product->slug,
                ],
            ]])
            ->get(route('cart.index'));

        $this->assertDatabaseHas('cart_items', [
            'user_id'    => $user->id,
            'product_id' => $product->id,
        ]);
    }

    /**
     * IMP-015-08: When session quantity is higher than DB quantity the DB is
     * updated to the higher value during the merge.
     */
    public function test_imp015_merge_takes_max_quantity_from_session(): void
    {
        $user    = User::factory()->create();
        $product = $this->makeProduct(['stock' => 10]);

        // DB has qty = 2
        CartItem::create(['user_id' => $user->id, 'product_id' => $product->id, 'quantity' => 2]);

        // Session has qty = 6 (higher)
        $this->actingAs($user)
            ->withSession(['cart' => [
                $product->id => [
                    'product_id' => $product->id,
                    'name'       => $product->name,
                    'price'      => (float) $product->price,
                    'quantity'   => 6,
                    'slug'       => $product->slug,
                ],
            ]])
            ->get(route('cart.index'));

        $this->assertDatabaseHas('cart_items', [
            'user_id'    => $user->id,
            'product_id' => $product->id,
            'quantity'   => 6,
        ]);
    }

    // ---------------------------------------------------------------
    // Negative / security tests
    // ---------------------------------------------------------------

    /** IMP-015-09: User A's cart items are not visible to user B. */
    public function test_imp015_cart_is_isolated_between_users(): void
    {
        $userA   = User::factory()->create();
        $userB   = User::factory()->create();
        $product = $this->makeProduct();

        CartItem::create(['user_id' => $userA->id, 'product_id' => $product->id, 'quantity' => 5]);

        // User B visits cart — their session should NOT contain user A's items
        $this->actingAs($userB)->get(route('cart.index'));

        $cart = session('cart');
        $this->assertEmpty($cart);

        $this->assertDatabaseMissing('cart_items', [
            'user_id'    => $userB->id,
            'product_id' => $product->id,
        ]);
    }

    /**
     * IMP-015-10: Deleting a user cascades and removes all their cart_items rows
     * (foreign-key onDelete cascade).
     */
    public function test_imp015_cart_items_deleted_when_user_is_deleted(): void
    {
        $user    = User::factory()->create();
        $product = $this->makeProduct();

        CartItem::create(['user_id' => $user->id, 'product_id' => $product->id, 'quantity' => 1]);
        $userId = $user->id;

        $user->delete();

        $this->assertDatabaseMissing('cart_items', ['user_id' => $userId]);
    }

    // ---------------------------------------------------------------
    // Accessibility / data-attribute test (via response body check)
    // ---------------------------------------------------------------

    /**
     * IMP-015-11: Auth user adding via JSON still returns the correct cart_count
     * after DB is persisted.
     */
    public function test_imp015_auth_add_json_returns_correct_cart_count(): void
    {
        $user     = User::factory()->create();
        $product1 = $this->makeProduct(['stock' => 10]);
        $product2 = $this->makeProduct(['stock' => 10]);

        $this->actingAs($user)->postJson(route('cart.store'), ['product_id' => $product1->id, 'quantity' => 2]);
        $response = $this->actingAs($user)->postJson(route('cart.store'), ['product_id' => $product2->id, 'quantity' => 3]);

        $response->assertStatus(200)
            ->assertJson(['cart_count' => 5]);

        $this->assertEquals(2, CartItem::where('user_id', $user->id)->count());
    }

    // ---------------------------------------------------------------
    // Performance test
    // ---------------------------------------------------------------

    /** IMP-015-12: Cart index responds within 2 seconds for an authenticated user. */
    public function test_imp015_performance_cart_index_under_2_seconds(): void
    {
        $user = User::factory()->create();
        $products = Product::factory()->count(20)->create(['stock' => 5, 'price' => 9.99]);

        foreach ($products as $product) {
            CartItem::create(['user_id' => $user->id, 'product_id' => $product->id, 'quantity' => 1]);
        }

        $start    = microtime(true);
        $response = $this->actingAs($user)->get(route('cart.index'));
        $elapsed  = microtime(true) - $start;

        $response->assertStatus(200);
        $this->assertLessThan(2.0, $elapsed, "Cart index took {$elapsed}s — exceeds 2s budget.");
    }
}
