@extends('layouts.app')

@section('title', 'Forgot Password — E-Commerce')

@section('content')
@include('partials.toast')
<div class="min-vh-50 d-flex align-items-center justify-content-center py-5">
    <div class="w-100" style="max-width:440px;">

        {{-- Card --}}
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4 p-md-5">

                {{-- Header --}}
                <div class="text-center mb-4">
                    <div class="d-inline-flex align-items-center justify-content-center
                                bg-warning bg-opacity-10 rounded-circle mb-3"
                         style="width:56px;height:56px;">
                        <i class="bi bi-key fs-4 text-warning"></i>
                    </div>
                    <h1 class="h4 fw-bold mb-1">Forgot Password?</h1>
                    <p class="text-muted small mb-0">
                        Enter your email and we&rsquo;ll send you a reset link.
                    </p>
                </div>

                {{-- Forgot-password form --}}
                <form method="POST" action="{{ route('password.email') }}"
                      x-data="{ loading: false }" @submit="loading = true">
                    @csrf

                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold">Email address</label>
                        <input type="email" id="email" name="email"
                               class="form-control @error('email') is-invalid @enderror"
                               value="{{ old('email') }}"
                               required autocomplete="email" autofocus>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-primary"
                                :disabled="loading">
                            <span x-show="!loading">Send Reset Link</span>
                            <span x-show="loading" class="d-flex align-items-center justify-content-center gap-2">
                                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                Sending&hellip;
                            </span>
                        </button>
                    </div>
                </form>

            </div>
        </div>

        {{-- Back to login --}}
        <p class="text-center text-muted small mt-3 mb-0">
            Remember your password?
            <a href="{{ route('login') }}" class="text-decoration-none fw-semibold">Sign in</a>
        </p>

    </div>
</div>
@endsection