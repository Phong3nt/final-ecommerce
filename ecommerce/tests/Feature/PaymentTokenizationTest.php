<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\PaymentServiceInterface;
use App\Services\StripePaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * NF-003 — Payment data never stored server-side; tokenization via gateway SDK.
 *
 * Strategy:
 *   1. Schema audit — orders table has no PAN / CVV / expiry columns.
 *   2. Model audit — Order::$fillable contains no card data fields.
 *   3. Controller source audit — CheckoutController never reads card fields from request.
 *   4. Service audit — StripePaymentService wraps the official Stripe SDK; no raw card HTTP.
 *   5. Interface audit — PaymentServiceInterface exposes no methods that accept card data.
 *   6. View audit — review blade uses Stripe Elements (client-side tokenization);
 *                   no plain <input> fields for card data; no server round-trip of card numbers.
 *   7. Runtime audit — POST /checkout/place-order with injected card_number stores nothing.
 */
class PaymentTokenizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    }

    private function makeUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('user');
        return $user;
    }

    // ---------------------------------------------------------------
    // TC-01  orders table has no raw card-data columns
    // ---------------------------------------------------------------

    /** @test */
    public function nf003_orders_table_has_no_card_pan_cvv_expiry_columns(): void
    {
        $forbidden = ['card_number', 'card_pan', 'pan', 'cvv', 'cvc', 'expiry', 'expiration', 'card_expiry'];
        $columns = Schema::getColumnListing('orders');

        foreach ($forbidden as $col) {
            $this->assertNotContains(
                $col,
                $columns,
                "orders table must not contain column '{$col}' (raw card data)"
            );
        }
    }

    // ---------------------------------------------------------------
    // TC-02  Order model $fillable contains no card data fields
    // ---------------------------------------------------------------

    /** @test */
    public function nf003_order_fillable_contains_no_card_data_fields(): void
    {
        $forbidden = ['card_number', 'card_pan', 'pan', 'cvv', 'cvc', 'expiry', 'expiration', 'card_expiry'];
        $fillable = (new Order())->getFillable();

        foreach ($forbidden as $field) {
            $this->assertNotContains(
                $field,
                $fillable,
                "Order::\$fillable must not include '{$field}' (raw card data)"
            );
        }
    }

    // ---------------------------------------------------------------
    // TC-03  PaymentServiceInterface exposes no card-data parameters
    // ---------------------------------------------------------------

    /** @test */
    public function nf003_payment_service_interface_has_no_card_data_parameters(): void
    {
        $rc = new ReflectionClass(PaymentServiceInterface::class);
        $methods = $rc->getMethods();

        $forbidden = ['card_number', 'card_pan', 'cvv', 'cvc', 'expiry', 'expiration'];

        foreach ($methods as $method) {
            foreach ($method->getParameters() as $param) {
                $this->assertNotContains(
                    $param->getName(),
                    $forbidden,
                    "PaymentServiceInterface::{$method->getName()}() must not have a '{$param->getName()}' parameter"
                );
            }
        }
    }

    // ---------------------------------------------------------------
    // TC-04  StripePaymentService wraps official Stripe SDK (StripeClient)
    // ---------------------------------------------------------------

    /** @test */
    public function nf003_stripe_payment_service_uses_official_stripe_client(): void
    {
        $rc = new ReflectionClass(StripePaymentService::class);
        $properties = $rc->getProperties();

        $usesStripeClient = false;
        foreach ($properties as $prop) {
            // Check type declaration of each property
            $type = $prop->getType();
            if ($type && str_contains($type->getName(), 'StripeClient')) {
                $usesStripeClient = true;
                break;
            }
        }

        $this->assertTrue(
            $usesStripeClient,
            'StripePaymentService must use \Stripe\StripeClient (official SDK), not raw HTTP'
        );
    }

    // ---------------------------------------------------------------
    // TC-05  StripePaymentService source contains no raw curl / card interpolation
    // ---------------------------------------------------------------

    /** @test */
    public function nf003_stripe_payment_service_source_has_no_raw_card_http(): void
    {
        $rc = new ReflectionClass(StripePaymentService::class);
        $source = file_get_contents($rc->getFileName());

        $forbidden = ['curl_init', 'file_get_contents(\'https://api.stripe', 'card_number', 'card_pan', 'cvv'];

        foreach ($forbidden as $pattern) {
            $this->assertStringNotContainsString(
                $pattern,
                $source,
                "StripePaymentService must not contain '{$pattern}'"
            );
        }
    }

    // ---------------------------------------------------------------
    // TC-06  CheckoutController source never reads raw card fields from request
    // ---------------------------------------------------------------

    /** @test */
    public function nf003_checkout_controller_source_never_reads_card_data_from_request(): void
    {
        $path = app_path('Http/Controllers/CheckoutController.php');
        $source = file_get_contents($path);

        $forbidden = ['card_number', 'card_pan', 'cvv', 'cvc', "->input('expir", "->get('expir", '->card'];

        foreach ($forbidden as $pattern) {
            $this->assertStringNotContainsString(
                $pattern,
                $source,
                "CheckoutController must not read '{$pattern}' from request (card data must stay in Stripe)"
            );
        }
    }

    // ---------------------------------------------------------------
    // TC-07  review.blade.php loads Stripe.js (gateway SDK on frontend)
    // ---------------------------------------------------------------

    /** @test */
    public function nf003_review_blade_loads_stripe_js_sdk(): void
    {
        $blade = file_get_contents(resource_path('views/checkout/review.blade.php'));

        $this->assertStringContainsString(
            'https://js.stripe.com/v3/',
            $blade,
            'review.blade.php must load the official Stripe.js v3 SDK'
        );
    }

    // ---------------------------------------------------------------
    // TC-08  review.blade.php mounts Stripe Elements (no plain card inputs)
    // ---------------------------------------------------------------

    /** @test */
    public function nf003_review_blade_uses_stripe_elements_not_plain_card_inputs(): void
    {
        $blade = file_get_contents(resource_path('views/checkout/review.blade.php'));

        // Must use Stripe Elements mount point
        $this->assertStringContainsString(
            "elements.create('payment')",
            $blade,
            'review.blade.php must mount a Stripe Payment Element for card tokenization'
        );

        // Must NOT have plain text-input for card_number
        $this->assertDoesNotMatchRegularExpression(
            '/\binput\b[^>]*name=["\']card_number["\']/',
            $blade,
            'review.blade.php must not have a plain <input name="card_number"> (card data must go via Stripe.js)'
        );
    }

    // ---------------------------------------------------------------
    // TC-09  review.blade.php calls stripe.confirmPayment (client-side tokenization)
    // ---------------------------------------------------------------

    /** @test */
    public function nf003_review_blade_calls_stripe_confirm_payment_client_side(): void
    {
        $blade = file_get_contents(resource_path('views/checkout/review.blade.php'));

        $this->assertStringContainsString(
            'stripe.confirmPayment',
            $blade,
            'review.blade.php must call stripe.confirmPayment() — card data sent directly to Stripe, not to server'
        );
    }

    // ---------------------------------------------------------------
    // TC-10  POST /checkout/place-order only creates PaymentIntent — stores no card data
    // ---------------------------------------------------------------

    /** @test */
    public function nf003_place_order_endpoint_stores_no_card_data_in_order_record(): void
    {
        $user = $this->makeUser();

        // Create a product and seed the cart session
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'price' => 50.00]);

        // Stub the PaymentService so no real Stripe call is made
        $this->instance(PaymentServiceInterface::class, new class implements PaymentServiceInterface {
            public function createPaymentIntent(int $amountCents, string $currency, array $metadata): array
            {
                return ['id' => 'pi_test_stub', 'client_secret' => 'pi_test_stub_secret_xxx'];
            }

            public function constructWebhookEvent(string $payload, string $sigHeader, string $secret): object
            {
                return (object) [];
            }

            public function cancelPaymentIntent(string $intentId): void
            {
            }

            public function refund(string $intentId, int $amountCents): string
            {
                return 're_stub';
            }
        });

        $this->actingAs($user)->withSession([
            'cart' => [
                $product->id => ['name' => $product->name, 'price' => $product->price, 'quantity' => 1],
            ],
            'checkout' => [
                'address' => [
                    'id' => 1,
                    'name' => 'Test User',
                    'address_line1' => '123 Test St',
                    'address_line2' => null,
                    'city' => 'Testville',
                    'state' => 'TS',
                    'postal_code' => '00000',
                    'country' => 'US',
                ],
                'shipping' => [
                    'method' => 'standard',
                    'label' => 'Standard Shipping',
                    'cost' => 5.00,
                ],
            ],
        ])->postJson('/checkout/review')
            ->assertStatus(200)
            ->assertJsonStructure(['client_secret', 'order_id']);

        // Verify the stored order has NO card data columns populated
        $order = Order::first();
        $this->assertNotNull($order);

        $columns = Schema::getColumnListing('orders');
        $cardColumns = array_filter($columns, fn($c) => in_array($c, [
            'card_number',
            'card_pan',
            'cvv',
            'cvc',
            'expiry',
            'expiration',
        ]));

        foreach ($cardColumns as $col) {
            $this->assertNull($order->$col, "orders.{$col} must never be populated");
        }

        // Only intent ID and client_secret (from Stripe) should be stored — not card data
        $this->assertEquals('pi_test_stub', $order->stripe_payment_intent_id);
        $this->assertStringNotContainsString('4242', $order->stripe_payment_intent_id ?? '');
    }

    // ---------------------------------------------------------------
    // TC-11  POST /checkout/place-order with injected card_number in body does not store it
    // ---------------------------------------------------------------

    /** @test */
    public function nf003_place_order_ignores_card_number_sent_in_request_body(): void
    {
        $user = $this->makeUser();

        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'price' => 30.00]);

        $this->instance(PaymentServiceInterface::class, new class implements PaymentServiceInterface {
            public function createPaymentIntent(int $amountCents, string $currency, array $metadata): array
            {
                return ['id' => 'pi_test_stub2', 'client_secret' => 'pi_test_stub2_secret_xxx'];
            }

            public function constructWebhookEvent(string $payload, string $sigHeader, string $secret): object
            {
                return (object) [];
            }

            public function cancelPaymentIntent(string $intentId): void
            {
            }

            public function refund(string $intentId, int $amountCents): string
            {
                return 're_stub';
            }
        });

        $this->actingAs($user)->withSession([
            'cart' => [
                $product->id => ['name' => $product->name, 'price' => $product->price, 'quantity' => 1],
            ],
            'checkout' => [
                'address' => [
                    'id' => 1,
                    'name' => 'Test User',
                    'address_line1' => '1 Attack Rd',
                    'address_line2' => null,
                    'city' => 'Hacktown',
                    'state' => 'HX',
                    'postal_code' => '99999',
                    'country' => 'US',
                ],
                'shipping' => [
                    'method' => 'express',
                    'label' => 'Express Shipping',
                    'cost' => 15.00,
                ],
            ],
        ])->postJson('/checkout/review', [
                    // Attacker tries to send card data in the JSON body
                    'card_number' => '4242424242424242',
                    'cvv' => '123',
                    'expiry' => '12/99',
                ])->assertStatus(200);

        // Verify nothing card-related was persisted in the order or the raw JSON columns
        $order = Order::first();
        $this->assertNotNull($order);

        // stripe_payment_intent_id should be our stub value, not a card number
        $this->assertStringNotContainsString('4242', $order->stripe_payment_intent_id ?? '');

        // The address JSON field must not have leaked card data into it either
        $addressJson = json_encode($order->address);
        $this->assertStringNotContainsString('4242', $addressJson);
    }

    // ---------------------------------------------------------------
    // TC-12  Order $fillable includes stripe intent ID but NOT raw card fields
    // ---------------------------------------------------------------

    /** @test */
    public function nf003_order_fillable_includes_stripe_intent_id_but_not_card_fields(): void
    {
        $fillable = (new Order())->getFillable();

        // Must store Stripe intent ID (for webhook correlation)
        $this->assertContains(
            'stripe_payment_intent_id',
            $fillable,
            'Order must store stripe_payment_intent_id to correlate webhook events'
        );

        // Must NOT store raw card numbers — these belong to Stripe's vault
        $this->assertNotContains('card_number', $fillable);
        $this->assertNotContains('card_pan', $fillable);
        $this->assertNotContains('cvv', $fillable);
        $this->assertNotContains('cvc', $fillable);
    }
}
