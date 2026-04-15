<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    }

    private function makeUser(array $attrs = []): User
    {
        $user = User::factory()->create($attrs);
        $user->assignRole('user');
        return $user;
    }

    // -------------------------------------------------------
    // TC-AU005-01 | HAPPY — forgot-password form is accessible
    // -------------------------------------------------------
    public function test_AU005_forgotPasswordPage_returns200(): void
    {
        $response = $this->get('/forgot-password');
        $response->assertStatus(200);
    }

    // -------------------------------------------------------
    // TC-AU005-02 | HAPPY — reset link sent for valid email
    // -------------------------------------------------------
    public function test_AU005_validEmail_sendsResetNotification(): void
    {
        Notification::fake();
        $user = $this->makeUser();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    // -------------------------------------------------------
    // TC-AU005-03 | HAPPY — same "sent" status returned for unknown email (no enumeration)
    // -------------------------------------------------------
    public function test_AU005_unknownEmail_returnsSentStatusWithoutRevealingExistence(): void
    {
        $response = $this->post('/forgot-password', ['email' => 'nobody@example.com']);

        // Same redirect back with status (not an error about the email not existing)
        $response->assertRedirect();
        $response->assertSessionHas('status');
    }

    // -------------------------------------------------------
    // TC-AU005-04 | HAPPY — successful reset: password changed, redirected to login
    // -------------------------------------------------------
    public function test_AU005_validToken_resetsPasswordAndRedirectsToLogin(): void
    {
        $user = $this->makeUser(['password' => Hash::make('OldPass123')]);
        $token = Password::createToken($user);

        $response = $this->post('/reset-password', [
            'token'                 => $token,
            'email'                 => $user->email,
            'password'              => 'NewPass456',
            'password_confirmation' => 'NewPass456',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertTrue(Hash::check('NewPass456', $user->fresh()->password));
    }

    // -------------------------------------------------------
    // TC-AU005-05 | SECURITY — new password is stored as bcrypt hash
    // -------------------------------------------------------
    public function test_AU005_resetPassword_passwordIsHashed(): void
    {
        $user = $this->makeUser();
        $token = Password::createToken($user);

        $this->post('/reset-password', [
            'token'                 => $token,
            'email'                 => $user->email,
            'password'              => 'Hashed789',
            'password_confirmation' => 'Hashed789',
        ]);

        $fresh = $user->fresh();
        $this->assertNotEquals('Hashed789', $fresh->password);
        $this->assertStringStartsWith('$2y$', $fresh->password);
    }

    // -------------------------------------------------------
    // TC-AU005-06 | SECURITY — expired / invalid token returns error
    // -------------------------------------------------------
    public function test_AU005_invalidToken_returnsErrorNotRedirectToLogin(): void
    {
        $user = $this->makeUser();

        $response = $this->post('/reset-password', [
            'token'                 => 'invalid-token',
            'email'                 => $user->email,
            'password'              => 'NewPass456',
            'password_confirmation' => 'NewPass456',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['email']);
        // Password must not have changed
        $this->assertFalse(Hash::check('NewPass456', $user->fresh()->password));
    }

    // -------------------------------------------------------
    // TC-AU005-07 | NEGATIVE — empty email on forgot-password form fails validation
    // -------------------------------------------------------
    public function test_AU005_emptyEmail_failsValidation(): void
    {
        $response = $this->post('/forgot-password', ['email' => '']);
        $response->assertSessionHasErrors(['email']);
    }

    // -------------------------------------------------------
    // TC-AU005-08 | NEGATIVE — invalid email format fails validation
    // -------------------------------------------------------
    public function test_AU005_invalidEmailFormat_failsValidation(): void
    {
        $response = $this->post('/forgot-password', ['email' => 'not-an-email']);
        $response->assertSessionHasErrors(['email']);
    }

    // -------------------------------------------------------
    // TC-AU005-09 | NEGATIVE — weak password on reset fails validation
    // -------------------------------------------------------
    public function test_AU005_weakPassword_failsValidation(): void
    {
        $user = $this->makeUser();
        $token = Password::createToken($user);

        $response = $this->post('/reset-password', [
            'token'                 => $token,
            'email'                 => $user->email,
            'password'              => 'weak',
            'password_confirmation' => 'weak',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    // -------------------------------------------------------
    // TC-AU005-10 | NEGATIVE — password confirmation mismatch fails validation
    // -------------------------------------------------------
    public function test_AU005_passwordMismatch_failsValidation(): void
    {
        $user = $this->makeUser();
        $token = Password::createToken($user);

        $response = $this->post('/reset-password', [
            'token'                 => $token,
            'email'                 => $user->email,
            'password'              => 'NewPass456',
            'password_confirmation' => 'DifferentPass789',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    // -------------------------------------------------------
    // TC-AU005-11 | SECURITY — CSRF middleware active on forgot-password route
    // -------------------------------------------------------
    public function test_AU005_csrfMiddlewareIsActive(): void
    {
        $this->assertTrue(class_exists(\App\Http\Middleware\VerifyCsrfToken::class));
        $webMiddleware = app(\Illuminate\Contracts\Http\Kernel::class)->getMiddlewareGroups()['web'] ?? [];
        $this->assertContains(\App\Http\Middleware\VerifyCsrfToken::class, $webMiddleware);
    }

    // -------------------------------------------------------
    // TC-AU005-12 | PERFORMANCE — forgot-password flow completes within 2s
    // -------------------------------------------------------
    public function test_AU005_resetLinkRequest_completesWithinTwoSeconds(): void
    {
        Notification::fake();
        $user = $this->makeUser();

        $start = microtime(true);
        $this->post('/forgot-password', ['email' => $user->email]);
        $elapsed = microtime(true) - $start;

        $this->assertLessThan(2.0, $elapsed, 'Password reset link request took longer than 2 seconds');
    }
}
