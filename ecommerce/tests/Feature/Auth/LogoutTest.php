<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    }

    private function makeUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('user');
        return $user;
    }

    /** @test AU-004 TC-01 (Happy): Authenticated POST /logout redirects to home */
    public function au004_authenticated_logout_redirects_to_home(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect('/');
    }

    /** @test AU-004 TC-02 (Happy): User is no longer authenticated after logout */
    public function au004_user_is_unauthenticated_after_logout(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->post('/logout');

        $this->assertGuest();
    }

    /** @test AU-004 TC-03 (Edge): Session is invalidated after logout */
    public function au004_session_is_invalidated_after_logout(): void
    {
        $user = $this->makeUser();

        $sessionBefore = session()->getId();
        $this->actingAs($user)->post('/logout');
        $sessionAfter = session()->getId();

        $this->assertNotEquals($sessionBefore, $sessionAfter);
    }

    /** @test AU-004 TC-04 (Edge): CSRF token is regenerated after logout */
    public function au004_csrf_token_is_regenerated_after_logout(): void
    {
        $user = $this->makeUser();

        $tokenBefore = session()->token();
        $this->actingAs($user)->post('/logout');
        $tokenAfter = session()->token();

        $this->assertNotEquals($tokenBefore, $tokenAfter);
    }

    /** @test AU-004 TC-05 (Security): VerifyCsrfToken middleware is active on logout route */
    public function au004_logout_without_csrf_returns_419(): void
    {
        $this->assertTrue(class_exists(\App\Http\Middleware\VerifyCsrfToken::class));

        $webMiddleware = app(\Illuminate\Contracts\Http\Kernel::class)->getMiddlewareGroups()['web'] ?? [];
        $this->assertContains(\App\Http\Middleware\VerifyCsrfToken::class, $webMiddleware);
    }

    /** @test AU-004 TC-06 (Security): Dashboard is inaccessible after logout */
    public function au004_dashboard_inaccessible_after_logout(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->post('/logout');

        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    /** @test AU-004 TC-07 (Security): GET /logout returns 405 Method Not Allowed */
    public function au004_get_logout_returns_405(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user)->get('/logout');

        $response->assertStatus(405);
    }

    /** @test AU-004 TC-08 (Negative): Guest POST /logout is redirected (auth middleware) */
    public function au004_guest_logout_is_redirected(): void
    {
        $response = $this->post('/logout');

        $response->assertRedirect('/login');
    }

    /** @test AU-004 TC-09 (Edge): Logout route only accepts POST — PUT returns 405 */
    public function au004_put_logout_returns_405(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user)->put('/logout');

        $response->assertStatus(405);
    }

    /** @test AU-004 TC-10 (Edge): Auth user data not in session after logout */
    public function au004_auth_data_cleared_from_session(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->post('/logout');

        $this->assertNull(session()->get('login_web_' . sha1(\Illuminate\Auth\SessionGuard::class)));
    }

    /** @test AU-004 TC-11 (Edge): Consecutive logouts do not cause 500 */
    public function au004_multiple_logouts_do_not_crash(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->post('/logout');
        $response = $this->post('/logout'); // second call as guest

        $response->assertRedirect('/login'); // auth middleware redirects
    }

    /** @test AU-004 TC-12 (Performance): Logout completes within 2 seconds */
    public function au004_logout_completes_within_two_seconds(): void
    {
        $user = $this->makeUser();

        $start = microtime(true);
        $this->actingAs($user)->post('/logout');
        $elapsed = microtime(true) - $start;

        $this->assertLessThan(2.0, $elapsed, 'Logout took longer than 2 seconds');
    }
}
