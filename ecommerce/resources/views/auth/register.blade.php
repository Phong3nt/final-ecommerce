@extends('layouts.app')

@section('title', 'Register — E-Commerce')

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
                    <p class="text-muted small">Create your account</p>
                </div>

                {{-- Register form --}}
                <form method="POST" action="{{ route('register.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold">Full name</label>
                        <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name') }}" required autofocus autocomplete="name">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

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
                        <label for="password" class="form-label fw-semibold">Password</label>
                        <input type="password" id="password" name="password"
                            class="form-control @error('password') is-invalid @enderror" required
                            autocomplete="new-password">
                        <div class="form-text text-muted">
                            <i class="bi bi-info-circle me-1"></i>Min 8 chars, upper &amp; lower case, number.
                        </div>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="password_confirmation" class="form-label fw-semibold">Confirm password</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" class="form-control"
                            required autocomplete="new-password">
                    </div>

                    <button type="submit" class="btn btn-primary w-100" x-data="{ loading: false }" @click="loading = true"
                        :disabled="loading">
                        <span x-show="loading" class="spinner-border spinner-border-sm me-2" role="status"></span>
                        <span x-text="loading ? 'Creating account…' : 'Create account'">Create account</span>
                    </button>
                </form>

                <p class="text-center mt-3 small mb-0">
                    Already have an account?
                    <a href="{{ route('login') }}" class="fw-semibold">Sign in</a>
                </p>

            </div>
        </div>
    </div>
@endsection