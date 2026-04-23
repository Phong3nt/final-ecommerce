@extends('layouts.app')
@section('title', 'My Addresses — E-Commerce')

@section('content')
@include('partials.toast')

<div class="d-flex align-items-center justify-content-between mb-4">
    <h2 class="h4 fw-semibold mb-0">
        <i class="bi bi-geo-alt me-2 text-primary"></i>My Addresses
    </h2>
    <a href="#add-address" class="btn btn-primary btn-sm" x-data @click.prevent="$dispatch('open-add-form')">
        <i class="bi bi-plus-lg me-1"></i>Add New Address
    </a>
</div>

{{-- Address list or empty state --}}
@if($addresses->isEmpty())
    <div class="card shadow-sm border-0 rounded-4 text-center py-5 mb-4">
        <div class="card-body">
            <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-secondary bg-opacity-10 mb-3"
                  style="width:64px;height:64px;">
                <i class="bi bi-geo-alt fs-2 text-secondary"></i>
            </span>
            <p class="text-muted mb-3">You have no saved addresses yet. Add one to speed up checkout!</p>
            <a href="#add-address" class="btn btn-primary btn-sm" x-data @click.prevent="$dispatch('open-add-form')">
                <i class="bi bi-plus-lg me-1"></i>Add Your First Address
            </a>
        </div>
    </div>
@else
    @foreach($addresses as $address)
        <div class="card shadow-sm border-0 rounded-4 mb-3" x-data="{ editing: false }">
            <div class="card-body p-4">
                {{-- Header --}}
                <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-2">
                    <h6 class="fw-semibold mb-0">
                        {{ $address->name }}
                        @if($address->is_default)
                            <span class="badge bg-success ms-2">
                                <i class="bi bi-geo-alt-fill me-1"></i>Default
                            </span>
                        @endif
                    </h6>
                    <div class="d-flex gap-2 flex-wrap">
                        @unless($address->is_default)
                            <form method="POST" action="{{ route('addresses.setDefault', $address) }}" class="m-0">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-outline-success btn-sm">
                                    <i class="bi bi-check-circle me-1"></i>Set Default
                                </button>
                            </form>
                        @endunless
                        <button type="button" class="btn btn-outline-primary btn-sm" @click="editing = !editing">
                            <i class="bi bi-pencil me-1"></i>Edit
                        </button>
                        <form method="POST" action="{{ route('addresses.destroy', $address) }}" class="m-0"
                              @submit.prevent="if(confirm('Delete this address?')) $el.submit()">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                <i class="bi bi-trash me-1"></i>Delete
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Address display --}}
                <address class="text-muted small mb-0" x-show="!editing">
                    {{ $address->address_line1 }}@if($address->address_line2), {{ $address->address_line2 }}@endif<br>
                    {{ $address->city }}, {{ $address->state }} {{ $address->postal_code }}<br>
                    {{ $address->country }}
                </address>

                {{-- Inline edit form --}}
                <div x-show="editing" x-transition class="mt-3 pt-3 border-top">
                    <form method="POST" action="{{ route('addresses.update', $address) }}">
                        @csrf
                        @method('PUT')
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label small fw-medium">Recipient Name</label>
                                <input type="text" name="name" class="form-control form-control-sm"
                                       value="{{ old('name', $address->name) }}" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-medium">Address Line 1</label>
                                <input type="text" name="address_line1" class="form-control form-control-sm"
                                       value="{{ old('address_line1', $address->address_line1) }}" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-medium">Address Line 2 <span class="text-muted">(optional)</span></label>
                                <input type="text" name="address_line2" class="form-control form-control-sm"
                                       value="{{ old('address_line2', $address->address_line2) }}">
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label small fw-medium">City</label>
                                <input type="text" name="city" class="form-control form-control-sm"
                                       value="{{ old('city', $address->city) }}" required>
                            </div>
                            <div class="col-sm-3">
                                <label class="form-label small fw-medium">State</label>
                                <input type="text" name="state" class="form-control form-control-sm"
                                       value="{{ old('state', $address->state) }}" required>
                            </div>
                            <div class="col-sm-3">
                                <label class="form-label small fw-medium">Postal Code</label>
                                <input type="text" name="postal_code" class="form-control form-control-sm"
                                       value="{{ old('postal_code', $address->postal_code) }}" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-medium">Country</label>
                                <input type="text" name="country" class="form-control form-control-sm"
                                       value="{{ old('country', $address->country) }}" required>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_default" value="1"
                                           id="default_{{ $address->id }}" {{ $address->is_default ? 'checked' : '' }}>
                                    <label class="form-check-label small" for="default_{{ $address->id }}">
                                        Set as default address
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex gap-2 mt-3">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="bi bi-floppy me-1"></i>Save Changes
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" @click="editing = false">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
@endif

