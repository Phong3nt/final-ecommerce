<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CouponController extends Controller
{
    public function index(): View
    {
        $coupons = Coupon::orderByDesc('created_at')->paginate(20);
        return view('admin.coupons.index', compact('coupons'));
    }

    public function create(): View
    {
        return view('admin.coupons.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:64', 'unique:coupons,code'],
            'type' => ['required', Rule::in(['percent', 'fixed'])],
            'value' => ['required', 'numeric', 'min:0.01'],
            'expires_at' => ['nullable', 'date'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        Coupon::create([
            'code' => strtoupper($validated['code']),
            'type' => $validated['type'],
            'value' => $validated['value'],
            'expires_at' => $validated['expires_at'] ?? null,
            'usage_limit' => $validated['usage_limit'] ?? null,
            'min_order_amount' => $validated['min_order_amount'] ?? null,
            'is_active' => isset($validated['is_active']) ? (bool) $validated['is_active'] : true,
        ]);

        return redirect()->route('admin.coupons.index')
            ->with('success', 'Coupon created successfully.');
    }

    public function edit(Coupon $coupon): View
    {
        return view('admin.coupons.edit', compact('coupon'));
    }

    public function update(Request $request, Coupon $coupon): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:64', Rule::unique('coupons', 'code')->ignore($coupon->id)],
            'type' => ['required', Rule::in(['percent', 'fixed'])],
            'value' => ['required', 'numeric', 'min:0.01'],
            'expires_at' => ['nullable', 'date'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $coupon->update([
            'code' => strtoupper($validated['code']),
            'type' => $validated['type'],
            'value' => $validated['value'],
            'expires_at' => $validated['expires_at'] ?? null,
            'usage_limit' => $validated['usage_limit'] ?? null,
            'min_order_amount' => $validated['min_order_amount'] ?? null,
            'is_active' => isset($validated['is_active']) ? (bool) $validated['is_active'] : false,
        ]);

        return redirect()->route('admin.coupons.index')
            ->with('success', 'Coupon updated successfully.');
    }

    public function destroy(Coupon $coupon): RedirectResponse
    {
        $coupon->delete();

        return redirect()->route('admin.coupons.index')
            ->with('success', 'Coupon deleted successfully.');
    }

    public function toggle(Coupon $coupon): RedirectResponse
    {
        $coupon->update(['is_active' => !$coupon->is_active]);

        $label = $coupon->is_active ? 'activated' : 'deactivated';

        return redirect()->route('admin.coupons.index')
            ->with('success', "Coupon {$label} successfully.");
    }
}
