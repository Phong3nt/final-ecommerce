<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Spatie\Permission\Models\Role;

class GoogleController extends Controller
{
    /**
     * AU-003: Redirect the visitor to Google's OAuth consent page.
     */
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * AU-003: Handle the callback from Google after the user grants access.
     *
     * Scenarios handled:
     *   1. User already linked with this google_id  → log in directly.
     *   2. User exists by email but no google_id    → link google_id then log in.
     *   3. No matching user found                   → auto-register then log in.
     *   4. Socialite throws any exception           → redirect to login with error.
     */
    public function callback(Request $request): RedirectResponse
    {
        try {
            $socialUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Unable to authenticate with Google. Please try again.']);
        }

        // 1 & 2: look up by google_id first, fall back to email
        $user = User::where('google_id', $socialUser->getId())->first()
            ?? User::where('email', $socialUser->getEmail())->first();

        if ($user) {
            // Link google_id on first Google login for an existing email account
            if (! $user->google_id) {
                $user->update(['google_id' => $socialUser->getId()]);
            }
        } else {
            // 3: Auto-register new Google user
            $role = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

            $user = User::create([
                'name'              => $socialUser->getName() ?? $socialUser->getEmail(),
                'email'             => $socialUser->getEmail(),
                'google_id'         => $socialUser->getId(),
                'password'          => bcrypt(Str::random(32)),
                'email_verified_at' => now(),   // Google already verified the address
                'is_active'         => true,
            ]);

            $user->assignRole($role);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}
