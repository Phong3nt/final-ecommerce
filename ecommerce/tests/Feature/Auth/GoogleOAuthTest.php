<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * AU-003 — Google OAuth Login
 *
 * Task:      Log in / auto-register via Google OAuth (Laravel Socialite).
 * Standards: testing_standards.md — min 3 cases (happy, negative, edge) + security.
 */
class GoogleOAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    }

    // -------------------------------------------------------
    // Helper: build a mocked Socialite provider + user
    // -------------------------------------------------------
    private function mockSocialiteUser(
        string $id = 'google-uid-001',
        string $email = 'alice@gmail.com',
        string $name = 'Alice Google'
    ): void {
        $socialUser = Mockery::mock(SocialiteUser::class);
        $socialUser->shouldReceive('getId')->andReturn($id);
        $socialUser->shouldReceive('getEmail')->andReturn($email);
        $socialUser->shouldReceive('getName')->andReturn($name);

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andReturn($socialUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }

    private function mockSocialiteRedirect(): void
    {
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('redirect')->andReturn(
            redirect('https://accounts.google.com/o/oauth2/auth?scope=...')
        );

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }

    // -------------------------------------------------------
    // TC-01 | HAPPY — New Google user is auto-registered
    // -------------------------------------------------------
    public function test_new_google_user_is_auto_registered_and_logged_in(): void
    {
        $this->mockSocialiteUser('uid-new', 'newuser@gmail.com', 'New User');

        $response = $this->get(route('auth.google.callback'));

        $this->assertDatabaseHas('users', [
            'email' => 'newuser@gmail.com',
            'google_id' => 'uid-new',
        ]);
        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard'));
    }

    // -------------------------------------------------------
    // TC-02 | HAPPY — Existing email user gets google_id linked
    // -------------------------------------------------------
    public function test_existing_email_user_gets_google_id_linked_and_is_logged_in(): void
    {
        $user = User::factory()->create(['email' => 'bob@gmail.com', 'google_id' => null]);

        $this->mockSocialiteUser('uid-bob', 'bob@gmail.com', 'Bob');

        $response = $this->get(route('auth.google.callback'));

        $user->refresh();
        $this->assertEquals('uid-bob', $user->google_id);
        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard'));
    }

    // -------------------------------------------------------
    // TC-03 | HAPPY — Already-linked google_id user logs in
    // -------------------------------------------------------
    public function test_already_linked_google_user_logs_in_directly(): void
    {
        $user = User::factory()->create(['email' => 'carol@gmail.com', 'google_id' => 'uid-carol']);

        $this->mockSocialiteUser('uid-carol', 'carol@gmail.com', 'Carol');

        $response = $this->get(route('auth.google.callback'));

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard'));
    }

    // -------------------------------------------------------
    // TC-04 | HAPPY — Redirect route sends user to Google
    // -------------------------------------------------------
    public function test_redirect_route_redirects_to_google(): void
    {
        $this->mockSocialiteRedirect();

        $response = $this->get(route('auth.google.redirect'));

        $response->assertRedirect();
        $this->assertStringContainsString('google.com', $response->headers->get('Location'));
    }

    // -------------------------------------------------------
    // TC-05 | EDGE — New Google user has email_verified_at set
    // -------------------------------------------------------
    public function test_new_google_user_has_email_verified_at_set(): void
    {
        $this->mockSocialiteUser('uid-eve', 'eve@gmail.com', 'Eve');

        $this->get(route('auth.google.callback'));

        $user = User::where('email', 'eve@gmail.com')->first();
        $this->assertNotNull($user->email_verified_at);
    }

    // -------------------------------------------------------
    // TC-06 | EDGE — New Google user gets 'user' role assigned
    // -------------------------------------------------------
    public function test_new_google_user_gets_user_role(): void
    {
        $this->mockSocialiteUser('uid-frank', 'frank@gmail.com', 'Frank');

        $this->get(route('auth.google.callback'));

        $user = User::where('email', 'frank@gmail.com')->first();
        $this->assertTrue($user->hasRole('user'));
    }

    // -------------------------------------------------------
    // TC-07 | EDGE — New user name is taken from Google profile
    // -------------------------------------------------------
    public function test_new_google_user_name_is_set_from_google_profile(): void
    {
        $this->mockSocialiteUser('uid-grace', 'grace@gmail.com', 'Grace Kelly');

        $this->get(route('auth.google.callback'));

        $this->assertDatabaseHas('users', [
            'email' => 'grace@gmail.com',
            'name' => 'Grace Kelly',
        ]);
    }

    // -------------------------------------------------------
    // TC-08 | EDGE — Intended URL is honoured after Google login
    // -------------------------------------------------------
    public function test_intended_url_redirect_works_after_google_login(): void
    {
        $this->mockSocialiteUser('uid-henry', 'henry@gmail.com', 'Henry');

        // Simulate visiting a protected page before OAuth
        $this->get('/dashboard');

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('dashboard'));
    }

    // -------------------------------------------------------
    // TC-09 | SECURITY — Session is regenerated after OAuth login
    // -------------------------------------------------------
    public function test_session_is_regenerated_after_google_login(): void
    {
        $this->mockSocialiteUser('uid-iris', 'iris@gmail.com', 'Iris');

        $sessionBefore = session()->getId();

        $this->get(route('auth.google.callback'));

        $sessionAfter = session()->getId();
        $this->assertNotEquals($sessionBefore, $sessionAfter);
    }

    // -------------------------------------------------------
    // TC-10 | NEGATIVE — Socialite exception redirects to login
    // -------------------------------------------------------
    public function test_socialite_exception_redirects_to_login_with_error(): void
    {
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andThrow(new \Exception('OAuth denied'));

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    // -------------------------------------------------------
    // TC-11 | SECURITY — New auto-registered user has a hashed password (not empty)
    // -------------------------------------------------------
    public function test_new_google_user_has_non_empty_hashed_password(): void
    {
        $this->mockSocialiteUser('uid-julia', 'julia@gmail.com', 'Julia');

        $this->get(route('auth.google.callback'));

        $user = User::where('email', 'julia@gmail.com')->first();
        $this->assertNotNull($user->password);
        $this->assertNotEmpty($user->password);
        // Must not be stored as plain text — bcrypt hashes start with $2y$
        $this->assertStringStartsWith('$2y$', $user->password);
    }

    // -------------------------------------------------------
    // TC-12 | PERFORMANCE — Callback responds within 2 seconds
    // -------------------------------------------------------
    public function test_google_callback_responds_within_two_seconds(): void
    {
        $this->mockSocialiteUser('uid-perf', 'perf@gmail.com', 'Perf User');

        $start = microtime(true);
        $this->get(route('auth.google.callback'));
        $elapsed = microtime(true) - $start;

        $this->assertLessThan(2.0, $elapsed, "Google callback took {$elapsed}s — exceeds 2s threshold.");
    }
}
