@extends('layouts.admin')

@section('title', 'Admin — New Coupon')
@section('page-title', 'New Coupon')

@section('content')
<div x-data x-init="$el.classList.add('fade-in')">
    <div class="mb-3">
        <a href="{{ route('admin.coupons.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to Coupons
        </a>
    </div>

    <div class="card shadow-sm border-0 rounded-3" style="max-width:560px;">
        <div class="card-body">
            <h5 class="card-title mb-4">New Coupon</h5>

            <form method="POST" action="{{ route('admin.coupons.store') }}">
                @csrf

                <div class="mb-3">
                    <label for="code" class="form-label fw-semibold">Code <span class="text-danger">*</span></label>
                    <input type="text" id="code" name="code" value="{{ old('code') }}"
                        class="form-control @error('code') is-invalid @enderror"
                        placeholder="e.g. SAVE20" required maxlength="64">
                    <div class="form-text">Will be stored in UPPER CASE.</div>
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label for="name" class="form-label fw-semibold">Name <span class="text-muted fw-normal">(optional)</span></label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}"
                        class="form-control @error('name') is-invalid @enderror"
                        maxlength="120" placeholder="e.g. Welcome Gift Coupon">
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label fw-semibold">Description/Story <span class="text-muted fw-normal">(optional)</span></label>
                    <textarea id="description" name="description" rows="3"
                        class="form-control @error('description') is-invalid @enderror"
                        placeholder="Explain who this coupon is for and when it should be used.">{{ old('description') }}</textarea>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label for="type" class="form-label fw-semibold">Discount Type <span class="text-danger">*</span></label>
                    <select id="type" name="type" class="form-select @error('type') is-invalid @enderror" required>
                        <option value="">— Select type —</option>
                        <option value="percent" {{ old('type') === 'percent' ? 'selected' : '' }}>Percentage (%)</option>
                        <option value="fixed" {{ old('type') === 'fixed' ? 'selected' : '' }}>Fixed Amount ($)</option>
                    </select>
                    @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label for="value" class="form-label fw-semibold">Value <span class="text-danger">*</span></label>
                    <input type="number" id="value" name="value" value="{{ old('value') }}"
                        class="form-control @error('value') is-invalid @enderror"
                        step="0.01" min="0.01" required>
                    <div class="form-text">For % type enter a number like 20 (= 20%). For fixed enter the dollar amount.</div>
                    @error('value')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label for="min_order_amount" class="form-label fw-semibold">Minimum Order Amount <span class="text-muted fw-normal">(optional)</span></label>
                    <input type="number" id="min_order_amount" name="min_order_amount" value="{{ old('min_order_amount') }}"
                        class="form-control @error('min_order_amount') is-invalid @enderror"
                        step="0.01" min="1" placeholder="1.00">
                    @error('min_order_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label for="usage_limit" class="form-label fw-semibold">Usage Limit <span class="text-muted fw-normal">(optional)</span></label>
                    <input type="number" id="usage_limit" name="usage_limit" value="{{ old('usage_limit') }}"
                        class="form-control @error('usage_limit') is-invalid @enderror"
                        step="1" min="1">
                    <div class="form-text">Leave blank for unlimited uses.</div>
                    @error('usage_limit')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label for="expires_at" class="form-label fw-semibold">Expiry Date <span class="text-muted fw-normal">(optional)</span></label>
                    <input type="date" id="expires_at" name="expires_at" value="{{ old('expires_at') }}"
                        class="form-control @error('expires_at') is-invalid @enderror">
                    @error('expires_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-4">
                    <input type="hidden" name="is_active" value="0">
                    <div class="form-check">
                        <input type="checkbox" id="is_active" name="is_active" value="1"
                            class="form-check-input" {{ old('is_active', '1') == '1' ? 'checked' : '' }}>
                        <label for="is_active" class="form-check-label">Active</label>
                    </div>
                    @error('is_active')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Create Coupon</button>
                    <a href="{{ route('admin.coupons.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
