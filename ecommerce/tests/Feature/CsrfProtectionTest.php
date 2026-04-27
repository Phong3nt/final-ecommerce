<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * NF-001 — CSRF Protection Audit
 *
 * Laravel's VerifyCsrfToken bypasses the check during unit tests
 * (via runningUnitTests()), so these tests verify protection through
 * three complementary angles:
 *
 *  1. Middleware registration  — the middleware is wired into the web group.
 *  2. Route-level exclusion    — only the Stripe webhook is exempt.
 *  3. View-level presence      — every state-changing form renders a hidden
 *                                `_token` field via @csrf.
 */
class CsrfProtectionTest extends TestCase
{
    use RefreshDatabase;

    // ─── TC-01: middleware registered in web group ────────────────────────────

    /** @test */
    public function nf001_verify_csrf_token_middleware_is_in_web_group(): void
    {
        $webMiddleware = app(\Illuminate\Routing\Router::class)
            ->getMiddlewareGroups()['web'] ?? [];

        $this->assertContains(
            VerifyCsrfToken::class,
            $webMiddleware,
            'VerifyCsrfToken must be present in the web middleware group.'
        );
    }

    // ─── TC-02: $except array is empty (webhook uses route-level exclusion) ───

    /** @test */
    public function nf001_csrf_except_list_is_empty(): void
    {
        $middleware = new VerifyCsrfToken(app(), app('encrypter'));

        $reflection = new \ReflectionProperty($middleware, 'except');
        $reflection->setAccessible(true);

        $this->assertEmpty(
            $reflection->getValue($middleware),
            'VerifyCsrfToken::$except must be empty; exclusions should use withoutMiddleware() on routes.'
        );
    }

    // ─── TC-03: webhook route excludes VerifyCsrfToken ───────────────────────

    /** @test */
    public function nf001_webhook_route_excludes_csrf_middleware(): void
    {
        $route = Route::getRoutes()->getByName('webhook.stripe');

        $this->assertNotNull($route, 'webhook.stripe route must exist.');

        $excluded = $route->excludedMiddleware();

        $this->assertContains(
            VerifyCsrfToken::class,
            $excluded,
            'The Stripe webhook route must explicitly exclude VerifyCsrfToken.'
        );
    }

    // ─── TC-04: login form renders CSRF token ─────────────────────────────────

    /** @test */
    public function nf001_login_form_contains_csrf_field(): void
    {
        $this->get(route('login'))
            ->assertStatus(200)
            ->assertSee('name="_token"', false);
    }

    // ─── TC-05: register form renders CSRF token ──────────────────────────────

    /** @test */
    public function nf001_register_form_contains_csrf_field(): void
    {
        $this->get(route('register'))
            ->assertStatus(200)
            ->assertSee('name="_token"', false);
    }

    // ─── TC-06: forgot-password form renders CSRF token ───────────────────────

    /** @test */
    public function nf001_forgot_password_form_contains_csrf_field(): void
    {
        $this->get(route('password.request'))
            ->assertStatus(200)
            ->assertSee('name="_token"', false);
    }

    // ─── TC-07: reset-password form renders CSRF token ────────────────────────

    /** @test */
    public function nf001_reset_password_form_contains_csrf_field(): void
    {
        $this->get(route('password.reset', ['token' => 'fake-token']))
            ->assertStatus(200)
            ->assertSee('name="_token"', false);
    }

    // ─── TC-08: profile form renders CSRF token ───────────────────────────────

    /** @test */
    public function nf001_profile_form_contains_csrf_field(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('profile.show'))
            ->assertStatus(200)
            ->assertSee('name="_token"', false);
    }

    // ─── TC-09: checkout address form renders CSRF token ─────────────────────

    /** @test */
    public function nf001_checkout_address_form_contains_csrf_field(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['cart' => [1 => ['name' => 'Product', 'price' => 10.00, 'quantity' => 1]]])
            ->get(route('checkout.address'))
            ->assertStatus(200)
            ->assertSee('name="_token"', false);
    }

    // ─── TC-10: checkout shipping form renders CSRF token ────────────────────

    /** @test */
    public function nf001_checkout_shipping_form_contains_csrf_field(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['checkout.address' => ['name' => 'Test', 'city' => 'NY']])
            ->get(route('checkout.shipping'))
            ->assertStatus(200)
            ->assertSee('name="_token"', false);
    }

    // ─── TC-11: cart update/remove forms render CSRF tokens ──────────────────

    /** @test */
    public function nf001_cart_forms_contain_csrf_field(): void
    {
        $product = Product::factory()->create(['stock' => 10]);

        // Add to cart via the normal store route so session structure is correct
        $this->post(route('cart.store'), ['product_id' => $product->id, 'quantity' => 1]);

        $this->get(route('cart.index'))
            ->assertStatus(200)
            ->assertSee('name="_token"', false);
    }

    // ─── TC-12: add-to-cart form on product detail page renders CSRF token ───

    /** @test */
    public function nf001_add_to_cart_form_contains_csrf_field(): void
    {
        $product = Product::factory()->create(['stock' => 5]);

        $this->get(route('products.show', $product->slug))
            ->assertStatus(200)
            ->assertSee('name="_token"', false);
    }
}
