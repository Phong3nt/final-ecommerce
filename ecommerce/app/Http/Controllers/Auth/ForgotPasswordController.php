<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class ForgotPasswordController extends Controller
{
    /**
     * AU-005: Show the forgot-password form.
     */
    public function show(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * AU-005: Send a password reset link to the given email.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email:rfc'],
        ]);

        // We always return the same "sent" status regardless of whether the email
        // exists — prevents user enumeration via timing/message differences.
        Password::sendResetLink($request->only('email'));

        return back()->with('status', __('passwords.sent'));
    }
}
