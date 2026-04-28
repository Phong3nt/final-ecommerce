@extends('layouts.app')

@section('title', 'Page Not Found — E-Commerce')

@section('content')
    <div class="min-vh-100 d-flex align-items-center justify-content-center py-5">
        <div class="text-center">
            <div class="display-1 fw-bold text-primary mb-0">404</div>
            <p class="fs-4 fw-semibold text-dark mb-2">Page not found</p>
            <p class="text-muted mb-4">
                {{ $message ?? "The page you're looking for doesn't exist or has been moved." }}
            </p>
            <div class="d-flex justify-content-center gap-2 flex-wrap">
                <a href="{{ route('products.index') }}" class="btn btn-primary px-4">
                    <i class="bi bi-shop me-1"></i>Browse Products
                </a>
                <a href="javascript:history.back()" class="btn btn-outline-secondary px-4">
                    <i class="bi bi-arrow-left me-1"></i>Go Back
                </a>
            </div>
        </div>
    </div>
@endsection