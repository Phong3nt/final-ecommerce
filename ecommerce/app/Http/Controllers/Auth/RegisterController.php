<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisterController extends Controller
{
    /**
     * Show the registration form.
     */
    public function show(): View
    {
        return view('auth.register');
    }

    /**
     * Handle the registration form submission.
     * AU-001: Creates user, fires Registered event (sends verification email), logs in.
     */
    public function store(RegisterRequest $request): RedirectResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password, // auto-hashed by cast
        ]);

        $user->assignRole('user');

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('dashboard')
            ->with('success', 'Registration successful! Please check your email to verify your account.');
    }
}
