@extends('layouts.app')

@section('title', 'Verify Email — E-Commerce')

@section('content')
    <div class="d-flex justify-content-center align-items-center py-5">
        <div class="card shadow-sm border-0 rounded-3" style="max-width:480px;width:100%;">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <i class="bi bi-envelope-check fs-1 text-primary"></i>
                    <h4 class="fw-bold mt-2 mb-1">Verify Your Email Address</h4>
                    <p class="text-muted small mb-0">
                        Thanks for signing up! Please verify your email address by clicking the link we sent you.
                    </p>
                </div>

                @if (session('error'))
                    <div class="alert alert-danger d-flex align-items-center gap-2 mb-3" role="alert">
                        <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
                        <span>{{ session('error') }}</span>
                    </div>
                @endif

                @if (session('status') === 'verification-link-sent')
                    <div class="alert alert-success d-flex align-items-center gap-2 mb-3" role="alert">
                        <i class="bi bi-check-circle-fill flex-shrink-0"></i>
                        <span>A new verification link has been sent to your email address.</span>
                    </div>
                @endif

                <p class="text-muted small text-center mb-3">
                    Didn't receive the email? Check your spam folder, or request a new link below.
                </p>

                <form method="POST" action="{{ route('verification.send') }}" class="mb-3">
                    @csrf
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-send me-1"></i> Resend Verification Email
                    </button>
                </form>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary w-100">Log Out</button>
                </form>
            </div>
        </div>
    </div>
@endsection