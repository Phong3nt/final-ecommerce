@extends('layouts.app')

@section('title', 'My Profile — E-Commerce')

@section('content')
    <div x-data x-init="$el.classList.add('fade-in')">

        {{-- Page header --}}
        <div class="mb-4">
            <h1 class="h3 fw-bold mb-1">My Profile</h1>
            <p class="text-muted mb-0">Update your name, email address and avatar.</p>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-7 col-xl-6">
                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-body p-4">

                        {{-- Avatar preview --}}
                        <div class="text-center mb-4">
                            @if ($user->avatar)
                                <img src="{{ Storage::url($user->avatar) }}" alt="Avatar" class="rounded-circle border"
                                    width="88" height="88" style="object-fit:cover;">
                            @else
                                <div class="rounded-circle bg-primary bg-opacity-10 d-inline-flex
                                                align-items-center justify-content-center border"
                                    style="width:88px;height:88px;">
                                    <i class="bi bi-person fs-2 text-primary"></i>
                                </div>
                            @endif
                            <p class="text-muted small mt-2 mb-0">{{ $user->email }}</p>
                        </div>

                        {{-- Profile form --}}
                        <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')

                            {{-- Name --}}
                            <div class="mb-3">
                                <label for="name" class="form-label fw-semibold">Full Name</label>
                                <input type="text" id="name" name="name"
                                    class="form-control @error('name') is-invalid @enderror"
                                    value="{{ old('name', $user->name) }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Email --}}
                            <div class="mb-3">
                                <label for="email" class="form-label fw-semibold">Email Address</label>
                                <input type="email" id="email" name="email"
                                    class="form-control @error('email') is-invalid @enderror"
                                    value="{{ old('email', $user->email) }}" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Avatar upload --}}
                            <div class="mb-4">
                                <label for="avatar" class="form-label fw-semibold">
                                    Avatar
                                    <span class="text-muted fw-normal">(jpg / png, max 2 MB)</span>
                                </label>
                                <input type="file" id="avatar" name="avatar" accept="image/jpeg,image/png"
                                    class="form-control @error('avatar') is-invalid @enderror">
                                @error('avatar')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Submit --}}
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary px-4" x-data="{ loading: false }"
                                    @click="loading = true" :disabled="loading">
                                    <span x-show="loading" class="spinner-border spinner-border-sm me-2"
                                        role="status"></span>
                                    <span x-text="loading ? 'Saving…' : 'Save Changes'">Save Changes</span>
                                </button>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection