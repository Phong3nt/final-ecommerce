<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Coupon;
use App\Models\CouponTemplate;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CouponTemplateService
{
    /**
     * Generate templates by case without hardcoding single static templates.
     * Returns the created template count.
     */
    public function generateTemplates(array $data): int
    {
        $case = $data['generation_case'];

        return DB::transaction(function () use ($data, $case): int {
            if ($case === 'new_user') {
                return $this->generateNewUserTemplates($data);
            }

            if ($case === 'seasons') {
                return $this->generateSeasonTemplates($data);
            }

            return $this->generateCategoryTemplates($data);
        });
    }

    public function assignToEligibleUsers(CouponTemplate $template): int
    {
        $users = $this->eligibleUsers($template);
        $createdCount = 0;

        foreach ($users as $user) {
            if ($this->assignToUser($template, $user)) {
                $createdCount++;
            }
        }

        return $createdCount;
    }

    public function assignNewUserTemplates(User $user): int
    {
        $templates = CouponTemplate::query()
            ->where('scope', 'new_user')
            ->where('is_active', true)
            ->get();

        $createdCount = 0;
        foreach ($templates as $template) {
            if ($this->assignToUser($template, $user)) {
                $createdCount++;
            }
        }

        return $createdCount;
    }

    public function assignToUser(CouponTemplate $template, User $user): bool
    {
        if (!$template->is_active) {
            return false;
        }

        if ($template->quantity_limit !== null && $template->quantity_issued >= $template->quantity_limit) {
            return false;
        }

        $exists = Coupon::query()
            ->where('coupon_template_id', $template->id)
            ->where('user_id', $user->id)
            ->exists();

        if ($exists) {
            return false;
        }

        $assignedAt = now();
        $context = $this->buildTemplateContext($template, $user);

        Coupon::create([
            'coupon_template_id' => $template->id,
            'user_id' => $user->id,
            'code' => $this->generateCouponCode($template, $user),
            'name' => $template->renderName($context),
            'description' => $template->renderDescription($context),
            'type' => $template->type,
            'value' => $template->value,
            'usage_limit' => $template->uses_per_user,
            'min_order_amount' => $template->min_order_amount,
            'expires_at' => $this->resolveExpiry($template, $assignedAt),
            'is_active' => true,
            'times_used' => 0,
            'assigned_at' => $assignedAt,
        ]);

        $template->increment('quantity_issued');

        return true;
    }

    private function eligibleUsers(CouponTemplate $template): Collection
    {
        if ($template->scope === 'new_user') {
            return User::query()
                ->where('is_active', true)
                ->doesntHave('orders')
                ->get();
        }

        if ($template->scope === 'category' && $template->category_id !== null) {
            return User::query()
                ->where('is_active', true)
                ->whereHas('orders.items.product', function ($query) use ($template) {
                    $query->where('category_id', $template->category_id);
                })
                ->get();
        }

        return User::query()->where('is_active', true)->get();
    }

    private function resolveExpiry(CouponTemplate $template, Carbon $assignedAt): ?Carbon
    {
        if ($template->expiry_mode === 'duration_days' && $template->expiry_days !== null) {
            return $assignedAt->copy()->addDays($template->expiry_days);
        }

        if ($template->expiry_mode === 'fixed_date') {
            return $template->fixed_expires_at;
        }

        return null;
    }

    private function buildTemplateContext(CouponTemplate $template, User $user): array
    {
        $categoryName = $template->category?->name ?? '';

        return [
            '{user_name}' => $user->name,
            '{year}' => (string) ($template->season_year ?? now()->year),
            '{season}' => $template->season ? Str::title($template->season) : '',
            '{category}' => $categoryName,
            '{discount}' => $template->type === 'percent'
                ? ((string) (float) $template->value) . '%'
                : '$' . number_format((float) $template->value, 2),
        ];
    }

    private function generateCouponCode(CouponTemplate $template, User $user): string
    {
        $prefix = strtoupper($template->code_prefix ?: 'TPL');
        $base = preg_replace('/[^A-Z0-9]/', '', $prefix) ?: 'TPL';

        do {
            $code = sprintf('%s-U%d-%s', $base, $user->id, strtoupper(Str::random(6)));
        } while (Coupon::query()->where('code', $code)->exists());

        return $code;
    }

    private function generateNewUserTemplates(array $data): int
    {
        $templates = [
            [
                'name_template' => $data['new_user_percent_name_template'],
                'description_template' => $data['new_user_percent_description_template'] ?? null,
                'scope' => 'new_user',
                'type' => 'percent',
                'value' => $data['new_user_percent_value'],
                'code_prefix' => strtoupper($data['new_user_percent_code_prefix'] ?? 'WELCOMEPCT'),
            ],
            [
                'name_template' => $data['new_user_fixed_name_template'],
                'description_template' => $data['new_user_fixed_description_template'] ?? null,
                'scope' => 'new_user',
                'type' => 'fixed',
                'value' => $data['new_user_fixed_value'],
                'code_prefix' => strtoupper($data['new_user_fixed_code_prefix'] ?? 'WELCOMEFIX'),
            ],
        ];

        foreach ($templates as $templateData) {
            $template = CouponTemplate::create(array_merge($templateData, [
                'uses_per_user' => $data['uses_per_user'] ?? null,
                'expiry_mode' => $data['expiry_mode'],
                'expiry_days' => $data['expiry_mode'] === 'duration_days' ? $data['expiry_days'] : null,
                'fixed_expires_at' => $data['expiry_mode'] === 'fixed_date' ? $data['fixed_expires_at'] : null,
                'quantity_limit' => $data['quantity_limit'] ?? null,
                'min_order_amount' => $data['min_order_amount'] ?? 1,
                'is_active' => !empty($data['activate_now']),
            ]));

            if (!empty($data['activate_now'])) {
                $this->assignToEligibleUsers($template);
            }
        }

        return 2;
    }

    private function generateSeasonTemplates(array $data): int
    {
        $seasons = $data['season_list'] ?? ['summer', 'autumn'];
        $year = (int) $data['season_year'];
        $created = 0;

        foreach ($seasons as $season) {
            $template = CouponTemplate::create([
                'name_template' => str_replace(['{season}', '{year}'], [Str::title($season), (string) $year], $data['season_name_template']),
                'description_template' => str_replace(['{season}', '{year}'], [Str::title($season), (string) $year], $data['season_description_template'] ?? ''),
                'scope' => 'seasonal',
                'season' => $season,
                'season_year' => $year,
                'type' => $data['season_discount_type'],
                'value' => $data['season_discount_value'],
                'uses_per_user' => $data['uses_per_user'] ?? null,
                'expiry_mode' => $data['expiry_mode'],
                'expiry_days' => $data['expiry_mode'] === 'duration_days' ? $data['expiry_days'] : null,
                'fixed_expires_at' => $data['expiry_mode'] === 'fixed_date' ? $data['fixed_expires_at'] : null,
                'quantity_limit' => $data['quantity_limit'] ?? null,
                'min_order_amount' => $data['min_order_amount'] ?? 1,
                'is_active' => !empty($data['activate_now']),
                'code_prefix' => strtoupper(($data['season_code_prefix'] ?? 'SEASON') . $year . strtoupper(substr($season, 0, 2))),
            ]);

            if (!empty($data['activate_now'])) {
                $this->assignToEligibleUsers($template);
            }

            $created++;
        }

        return $created;
    }

    private function generateCategoryTemplates(array $data): int
    {
        $categories = Category::query()
            ->when(!empty($data['category_ids']), fn($q) => $q->whereIn('id', $data['category_ids']))
            ->get();

        if (!empty($data['category_limit'])) {
            $categories = $categories->take((int) $data['category_limit']);
        }

        $created = 0;

        foreach ($categories as $category) {
            $template = CouponTemplate::create([
                'name_template' => str_replace('{category}', $category->name, $data['category_name_template']),
                'description_template' => str_replace('{category}', $category->name, $data['category_description_template'] ?? ''),
                'scope' => 'category',
                'category_id' => $category->id,
                'type' => $data['category_discount_type'],
                'value' => $data['category_discount_value'],
                'uses_per_user' => $data['uses_per_user'] ?? null,
                'expiry_mode' => $data['expiry_mode'],
                'expiry_days' => $data['expiry_mode'] === 'duration_days' ? $data['expiry_days'] : null,
                'fixed_expires_at' => $data['expiry_mode'] === 'fixed_date' ? $data['fixed_expires_at'] : null,
                'quantity_limit' => $data['quantity_limit'] ?? null,
                'min_order_amount' => $data['min_order_amount'] ?? 1,
                'is_active' => !empty($data['activate_now']),
                'code_prefix' => strtoupper(($data['category_code_prefix'] ?? 'CAT') . $category->id),
            ]);

            if (!empty($data['activate_now'])) {
                $this->assignToEligibleUsers($template);
            }

            $created++;
        }

        return $created;
    }
}
