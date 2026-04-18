<!DOCTYPE html>
<html>

<head>
    <title>Admin — New Coupon</title>
    <style>
        body {
            font-family: sans-serif;
            margin: 2rem;
            background: #f5f5f5;
        }

        h1 {
            margin-bottom: 1rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        label {
            display: block;
            font-weight: 600;
            margin-bottom: .25rem;
        }

        input,
        select {
            width: 100%;
            max-width: 480px;
            padding: .5rem;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .hint {
            font-size: .8rem;
            color: #6c757d;
            margin-top: .2rem;
        }

        .error {
            color: #dc3545;
            font-size: .875rem;
            margin-top: .25rem;
        }

        .check-row {
            display: flex;
            align-items: center;
            gap: .5rem;
        }

        .check-row input {
            width: auto;
        }

        button[type=submit] {
            padding: .5rem 1.5rem;
            background: #198754;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
        }

        a {
            color: #0d6efd;
        }
    </style>
</head>

<body>
    <h1>New Coupon</h1>

    <form method="POST" action="{{ route('admin.coupons.store') }}">
        @csrf

        <div class="form-group">
            <label for="code">Code *</label>
            <input type="text" id="code" name="code" value="{{ old('code') }}" placeholder="e.g. SAVE20" required
                maxlength="64">
            <div class="hint">Will be stored in UPPER CASE.</div>
            @error('code')<div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="form-group">
            <label for="type">Discount Type *</label>
            <select id="type" name="type" required>
                <option value="">— Select type —</option>
                <option value="percent" {{ old('type') === 'percent' ? 'selected' : '' }}>Percentage (%)</option>
                <option value="fixed" {{ old('type') === 'fixed' ? 'selected' : '' }}>Fixed Amount ($)</option>
            </select>
            @error('type')<div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="form-group">
            <label for="value">Value *</label>
            <input type="number" id="value" name="value" value="{{ old('value') }}" step="0.01" min="0.01" required>
            <div class="hint">For % type enter a number like 20 (= 20%). For fixed enter the dollar amount.</div>
            @error('value')<div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="form-group">
            <label for="min_order_amount">Minimum Order Amount (optional)</label>
            <input type="number" id="min_order_amount" name="min_order_amount" value="{{ old('min_order_amount') }}"
                step="0.01" min="0">
            @error('min_order_amount')<div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="form-group">
            <label for="usage_limit">Usage Limit (optional)</label>
            <input type="number" id="usage_limit" name="usage_limit" value="{{ old('usage_limit') }}" step="1" min="1">
            <div class="hint">Leave blank for unlimited uses.</div>
            @error('usage_limit')<div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="form-group">
            <label for="expires_at">Expiry Date (optional)</label>
            <input type="date" id="expires_at" name="expires_at" value="{{ old('expires_at') }}">
            @error('expires_at')<div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="form-group">
            <div class="check-row">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', '1') == '1' ? 'checked' : '' }}>
                <label for="is_active" style="display:inline;font-weight:normal;">Active</label>
            </div>
            @error('is_active')<div class="error">{{ $message }}</div>@enderror
        </div>

        <button type="submit">Create Coupon</button>
        <a href="{{ route('admin.coupons.index') }}" style="margin-left:1rem;">Cancel</a>
    </form>
</body>

</html>