@extends('layouts.app')

@section('title', 'Dashboard — E-Commerce')

@section('content')
    <div x-data x-init="$el.classList.add('fade-in')">

        {{-- Page header --}}
        <div class="mb-4">
            <h1 class="h3 fw-bold mb-1">Welcome back, {{ auth()->user()->name }}!</h1>
            <p class="text-muted mb-0">Here's an overview of your account.</p>
        </div>

        {{-- Email verification alert --}}
        @if (!auth()->user()->hasVerifiedEmail())
            <div class="alert alert-warning d-flex align-items-center gap-2 mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
                <div>
                    Your email is not verified yet.
                    <form method="POST" action="{{ route('verification.send') }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-link p-0 align-baseline text-warning fw-semibold"
                            x-data="{ loading: false }" @click="loading = true" :disabled="loading">
                            <span x-show="loading" class="spinner-border spinner-border-sm me-1" role="status"></span>
                            <span x-text="loading ? 'Sending…' : 'Resend verification email'">Resend verification email</span>
                        </button>
                    </form>
                </div>
            </div>
        @endif

        {{-- Quick-action cards --}}
        <div class="row g-3">

            {{-- Shop --}}
            <div class="col-sm-6 col-lg-3">
                <a href="{{ route('products.index') }}" class="text-decoration-none">
                    <div class="card shadow-sm border-0 h-100 card-hover">
                        <div class="card-body d-flex align-items-center gap-3 p-4">
                            <div class="d-inline-flex align-items-center justify-content-center
                                        bg-primary bg-opacity-10 rounded-3 flex-shrink-0" style="width:48px;height:48px;">
                                <i class="bi bi-shop fs-4 text-primary"></i>
                            </div>
                            <div>
                                <p class="text-label mb-1">Browse</p>
                                <h6 class="fw-bold mb-0">Shop</h6>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            {{-- My Orders --}}
            <div class="col-sm-6 col-lg-3">
                <a href="{{ route('orders.index') }}" class="text-decoration-none">
                    <div class="card shadow-sm border-0 h-100 card-hover">
                        <div class="card-body d-flex align-items-center gap-3 p-4">
                            <div class="d-inline-flex align-items-center justify-content-center
                                        bg-success bg-opacity-10 rounded-3 flex-shrink-0" style="width:48px;height:48px;">
                                <i class="bi bi-bag-check fs-4 text-success"></i>
                            </div>
                            <div>
                                <p class="text-label mb-1">Track</p>
                                <h6 class="fw-bold mb-0">My Orders</h6>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            {{-- Profile --}}
            <div class="col-sm-6 col-lg-3">
                <a href="{{ route('profile.show') }}" class="text-decoration-none">
                    <div class="card shadow-sm border-0 h-100 card-hover">
                        <div class="card-body d-flex align-items-center gap-3 p-4">
                            <div class="d-inline-flex align-items-center justify-content-center
                                        bg-warning bg-opacity-10 rounded-3 flex-shrink-0" style="width:48px;height:48px;">
                                <i class="bi bi-person fs-4 text-warning"></i>
                            </div>
                            <div>
                                <p class="text-label mb-1">Settings</p>
                                <h6 class="fw-bold mb-0">Profile</h6>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            {{-- Addresses --}}
            <div class="col-sm-6 col-lg-3">
                <a href="{{ route('addresses.index') }}" class="text-decoration-none">
                    <div class="card shadow-sm border-0 h-100 card-hover">
                        <div class="card-body d-flex align-items-center gap-3 p-4">
                            <div class="d-inline-flex align-items-center justify-content-center
                                        bg-info bg-opacity-10 rounded-3 flex-shrink-0" style="width:48px;height:48px;">
                                <i class="bi bi-geo-alt fs-4 text-info"></i>
                            </div>
                            <div>
                                <p class="text-label mb-1">Manage</p>
                                <h6 class="fw-bold mb-0">Addresses</h6>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

        </div>{{-- /.row --}}
    </div>
@endsection