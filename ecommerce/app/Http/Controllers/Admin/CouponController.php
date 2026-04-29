<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\CouponTemplate;
use App\Services\CouponTemplateService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CouponController extends Controller
{
    public function index(): View
    {
        $coupons = Coupon::orderByDesc('created_at')->paginate(20);
        $templates = CouponTemplate::with('category')->orderByDesc('created_at')->paginate(20, ['*'], 'templates_page');
        $categories = Category::orderBy('name')->get(['id', 'name']);

        return view('admin.coupons.index', compact('coupons', 'templates', 'categories'));
    }

    public function create(): View
    {
        return view('admin.coupons.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:64', 'unique:coupons,code'],
            'name' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'type' => ['required', Rule::in(['percent', 'fixed'])],
            'value' => ['required', 'numeric', 'min:0.01'],
            'expires_at' => ['nullable', 'date'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'min_order_amount' => ['nullable', 'numeric', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        Coupon::create([
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'] ?? null,
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'],
            'value' => $validated['value'],
            'expires_at' => $validated['expires_at'] ?? null,
            'usage_limit' => $validated['usage_limit'] ?? null,
            'min_order_amount' => $validated['min_order_amount'] ?? 1,
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
            'name' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'type' => ['required', Rule::in(['percent', 'fixed'])],
            'value' => ['required', 'numeric', 'min:0.01'],
            'expires_at' => ['nullable', 'date'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'min_order_amount' => ['nullable', 'numeric', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $coupon->update([
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'] ?? null,
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'],
            'value' => $validated['value'],
            'expires_at' => $validated['expires_at'] ?? null,
            'usage_limit' => $validated['usage_limit'] ?? null,
            'min_order_amount' => $validated['min_order_amount'] ?? 1,
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

    public function storeTemplates(Request $request, CouponTemplateService $templateService): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'generation_case' => ['required', Rule::in(['new_user', 'seasons', 'categories'])],

            'uses_per_user' => ['nullable', 'integer', 'min:1'],
            'expiry_preset' => ['required', Rule::in(['week', 'month', 'year', 'fixed_day'])],
            'fixed_expires_at' => ['nullable', 'date', 'required_if:expiry_preset,fixed_day'],
            'quantity_limit' => ['nullable', 'integer', 'min:1'],
            'min_order_amount' => ['nullable', 'numeric', 'min:1'],
            'activate_now' => ['sometimes', 'boolean'],

            'new_user_percent_name_template' => ['nullable', 'string', 'max:120', 'required_if:generation_case,new_user'],
            'new_user_percent_description_template' => ['nullable', 'string', 'max:2000'],
            'new_user_percent_code_prefix' => ['nullable', 'string', 'max:30'],
            'new_user_percent_value' => ['nullable', 'numeric', 'min:0.01', 'required_if:generation_case,new_user'],
            'new_user_fixed_name_template' => ['nullable', 'string', 'max:120', 'required_if:generation_case,new_user'],
            'new_user_fixed_description_template' => ['nullable', 'string', 'max:2000'],
            'new_user_fixed_code_prefix' => ['nullable', 'string', 'max:30'],
            'new_user_fixed_value' => ['nullable', 'numeric', 'min:0.01', 'required_if:generation_case,new_user'],

            'season_year' => ['nullable', 'integer', 'min:2020', 'max:2100', 'required_if:generation_case,seasons'],
            'season_name_template' => ['nullable', 'string', 'max:120', 'required_if:generation_case,seasons'],
            'season_description_template' => ['nullable', 'string', 'max:2000'],
            'season_code_prefix' => ['nullable', 'string', 'max:30'],
            'season_discount_type' => ['nullable', Rule::in(['percent', 'fixed']), 'required_if:generation_case,seasons'],
            'season_discount_value' => ['nullable', 'numeric', 'min:0.01', 'required_if:generation_case,seasons'],

            'category_name_template' => ['nullable', 'string', 'max:120', 'required_if:generation_case,categories'],
            'category_description_template' => ['nullable', 'string', 'max:2000'],
            'category_code_prefix' => ['nullable', 'string', 'max:30'],
            'category_discount_type' => ['nullable', Rule::in(['percent', 'fixed']), 'required_if:generation_case,categories'],
            'category_discount_value' => ['nullable', 'numeric', 'min:0.01', 'required_if:generation_case,categories'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
        ]);

        $this->normalizeExpiryPreset($validated);

        $count = $templateService->generateTemplates($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => "{$count} coupon template(s) generated successfully.",
                'count' => $count,
            ]);
        }

        return redirect()->route('admin.coupons.index')
            ->with('success', "{$count} coupon template(s) generated successfully.");
    }

    public function toggleTemplate(CouponTemplate $template, CouponTemplateService $templateService): RedirectResponse|JsonResponse
    {
        $template->update(['is_active' => !$template->is_active]);

        $assigned = 0;
        if ($template->is_active) {
            $assigned = $templateService->assignToEligibleUsers($template);
        }

        $label = $template->is_active ? 'activated' : 'deactivated';

        if (request()->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => "Template {$label}. {$assigned} coupon(s) assigned.",
                'template_id' => $template->id,
                'is_active' => $template->is_active,
                'assigned' => $assigned,
            ]);
        }

        return redirect()->route('admin.coupons.index')
            ->with('success', "Template {$label}. {$assigned} coupon(s) assigned.");
    }

    public function assignTemplate(CouponTemplate $template, CouponTemplateService $templateService): RedirectResponse|JsonResponse
    {
        if (!$template->is_active) {
            if (request()->expectsJson()) {
                return response()->json(['ok' => false, 'message' => 'Template must be active before assigning to users.'], 422);
            }

            return redirect()->route('admin.coupons.index')
                ->withErrors(['template' => 'Template must be active before assigning to users.']);
        }

        $assigned = $templateService->assignToEligibleUsers($template);

        if (request()->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => "Assigned {$assigned} coupon(s) from template.",
                'template_id' => $template->id,
                'assigned' => $assigned,
            ]);
        }

        return redirect()->route('admin.coupons.index')
            ->with('success', "Assigned {$assigned} coupon(s) from template.");
    }

    public function bulkCoupons(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action' => ['required', Rule::in(['activate', 'deactivate', 'delete'])],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:coupons,id'],
        ]);

        $query = Coupon::query()->whereIn('id', $validated['ids']);
        $count = $query->count();

        if ($validated['action'] === 'activate') {
            $query->update(['is_active' => true]);
        } elseif ($validated['action'] === 'deactivate') {
            $query->update(['is_active' => false]);
        } else {
            $query->delete();
        }

        return response()->json([
            'ok' => true,
            'message' => "{$count} coupon(s) updated.",
            'action' => $validated['action'],
            'ids' => $validated['ids'],
        ]);
    }

    public function bulkTemplates(Request $request, CouponTemplateService $templateService): JsonResponse
    {
        $validated = $request->validate([
            'action' => ['required', Rule::in(['activate', 'deactivate', 'assign'])],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:coupon_templates,id'],
        ]);

        $templates = CouponTemplate::query()->whereIn('id', $validated['ids'])->get();
        $assigned = 0;

        foreach ($templates as $template) {
            if ($validated['action'] === 'activate') {
                if (!$template->is_active) {
                    $template->update(['is_active' => true]);
                }
                $assigned += $templateService->assignToEligibleUsers($template);
                continue;
            }

            if ($validated['action'] === 'deactivate') {
                $template->update(['is_active' => false]);
                continue;
            }

            if ($template->is_active) {
                $assigned += $templateService->assignToEligibleUsers($template);
            }
        }

        return response()->json([
            'ok' => true,
            'message' => $validated['action'] === 'assign'
                ? "Assigned {$assigned} coupon(s) from selected templates."
                : count($validated['ids']) . ' template(s) updated.',
            'action' => $validated['action'],
            'ids' => $validated['ids'],
            'assigned' => $assigned,
        ]);
    }

    public function generatePreset(Request $request, string $preset, CouponTemplateService $templateService): JsonResponse
    {
        if (!in_array($preset, ['new-user-2', 'summer-autumn-2', 'categories-2'], true)) {
            return response()->json(['ok' => false, 'message' => 'Unknown preset.'], 404);
        }

        if ($preset === 'new-user-2') {
            $payload = [
                'generation_case' => 'new_user',
                'uses_per_user' => 2,
                'expiry_mode' => 'duration_days',
                'expiry_days' => 365,
                'quantity_limit' => null,
                'min_order_amount' => 1,
                'activate_now' => true,
                'new_user_percent_name_template' => 'New User {discount} Welcome',
                'new_user_percent_description_template' => 'First-time user gift: {discount} off for your first year.',
                'new_user_percent_code_prefix' => 'NUPCT',
                'new_user_percent_value' => 5,
                'new_user_fixed_name_template' => 'New User {discount} Welcome',
                'new_user_fixed_description_template' => 'First-time user gift: {discount} off for your first year.',
                'new_user_fixed_code_prefix' => 'NUFIX',
                'new_user_fixed_value' => 5,
            ];

            $count = $templateService->generateTemplates($payload);

            return response()->json(['ok' => true, 'message' => 'Generated new-user preset templates.', 'count' => $count]);
        }

        if ($preset === 'summer-autumn-2') {
            $payload = [
                'generation_case' => 'seasons',
                'season_year' => now()->year,
                'season_list' => ['summer', 'autumn'],
                'season_name_template' => '{season} {year} Festival',
                'season_description_template' => 'Save {discount} in {season} {year}.',
                'season_code_prefix' => 'SEA',
                'season_discount_type' => 'percent',
                'season_discount_value' => 12,
                'uses_per_user' => 2,
                'expiry_mode' => 'fixed_date',
                'fixed_expires_at' => Carbon::create(now()->year, 12, 31, 23, 59, 59),
                'quantity_limit' => null,
                'min_order_amount' => 1,
                'activate_now' => true,
            ];

            $count = $templateService->generateTemplates($payload);

            return response()->json(['ok' => true, 'message' => 'Generated summer/autumn preset templates.', 'count' => $count]);
        }

        $categoryIds = Category::query()->orderBy('id')->limit(2)->pluck('id')->all();
        if (count($categoryIds) < 2) {
            return response()->json(['ok' => false, 'message' => 'Need at least 2 categories to generate this preset.'], 422);
        }

        $payload = [
            'generation_case' => 'categories',
            'category_ids' => $categoryIds,
            'category_name_template' => '{category} Saver',
            'category_description_template' => 'Special {category} promotion: {discount}.',
            'category_code_prefix' => 'CAT',
            'category_discount_type' => 'fixed',
            'category_discount_value' => 8,
            'uses_per_user' => 2,
            'expiry_mode' => 'duration_days',
            'expiry_days' => 30,
            'quantity_limit' => null,
            'min_order_amount' => 1,
            'activate_now' => true,
        ];

        $count = $templateService->generateTemplates($payload);

        return response()->json(['ok' => true, 'message' => 'Generated 2-category preset templates.', 'count' => $count]);
    }

    private function normalizeExpiryPreset(array &$validated): void
    {
        $preset = $validated['expiry_preset'];
        $validated['expiry_mode'] = 'duration_days';
        $validated['expiry_days'] = 7;
        $validated['fixed_expires_at'] = $validated['fixed_expires_at'] ?? null;

        if ($preset === 'week') {
            $validated['expiry_days'] = 7;
            return;
        }

        if ($preset === 'month') {
            $validated['expiry_days'] = 30;
            return;
        }

        if ($preset === 'year') {
            $validated['expiry_days'] = 365;
            return;
        }

        $validated['expiry_mode'] = 'fixed_date';
        $validated['expiry_days'] = null;
    }
}
