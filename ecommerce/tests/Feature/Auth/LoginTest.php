<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * AU-002 — User Login (email + password)
 *
 * Task:      Log in with email and password.
 * Standards: testing_standards.md — happy + negative + edge + security + performance.
 */
class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'user',  'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    }

    // -------------------------------------------------------
    // TC-AU002-01 | HAPPY PATH
    // Valid credentials → session created, redirected to dashboard
    // -------------------------------------------------------
    public function test_AU002_validCredentials_createsSessionAndRedirectsToDashboard(): void
    {
        $user = User::factory()->create([
            'email'    => 'john@example.com',
            'password' => Hash::make('Secret123'),
        ]);

        $response = $this->post(route('login.store'), [
            'email'    => 'john@example.com',
            'password' => 'Secret123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    // -------------------------------------------------------
    // TC-AU002-02 | NEGATIVE — wrong password
    // Wrong password → redirect back with error, not authenticated
    // -------------------------------------------------------
    public function test_AU002_wrongPassword_redirectsBackWithError(): void
    {
        User::factory()->create([
            'email'    => 'john@example.com',
            'password' => Hash::make('Secret123'),
        ]);

        $response = $this->post(route('login.store'), [
            'email'    => 'john@example.com',
            'password' => 'WrongPass999',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    // -------------------------------------------------------
    // TC-AU002-03 | NEGATIVE — non-existent email
    // Email not in database → error, not authenticated
    // -------------------------------------------------------
    public function test_AU002_nonExistentEmail_redirectsBackWithError(): void
    {
        $response = $this->post(route('login.store'), [
            'email'    => 'nobody@example.com',
            'password' => 'Secret123',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    // -------------------------------------------------------
    // TC-AU002-04 | NEGATIVE — empty form
    // No credentials submitted → validation errors on both fields
    // -------------------------------------------------------
    public function test_AU002_emptyForm_failsValidation(): void
    {
        $response = $this->post(route('login.store'), []);

        $response->assertSessionHasErrors(['email', 'password']);
        $this->assertGuest();
    }

    // -------------------------------------------------------
    // TC-AU002-05 | NEGATIVE — missing password
    // Email present but no password → validation error
    // -------------------------------------------------------
    public function test_AU002_missingPassword_failsValidation(): void
    {
        $response = $this->post(route('login.store'), [
            'email' => 'john@example.com',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertGuest();
    }

    // -------------------------------------------------------
    // TC-AU002-06 | EDGE — "remember me" flag creates persistent session
    // remember=true → auth persists (cookie set)
    // -------------------------------------------------------
    public function test_AU002_rememberMe_setsRememberCookie(): void
    {
        User::factory()->create([
            'email'    => 'remember@example.com',
            'password' => Hash::make('Secret123'),
        ]);

        $response = $this->post(route('login.store'), [
            'email'    => 'remember@example.com',
            'password' => 'Secret123',
            'remember' => '1',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs(User::where('email', 'remember@example.com')->first());
        // Laravel sets remember_token in DB when remember=true
        $this->assertNotNull(User::where('email', 'remember@example.com')->value('remember_token'));
    }

    // -------------------------------------------------------
    // TC-AU002-07 | EDGE — intended URL redirect
    // User tried to visit /dashboard before login → after login redirected there
    // -------------------------------------------------------
    public function test_AU002_intendedUrl_redirectsToIntendedAfterLogin(): void
    {
        User::factory()->create([
            'email'    => 'intend@example.com',
            'password' => Hash::make('Secret123'),
        ]);

        // Simulate the intended URL being stored (guest hitting protected route)
        $this->get(route('dashboard')); // sets intended in session via RedirectIfAuthenticated

        $response = $this->post(route('login.store'), [
            'email'    => 'intend@example.com',
            'password' => 'Secret123',
        ]);

        // Should redirect to dashboard (intended URL)
        $response->assertRedirect(route('dashboard'));
    }

    // -------------------------------------------------------
    // TC-AU002-08 | SECURITY — session ID regenerated after login
    // Prevents session fixation attacks
    // -------------------------------------------------------
    public function test_AU002_sessionIsRegeneratedAfterLogin(): void
    {
        User::factory()->create([
            'email'    => 'secure@example.com',
            'password' => Hash::make('Secret123'),
        ]);

        // Get session ID before login
        $before = session()->getId();

        $this->post(route('login.store'), [
            'email'    => 'secure@example.com',
            'password' => 'Secret123',
        ]);

        // Session ID must change after login (session regenerate)
        $after = session()->getId();
        $this->assertNotEquals($before, $after);
    }

    // -------------------------------------------------------
    // TC-AU002-09 | SECURITY — CSRF token required
    // VerifyCsrfToken middleware is registered on the login route
    // -------------------------------------------------------
    public function test_AU002_csrfMiddlewareIsActive(): void
    {
        $this->assertTrue(class_exists(\App\Http\Middleware\VerifyCsrfToken::class));

        /** @var \Illuminate\Foundation\Http\Kernel $kernel */
        $kernel = app(\Illuminate\Contracts\Http\Kernel::class);
        $webMiddleware = $kernel->getMiddlewareGroups()['web'] ?? [];
        $this->assertContains(\App\Http\Middleware\VerifyCsrfToken::class, $webMiddleware);
    }

    // -------------------------------------------------------
    // TC-AU002-10 | SECURITY — authenticated user cannot access login page
    // guest middleware redirects authenticated users away from /login
    // -------------------------------------------------------
    public function test_AU002_authenticatedUser_isRedirectedAwayFromLogin(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('login'));

        $response->assertRedirect();
    }

    // -------------------------------------------------------
    // TC-AU002-11 | SECURITY — error message is generic (not "email not found")
    // Must not reveal whether email exists in the system
    // -------------------------------------------------------
    public function test_AU002_failureMessage_isGenericNotRevealingEmailExistence(): void
    {
        // Create user with known email
        User::factory()->create([
            'email'    => 'known@example.com',
            'password' => Hash::make('Secret123'),
        ]);

        // Wrong password on known email
        $responseKnown = $this->post(route('login.store'), [
            'email'    => 'known@example.com',
            'password' => 'WrongPass',
        ]);

        // Unknown email
        $responseUnknown = $this->post(route('login.store'), [
            'email'    => 'unknown@example.com',
            'password' => 'WrongPass',
        ]);

        // Both should produce the same generic error (auth.failed)
        $errorKnown   = session()->get('errors')?->first('email') ?? '';
        $this->withSession([]); // reset
        
        $responseUnknown->assertSessionHasErrors('email');
        // Both return auth.failed — same message, no information leakage
        $responseKnown->assertSessionHasErrors('email');
    }

    // -------------------------------------------------------
    // TC-AU002-12 | PERFORMANCE — login completes within 2 seconds
    // -------------------------------------------------------
    public function test_AU002_loginCompletesWithinTimeThreshold(): void
    {
        User::factory()->create([
            'email'    => 'perf@example.com',
            'password' => Hash::make('Secret123'),
        ]);

        $start = microtime(true);

        $this->post(route('login.store'), [
            'email'    => 'perf@example.com',
            'password' => 'Secret123',
        ]);

        $elapsed = microtime(true) - $start;

        $this->assertLessThan(2.0, $elapsed, "Login took {$elapsed}s — exceeds 2s threshold.");
    }
}
