<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * IMP-035: Saved Payment Method (Card Vault)
 *
 * @property int    $id
 * @property int    $user_id
 * @property string $stripe_payment_method_id
 * @property string $last4
 * @property string $brand
 * @property int    $exp_month
 * @property int    $exp_year
 * @property bool   $is_default
 */
class SavedPaymentMethod extends Model
{
    protected $fillable = [
        'user_id',
        'stripe_payment_method_id',
        'last4',
        'brand',
        'exp_month',
        'exp_year',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'exp_month'  => 'integer',
        'exp_year'   => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Human-readable label, e.g. "Visa •••• 4242"
     */
    public function getDisplayLabelAttribute(): string
    {
        return ucfirst($this->brand) . ' •••• ' . $this->last4;
    }

    /**
     * Expiry string, e.g. "04/28"
     */
    public function getExpiryAttribute(): string
    {
        return str_pad((string) $this->exp_month, 2, '0', STR_PAD_LEFT)
            . '/'
            . substr((string) $this->exp_year, -2);
    }
}
