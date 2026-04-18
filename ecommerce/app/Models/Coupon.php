<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'type', 'value', 'expires_at', 'is_active', 'usage_limit', 'min_order_amount', 'times_used'];

    protected $casts = [
        'value' => 'float',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'usage_limit' => 'integer',
        'min_order_amount' => 'float',
        'times_used' => 'integer',
    ];

    /**
     * Returns true when the coupon can be applied:
     * must be active and not past its expiry date.
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }
}
