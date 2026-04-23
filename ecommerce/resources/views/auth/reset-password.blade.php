@extends('layouts.app')

@section('title', 'Reset Password — E-Commerce')

@section('content')
<div class="min-vh-50 d-flex align-items-center justify-content-center py-5">
    <div class="w-100" style="max-width:440px;">

        {{-- Card --}}
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4 p-md-5">

                {{-- Header --}}
                <div class="text-center mb-4">
                    <div class="d-inline-flex align-items-center justify-content-center
                                bg-success bg-opacity-10 rounded-circle mb-3"
                         style="width:56px;height:56px;">
                        <i class="bi bi-shield-lock fs-4 text-success"></i>
                    </div>
                    <h1 class="h4 fw-bold mb-1">Reset Password</h1>
                    <p class="text-muted small mb-0">Choose a strong new password.</p>
                </div>

                {{-- Reset-password form --}}
                <form method="POST" action="{{ route('password.update') }}"
                      x-data="{ loading: false }" @submit="loading = true">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">

                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold">Email address</label>
                        <input type="email" id="email" name="email"
                               class="form-control @error('email') is-invalid @enderror"
                               value="{{ $email ?? old('email') }}"
                               required autocomplete="email">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label fw-semibold">New password</label>
                        <input type="password" id="password" name="password"
                               class="form-control @error('password') is-invalid @enderror"
                               required autocomplete="new-password">
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label fw-semibold">
                            Confirm new password
                        </label>
                        <input type="password" id="password_confirmation"
                               name="password_confirmation"
                               class="form-control"
                               required autocomplete="new-password">
                    </div>

                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-success"
                                :disabled="loading">
                            <span x-show="!loading">Reset Password</span>
                            <span x-show="loading" class="d-flex align-items-center justify-content-center gap-2">
                                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                Resetting&hellip;
                            </span>
                        </button>
                    </div>
                </form>

            </div>
        </div>

        {{-- Back to login --}}
        <p class="text-center text-muted small mt-3 mb-0">
            <a href="{{ route('login') }}" class="text-decoration-none fw-semibold">Back to Sign in</a>
        </p>

    </div>
</div>
@endsection