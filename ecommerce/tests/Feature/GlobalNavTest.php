<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * IMP-031 — Global navigation: persistent top navbar + mobile hamburger menu.
 *
 * Scope: [FULL_STACK_MODE]
 * Verifies that partials/navbar.blade.php is present, included in layouts/app,
 * and renders the correct links and UI elements for both guest and authenticated users.
 */
class GlobalNavTest extends TestCase
{
    use RefreshDatabase;

    // ── Helper: read a Blade view file as a raw string ──────────────────────

    private function viewSource(string $relativePath): string
    {
        $fullPath = resource_path('views/' . $relativePath);
        $this->assertFileExists($fullPath, "Expected view to exist: {$relativePath}");

        return file_get_contents($fullPath);
    }

    // ── TC01: Navbar partial file exists with required structural markup ─────

    public function test_imp031_tc01_navbar_partial_exists_with_required_markup(): void
    {
        $nav = $this->viewSource('partials/navbar.blade.php');

        $this->assertStringContainsString('navbar-expand-lg', $nav);
        $this->assertStringContainsString('sticky-top', $nav);
        $this->assertStringContainsString('navbar-toggler', $nav);
        $this->assertStringContainsString('bi-cart3', $nav);
        $this->assertStringContainsString('bi-shop', $nav);
    }

    // ── TC02: layouts/app.blade.php includes the navbar partial ─────────────

    public function test_imp031_tc02_layouts_app_includes_navbar_partial(): void
    {
        $layout = $this->viewSource('layouts/app.blade.php');

        $this->assertStringContainsString("@include('partials.navbar')", $layout);
    }

    // ── TC03: Guest user sees Login link ─────────────────────────────────────

    public function test_imp031_tc03_guest_sees_login_link(): void
    {
        $response = $this->get(route('products.index'));

        $response->assertOk();
        $response->assertSee('Login');
    }

    // ── TC04: Guest user sees Register link ──────────────────────────────────

    public function test_imp031_tc04_guest_sees_register_link(): void
    {
        $response = $this->get(route('products.index'));

        $response->assertOk();
        $response->assertSee('Register');
    }

    // ── TC05: Authenticated user's name appears in navbar ───────────────────

    public function test_imp031_tc05_auth_user_name_shown_in_navbar(): void
    {
        $user = User::factory()->create(['name' => 'Alice Smith']);

        $response = $this->actingAs($user)->get(route('products.index'));

        $response->assertOk();
        $response->assertSee('Alice Smith');
    }

    // ── TC06: Authenticated user does NOT see Login link ────────────────────

    public function test_imp031_tc06_auth_user_does_not_see_login_link(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('products.index'));

        $response->assertOk();
        // Login link should not be visible (Register CTA also gone when auth)
        $response->assertDontSee('href="' . route('login') . '"', false);
    }

    // ── TC07: Navbar contains cart icon link ─────────────────────────────────

    public function test_imp031_tc07_navbar_has_cart_link(): void
    {
        $response = $this->get(route('products.index'));

        $response->assertOk();
        $response->assertSee(route('cart.index'), false);
    }

    // ── TC08: Cart badge shows count when cart_count is in session ───────────

    public function test_imp031_tc08_cart_badge_shows_when_cart_count_in_session(): void
    {
        $response = $this->withSession(['cart_count' => 3])
                         ->get(route('products.index'));

        $response->assertOk();
        $response->assertSee('3');
        $response->assertSee('rounded-pill', false);
    }

    // ── TC09: Mobile hamburger toggle button is present ──────────────────────

    public function test_imp031_tc09_mobile_hamburger_toggle_present(): void
    {
        $response = $this->get(route('products.index'));

        $response->assertOk();
        $response->assertSee('navbar-toggler', false);
        $response->assertSee('data-bs-toggle="collapse"', false);
    }

    // ── TC10: Navbar has sticky-top class for persistent display ────────────

    public function test_imp031_tc10_navbar_has_sticky_top_class(): void
    {
        $response = $this->get(route('products.index'));

        $response->assertOk();
        $response->assertSee('sticky-top', false);
    }

    // ── TC11: Authenticated user dropdown has all required links ─────────────

    public function test_imp031_tc11_auth_user_dropdown_has_all_links(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('products.index'));

        $response->assertOk();
        $response->assertSee(route('dashboard'), false);
        $response->assertSee(route('profile.show'), false);
        $response->assertSee(route('orders.index'), false);
        $response->assertSee(route('addresses.index'), false);
        $response->assertSee(route('logout'), false);
    }

    // ── TC12: Navbar renders within time threshold ────────────────────────────

    public function test_imp031_tc12_navbar_renders_within_one_second(): void
    {
        $start = microtime(true);
        $response = $this->get(route('products.index'));
        $elapsed = microtime(true) - $start;

        $response->assertOk();
        $this->assertLessThan(1.0, $elapsed, 'Navbar render took longer than 1 second');
    }
}
