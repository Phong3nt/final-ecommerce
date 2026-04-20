<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    /**
     * AU-002: Show the login form.
     */
    public function show(): View
    {
        return view('auth.login');
    }

    /**
     * AU-002: Attempt login with email and password.
     *
     * On success: regenerate session, redirect to intended URL or dashboard.
     * On failure: redirect back with 'email' error message.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            // IMP-016: log failed login attempt (user_id may be null — unknown or non-existent user)
            $attempted = User::where('email', $request->email)->first();
            AuditLog::create([
                'user_id'      => $attempted?->id,
                'action'       => 'auth.login_failed',
                'subject_type' => 'User',
                'subject_id'   => $attempted?->id ?? 0,
                'new_values'   => ['email' => $request->email],
                'ip_address'   => $request->ip(),
            ]);

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => __('auth.failed')]);
        }

        // UM-003: Block suspended users from logging in
        if (!Auth::user()->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Your account has been suspended. Please contact support.']);
        }

        $request->session()->regenerate();

        // IMP-016: log successful login
        AuditLog::create([
            'user_id'      => Auth::id(),
            'action'       => 'auth.login',
            'subject_type' => 'User',
            'subject_id'   => Auth::id(),
            'new_values'   => ['email' => Auth::user()->email],
            'ip_address'   => $request->ip(),
        ]);

        return redirect()->intended(route('dashboard'));
    }

    /**
     * AU-002: Log the user out and invalidate session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        // IMP-016: log logout before the session is destroyed
        if (Auth::check()) {
            AuditLog::create([
                'user_id'      => Auth::id(),
                'action'       => 'auth.logout',
                'subject_type' => 'User',
                'subject_id'   => Auth::id(),
                'ip_address'   => $request->ip(),
            ]);
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
