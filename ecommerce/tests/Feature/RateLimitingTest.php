<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('login');
        RateLimiter::clear('register');
    }

    protected function tearDown(): void
    {
        RateLimiter::clear('login');
        RateLimiter::clear('register');
        parent::tearDown();
    }

    // ---------------------------------------------------------------
    // TC-01  Route audit — login POST has throttle middleware
    // ---------------------------------------------------------------

    /** @test */
    public function nf006_login_post_route_has_throttle_middleware(): void
    {
        $route = Route::getRoutes()->getByName('login.store');

        $this->assertNotNull($route, 'Route login.store not found.');

        $middleware = $route->gatherMiddleware();

        $hasThrottle = collect($middleware)->contains(
            fn ($m) => str_starts_with($m, 'throttle:')
        );

        $this->assertTrue($hasThrottle, 'login.store route is missing throttle middleware.');
    }

    // ---------------------------------------------------------------
    // TC-02  Route audit — register POST has throttle middleware
    // ---------------------------------------------------------------

    /** @test */
    public function nf006_register_post_route_has_throttle_middleware(): void
    {
        $route = Route::getRoutes()->getByName('register.store');

        $this->assertNotNull($route, 'Route register.store not found.');

        $middleware = $route->gatherMiddleware();

        $hasThrottle = collect($middleware)->contains(
            fn ($m) => str_starts_with($m, 'throttle:')
        );

        $this->assertTrue($hasThrottle, 'register.store route is missing throttle middleware.');
    }

    // ---------------------------------------------------------------
    // TC-03  Route audit — forgot-password POST has throttle middleware
    // ---------------------------------------------------------------

    /** @test */
    public function nf006_forgot_password_post_route_has_throttle_middleware(): void
    {
        $route = Route::getRoutes()->getByName('password.email');

        $this->assertNotNull($route, 'Route password.email not found.');

        $middleware = $route->gatherMiddleware();

        $hasThrottle = collect($middleware)->contains(
            fn ($m) => str_starts_with($m, 'throttle:')
        );

        $this->assertTrue($hasThrottle, 'password.email route is missing throttle middleware.');
    }

    // ---------------------------------------------------------------
    // TC-04  Kernel — `throttle` alias is registered
    // ---------------------------------------------------------------

    /** @test */
    public function nf006_throttle_middleware_alias_is_registered_in_kernel(): void
    {
        $aliases = app('router')->getMiddleware();

        $this->assertArrayHasKey('throttle', $aliases);
    }

    // ---------------------------------------------------------------
    // TC-05  HTTP — login still works normally within the limit
    // ---------------------------------------------------------------

    /** @test */
    public function nf006_login_post_returns_non_429_within_rate_limit(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password')]);

        $response = $this->post(route('login.store'), [
            'email'    => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertNotEquals(429, $response->getStatusCode());
    }

    // ---------------------------------------------------------------
    // TC-06  HTTP — register still works normally within the limit
    // ---------------------------------------------------------------

    /** @test */
    public function nf006_register_post_returns_non_429_within_rate_limit(): void
    {
        $response = $this->post(route('register.store'), [
            'name'                  => 'Test User',
            'email'                 => 'ratelimit@example.com',
            'password'              => 'Password1!',
            'password_confirmation' => 'Password1!',
        ]);

        $this->assertNotEquals(429, $response->getStatusCode());
    }

    // ---------------------------------------------------------------
    // TC-07  HTTP — forgot-password still works normally within limit
    // ---------------------------------------------------------------

    /** @test */
    public function nf006_forgot_password_post_returns_non_429_within_rate_limit(): void
    {
        $response = $this->post(route('password.email'), [
            'email' => 'nobody@example.com',
        ]);

        $this->assertNotEquals(429, $response->getStatusCode());
    }

    // ---------------------------------------------------------------
    // TC-08  Rate limit config — login throttle limit is <= 10 per min
    // ---------------------------------------------------------------

    /** @test */
    public function nf006_login_throttle_limit_is_at_most_10_per_minute(): void
    {
        $route      = Route::getRoutes()->getByName('login.store');
        $middleware = $route->gatherMiddleware();

        $throttleEntry = collect($middleware)->first(
            fn ($m) => str_starts_with($m, 'throttle:')
        );

        // Parse the limit from "throttle:10,1" or "throttle:10"
        $parts = explode(':', $throttleEntry);
        $limit = (int) explode(',', $parts[1])[0];

        $this->assertLessThanOrEqual(10, $limit,
            "Login throttle limit ($limit) should be ≤ 10 per minute.");
    }

    // ---------------------------------------------------------------
    // TC-09  Rate limit config — register throttle limit is <= 10/min
    // ---------------------------------------------------------------

    /** @test */
    public function nf006_register_throttle_limit_is_at_most_10_per_minute(): void
    {
        $route      = Route::getRoutes()->getByName('register.store');
        $middleware = $route->gatherMiddleware();

        $throttleEntry = collect($middleware)->first(
            fn ($m) => str_starts_with($m, 'throttle:')
        );

        $parts = explode(':', $throttleEntry);
        $limit = (int) explode(',', $parts[1])[0];

        $this->assertLessThanOrEqual(10, $limit,
            "Register throttle limit ($limit) should be ≤ 10 per minute.");
    }

    // ---------------------------------------------------------------
    // TC-10  Route audit — GET login has NO throttle (GET is safe)
    // ---------------------------------------------------------------

    /** @test */
    public function nf006_login_get_route_does_not_have_throttle_middleware(): void
    {
        $route = Route::getRoutes()->getByName('login');

        $this->assertNotNull($route, 'Route login not found.');

        $middleware = $route->gatherMiddleware();

        $hasThrottle = collect($middleware)->contains(
            fn ($m) => str_starts_with($m, 'throttle:')
        );

        $this->assertFalse($hasThrottle,
            'GET login route should not carry throttle middleware (only the POST should).');
    }

    // ---------------------------------------------------------------
    // TC-11  Route audit — GET register has NO throttle (GET is safe)
    // ---------------------------------------------------------------

    /** @test */
    public function nf006_register_get_route_does_not_have_throttle_middleware(): void
    {
        $route = Route::getRoutes()->getByName('register');

        $this->assertNotNull($route, 'Route register not found.');

        $middleware = $route->gatherMiddleware();

        $hasThrottle = collect($middleware)->contains(
            fn ($m) => str_starts_with($m, 'throttle:')
        );

        $this->assertFalse($hasThrottle,
            'GET register route should not carry throttle middleware (only the POST should).');
    }

    // ---------------------------------------------------------------
    // TC-12  Performance — throttle middleware overhead is negligible
    // ---------------------------------------------------------------

    /** @test */
    public function nf006_login_post_with_throttle_responds_within_two_seconds(): void
    {
        $user  = User::factory()->create(['password' => bcrypt('password')]);
        $start = microtime(true);

        $this->post(route('login.store'), [
            'email'    => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertLessThan(2.0, microtime(true) - $start);
    }
}
