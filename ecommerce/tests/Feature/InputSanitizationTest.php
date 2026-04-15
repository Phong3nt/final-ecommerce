<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * NF-002 — All user inputs sanitized; no raw SQL (Eloquent / Query Builder only).
 *
 * Strategy:
 *   1. Audit — verify no raw DB::statement / DB::select etc. are reachable from controllers.
 *   2. Mass-assignment protection — all models declare $fillable, never $guarded = [].
 *   3. SQL-injection safety — send injection strings through every user-facing input and
 *      confirm the app responds correctly (no 500, no data leak).
 *   4. XSS safety — stored values are returned escaped through Blade {{ }}.
 */
class InputSanitizationTest extends TestCase
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

    // ---------------------------------------------------------------
    // TC-01  Model audit — User has $fillable (mass-assignment protected)
    // ---------------------------------------------------------------

    /** @test */
    public function nf002_user_model_has_fillable_not_open_guarded(): void
    {
        $user = new User();
        $this->assertNotEmpty($user->getFillable());
        // $guarded = [] would be: getGuarded() === []  AND getFillable() === []
        $this->assertFalse(
            empty($user->getFillable()) && empty($user->getGuarded()),
            'User model must not use open $guarded = [].'
        );
    }

    // ---------------------------------------------------------------
    // TC-02  Model audit — Product has $fillable
    // ---------------------------------------------------------------

    /** @test */
    public function nf002_product_model_has_fillable_not_open_guarded(): void
    {
        $product = new Product();
        $this->assertNotEmpty($product->getFillable());
    }

    // ---------------------------------------------------------------
    // TC-03  Model audit — Order has $fillable
    // ---------------------------------------------------------------

    /** @test */
    public function nf002_order_model_has_fillable_not_open_guarded(): void
    {
        $order = new \App\Models\Order();
        $this->assertNotEmpty($order->getFillable());
    }

    // ---------------------------------------------------------------
    // TC-04  SQL injection — search query is safely bound
    // ---------------------------------------------------------------

    /** @test */
    public function nf002_sql_injection_in_search_query_returns_200_not_500(): void
    {
        Product::factory()->create(['name' => 'Safe Product', 'stock' => 5]);

        $response = $this->get(route('products.search', ['q' => "' OR '1'='1"]));

        $response->assertStatus(200);
    }

    // ---------------------------------------------------------------
    // TC-05  SQL injection — search does not return all products
    // ---------------------------------------------------------------

    /** @test */
    public function nf002_sql_injection_in_search_does_not_return_all_products(): void
    {
        Product::factory()->count(3)->create(['stock' => 5]);

        $response = $this->get(route('products.search', ['q' => "' OR '1'='1"]));

        $response->assertStatus(200);
        // The injection string won't match any product name/description literally
        $response->assertSee('No products found', false);
    }

    // ---------------------------------------------------------------
    // TC-06  SQL injection — filter category is cast to int
    // ---------------------------------------------------------------

    /** @test */
    public function nf002_sql_injection_in_category_filter_returns_200(): void
    {
        $response = $this->get(route('products.index', ['category' => "1 OR 1=1"]));

        $response->assertStatus(200);
    }

    // ---------------------------------------------------------------
    // TC-07  SQL injection — min_price filter is cast to float
    // ---------------------------------------------------------------

    /** @test */
    public function nf002_sql_injection_in_min_price_filter_returns_200(): void
    {
        $response = $this->get(route('products.index', ['min_price' => "0; DROP TABLE products--"]));

        $response->assertStatus(200);
    }

    // ---------------------------------------------------------------
    // TC-08  XSS — product name stored via factory is escaped on listing
    // ---------------------------------------------------------------

    /** @test */
    public function nf002_xss_in_product_name_is_escaped_on_listing_page(): void
    {
        Product::factory()->create([
            'name'  => '<script>alert("xss")</script>',
            'stock' => 5,
        ]);

        $response = $this->get(route('products.index'));

        $response->assertStatus(200);
        $response->assertDontSee('<script>alert("xss")</script>', false);
        $response->assertSee('&lt;script&gt;', false);
    }

    // ---------------------------------------------------------------
    // TC-09  XSS — search query echoed back is escaped
    // ---------------------------------------------------------------

    /** @test */
    public function nf002_xss_in_search_query_is_escaped_in_response(): void
    {
        $payload  = '<img src=x onerror=alert(1)>';
        $response = $this->get(route('products.search', ['q' => $payload]));

        $response->assertStatus(200);
        $response->assertDontSee($payload, false);
    }

    // ---------------------------------------------------------------
    // TC-10  Validation — register rejects oversized name (mass-assign safe)
    // ---------------------------------------------------------------

    /** @test */
    public function nf002_register_rejects_excessively_long_name(): void
    {
        $response = $this->post(route('register.store'), [
            'name'                  => str_repeat('A', 300),
            'email'                 => 'long@example.com',
            'password'              => 'Password1!',
            'password_confirmation' => 'Password1!',
        ]);

        $response->assertSessionHasErrors('name');
    }

    // ---------------------------------------------------------------
    // TC-11  Validation — cart add rejects non-numeric product_id
    // ---------------------------------------------------------------

    /** @test */
    public function nf002_cart_add_rejects_non_numeric_product_id(): void
    {
        $response = $this->post(route('cart.store'), [
            'product_id' => "'; DROP TABLE products--",
            'quantity'   => 1,
        ]);

        // Must not be 500 — validation fires before any DB query
        $this->assertNotEquals(500, $response->getStatusCode());
    }

    // ---------------------------------------------------------------
    // TC-12  Eloquent PDO — all DB queries use prepared statements
    //        (verify by confirming DB query log contains "?" bindings)
    // ---------------------------------------------------------------

    /** @test */
    public function nf002_product_search_uses_pdo_bindings_not_raw_interpolation(): void
    {
        DB::enableQueryLog();

        Product::factory()->create(['name' => 'Gadget', 'stock' => 5]);
        $this->get(route('products.search', ['q' => 'Gadget']));

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertNotEmpty($queries, 'No queries were logged.');

        // Every query that touches the search term must use '?' bindings, not raw interpolation
        $searchQueries = collect($queries)->filter(
            fn ($q) => str_contains(strtolower($q['query']), 'like')
        );

        $this->assertNotEmpty($searchQueries, 'Expected at least one LIKE query for the search.');

        foreach ($searchQueries as $q) {
            $this->assertNotEmpty($q['bindings'],
                "Query uses raw interpolation instead of PDO bindings: {$q['query']}");
        }
    }
}
