<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * FIX-001 — EmailVerificationController missing
 *
 * Parent tasks: AU-001 (MustVerifyEmail interface, Registered event)
 *               AU-002 (verification routes present in web.php)
 * Root cause:   Controller class was imported in routes but never created.
 * Fix:          Created App\Http\Controllers\Auth\EmailVerificationController.
 *
 * New test cases: TC-FIX001-01 through TC-FIX001-06
 */
class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'user',  'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    }

    // -------------------------------------------------------
    // TC-FIX001-01 | HAPPY PATH
    // Unverified authenticated user visits /email/verify
    // → sees the verification notice view
    // -------------------------------------------------------
    public function test_FIX001_unverifiedUser_seesVerificationNotice(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get(route('verification.notice'));

        $response->assertStatus(200);
        $response->assertSee('Verify Your Email Address');
    }

    // -------------------------------------------------------
    // TC-FIX001-02 | EDGE
    // Already-verified user visits /email/verify
    // → redirected to dashboard (not shown the notice)
    // -------------------------------------------------------
    public function test_FIX001_alreadyVerifiedUser_redirectsToDashboard(): void
    {
        $user = User::factory()->create(); // factory creates with email_verified_at set

        $response = $this->actingAs($user)->get(route('verification.notice'));

        $response->assertRedirect(route('dashboard'));
    }

    // -------------------------------------------------------
    // TC-FIX001-03 | SECURITY
    // Unauthenticated user hits /email/verify
    // → redirected to login
    // -------------------------------------------------------
    public function test_FIX001_unauthenticatedUser_redirectsToLogin(): void
    {
        $response = $this->get(route('verification.notice'));

        $response->assertRedirect(route('login'));
    }

    // -------------------------------------------------------
    // TC-FIX001-04 | HAPPY PATH
    // Authenticated unverified user visits valid signed verify URL
    // → email marked as verified, redirected to dashboard with ?verified=1
    // -------------------------------------------------------
    public function test_FIX001_validSignedVerifyLink_marksEmailVerified(): void
    {
        $user = User::factory()->unverified()->create();

        $verifyUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->get($verifyUrl);

        $response->assertRedirect(route('dashboard') . '?verified=1');
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    // -------------------------------------------------------
    // TC-FIX001-05 | EDGE
    // Already-verified user visits a verify link
    // → still redirected to dashboard with ?verified=1 (idempotent)
    // -------------------------------------------------------
    public function test_FIX001_alreadyVerifiedUserHitsVerifyLink_redirectsGracefully(): void
    {
        $user = User::factory()->create(); // already verified

        $verifyUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->get($verifyUrl);

        $response->assertRedirect(route('dashboard') . '?verified=1');
    }

    // -------------------------------------------------------
    // TC-FIX001-06 | HAPPY PATH
    // Unverified user posts to /email/verification-notification
    // → notification queued, redirected back with 'verification-link-sent' status
    // -------------------------------------------------------
    public function test_FIX001_resendVerification_queuesNotificationAndFlashesStatus(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->post(route('verification.send'));

        $response->assertRedirect();
        $response->assertSessionHas('status', 'verification-link-sent');
        Notification::assertSentTo($user, VerifyEmail::class);
    }
}
