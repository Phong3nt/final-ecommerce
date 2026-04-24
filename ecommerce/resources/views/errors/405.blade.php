@extends('layouts.app')

@section('title', '405 — Method Not Allowed')

@section('content')
    <div class="min-vh-100 d-flex align-items-center justify-content-center py-5">
        <div class="text-center" x-data x-init="$el.classList.add('fade-in')">
            <div class="d-inline-flex align-items-center justify-content-center
                        bg-warning bg-opacity-10 rounded-circle mb-4" style="width:80px;height:80px;">
                <i class="bi bi-exclamation-triangle-fill fs-2 text-warning"></i>
            </div>
            <h1 class="display-6 fw-bold mb-2">405</h1>
            <h2 class="h5 fw-semibold text-muted mb-3">Method Not Allowed</h2>
            <p class="text-muted mb-4">That action is not permitted on this resource.</p>
            <div class="d-flex justify-content-center gap-2">
                <a href="javascript:history.back()" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Go Back
                </a>
                <a href="{{ url('/') }}" class="btn btn-primary">
                    <i class="bi bi-house me-1"></i> Home
                </a>
            </div>
        </div>
    </div>
@endsection