{{-- Add new address --}}
<div id="add-address" class="card shadow-sm border-0 rounded-4 mt-2"
     x-data="{ open: {{ $errors->any() ? 'true' : 'false' }} }"
     @open-add-form.window="open = true; $nextTick(() => $el.scrollIntoView({ behavior: 'smooth' }))">
    <div class="card-header bg-transparent border-0 d-flex align-items-center justify-content-between pt-4 px-4 pb-3">
        <h6 class="fw-semibold mb-0">
            <i class="bi bi-plus-circle me-2 text-primary"></i>Add New Address
        </h6>
        <button type="button" class="btn btn-link btn-sm p-0 text-muted" @click="open = !open">
            <i class="bi" :class="open ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
        </button>
    </div>
    <div class="card-body px-4 pb-4 pt-0" x-show="open" x-transition>
        @if($errors->any())
            <div class="alert alert-danger rounded-3 mb-3">
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $error)
                        <li class="small">{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <form method="POST" action="{{ route('addresses.store') }}" x-data="{ loading: false }" @submit="loading = true">
            @csrf
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label small fw-medium">Recipient Name</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name') }}" placeholder="e.g. Jane Doe" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label small fw-medium">Address Line 1</label>
                    <input type="text" name="address_line1" class="form-control @error('address_line1') is-invalid @enderror"
                           value="{{ old('address_line1') }}" placeholder="Street address" required>
                    @error('address_line1')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label small fw-medium">Address Line 2 <span class="text-muted">(optional)</span></label>
                    <input type="text" name="address_line2" class="form-control"
                           value="{{ old('address_line2') }}" placeholder="Apt, suite, unit, etc.">
                </div>
                <div class="col-sm-6">
                    <label class="form-label small fw-medium">City</label>
                    <input type="text" name="city" class="form-control @error('city') is-invalid @enderror"
                           value="{{ old('city') }}" required>
                    @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-sm-3">
                    <label class="form-label small fw-medium">State</label>
                    <input type="text" name="state" class="form-control @error('state') is-invalid @enderror"
                           value="{{ old('state') }}" required>
                    @error('state')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-sm-3">
                    <label class="form-label small fw-medium">Postal Code</label>
                    <input type="text" name="postal_code" class="form-control @error('postal_code') is-invalid @enderror"
                           value="{{ old('postal_code') }}" required>
                    @error('postal_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label small fw-medium">Country</label>
                    <input type="text" name="country" class="form-control @error('country') is-invalid @enderror"
                           value="{{ old('country') }}" required>
                    @error('country')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_default" value="1"
                               id="new_default" {{ old('is_default') ? 'checked' : '' }}>
                        <label class="form-check-label small" for="new_default">
                            Set as my default address
                        </label>
                    </div>
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-primary" :disabled="loading">
                    <span x-show="loading" class="spinner-border spinner-border-sm me-2" role="status"></span>
                    <i x-show="!loading" class="bi bi-plus-lg me-1"></i>
                    <span x-text="loading ? 'Saving...' : 'Add Address'">Add Address</span>
                </button>
                <a href="{{ route('profile.show') }}" class="btn btn-outline-secondary ms-2">
                    <i class="bi bi-person me-1"></i>Back to Profile
                </a>
            </div>
        </form>
    </div>
</div>

@endsection