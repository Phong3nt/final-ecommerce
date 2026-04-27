@extends('layouts.app')

@section('title', '403 — Forbidden')

@section('content')
    <div class="d-flex justify-content-center align-items-center py-5">
        <div class="card shadow-sm border-0 rounded-3 text-center" style="max-width:480px;width:100%;">
            <div class="card-body p-4 p-md-5">
                <div class="d-inline-flex align-items-center justify-content-center
                                bg-danger bg-opacity-10 rounded-circle mb-3" style="width:64px;height:64px;">
                    <i class="bi bi-shield-exclamation fs-2 text-danger"></i>
                </div>
                <h1 class="h3 fw-bold mb-2">403 — Forbidden</h1>
                <p class="text-muted mb-4">
                    {{ isset($exception) && $exception->getMessage() ? $exception->getMessage() : "You don't have permission to access this page." }}
                </p>
                <div class="d-flex gap-2 justify-content-center">
                    <button onclick="history.back()" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Go Back
                    </button>
                    <a href="{{ url('/') }}" class="btn btn-primary">
                        <i class="bi bi-house me-1"></i> Go Home
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection