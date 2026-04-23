@extends('layouts.app')

@section('title', 'Login — E-Commerce')

@section('content')
    <div class="min-vh-100 d-flex align-items-center justify-content-center py-5">
        <div class="card shadow-sm border-0 rounded-4" style="width:100%;max-width:420px;">
            <div class="card-body p-4 p-md-5">

                {{-- Brand header --}}
                <div class="text-center mb-4">
                    <div class="d-inline-flex align-items-center justify-content-center
                                bg-primary bg-opacity-10 rounded-circle mb-3" style="width:56px;height:56px;">
                        <i class="bi bi-shop fs-4 text-primary"></i>
                    </div>
                    <h1 class="h4 fw-bold mb-0">E-Commerce</h1>
                    <p class="text-muted small">Sign in to your account</p>
                </div>

                {{-- Google OAuth --}}
                <a href="{{ route('auth.google.redirect') }}"
                    class="btn btn-outline-dark w-100 d-flex align-items-center justify-content-center gap-2 mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 48 48" aria-hidden="true">
                        <path fill="#EA4335"
                            d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z" />
                        <path fill="#4285F4"
                            d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z" />
                        <path fill="#FBBC05"
                            d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z" />
                        <path fill="#34A853"
                            d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.18 1.48-4.97 2.36-8.16 2.36-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z" />
                        <path fill="none" d="M0 0h48v48H0z" />
                    </svg>
                    Continue with Google
                </a>

                <div class="d-flex align-items-center gap-2 mb-3">
                    <hr class="flex-grow-1 my-0">
                    <span class="text-muted small">or</span>
                    <hr class="flex-grow-1 my-0">
                </div>

                {{-- Login form --}}
                <form method="POST" action="{{ route('login.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold">Email address</label>
                        <input type="email" id="email" name="email"
                            class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required
                            autocomplete="email">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-baseline mb-1">
                            <label for="password" class="form-label fw-semibold mb-0">Password</label>
                            <a href="{{ route('password.request') }}" class="small text-muted">Forgot password?</a>
                        </div>
                        <input type="password" id="password" name="password"
                            class="form-control @error('password') is-invalid @enderror" required
                            autocomplete="current-password">
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="remember" id="remember">
                            <label class="form-check-label text-muted small" for="remember">
                                Remember me
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 px-4" x-data="{ loading: false }"
                        @click="loading = true" :disabled="loading">
                        <span x-show="loading" class="spinner-border spinner-border-sm me-2" role="status"></span>
                        <span x-text="loading ? 'Signing in…' : 'Sign in'">Sign in</span>
                    </button>
                </form>

                <p class="text-center mt-3 small text-muted mb-0">
                    Don't have an account?
                    <a href="{{ route('register') }}" class="fw-semibold">Create one</a>
                </p>

            </div>
        </div>
    </div>
@endsection