<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CouponTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_template',
        'description_template',
        'scope',
        'category_id',
        'season',
        'season_year',
        'type',
        'value',
        'uses_per_user',
        'expiry_mode',
        'expiry_days',
        'fixed_expires_at',
        'quantity_limit',
        'quantity_issued',
        'min_order_amount',
        'is_active',
        'code_prefix',
    ];

    protected $casts = [
        'value' => 'float',
        'uses_per_user' => 'integer',
        'expiry_days' => 'integer',
        'fixed_expires_at' => 'datetime',
        'quantity_limit' => 'integer',
        'quantity_issued' => 'integer',
        'min_order_amount' => 'float',
        'is_active' => 'boolean',
        'season_year' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function coupons(): HasMany
    {
        return $this->hasMany(Coupon::class, 'coupon_template_id');
    }

    public function renderName(array $context = []): string
    {
        return (string) str($this->name_template)->replace(array_keys($context), array_values($context));
    }

    public function renderDescription(array $context = []): ?string
    {
        if ($this->description_template === null) {
            return null;
        }

        return (string) str($this->description_template)->replace(array_keys($context), array_values($context));
    }
}
