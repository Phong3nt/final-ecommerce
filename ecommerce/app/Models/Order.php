<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'guest_email',
        'status',
        'subtotal',
        'shipping_cost',
        'total',
        'shipping_method',
        'shipping_label',
        'coupon_code',
        'discount_amount',
        'address',
        'stripe_payment_intent_id',
        'stripe_client_secret',
        'processing_at',
        'shipped_at',
        'delivered_at',
        'cancelled_at',
        'refunded_at',
    ];

    protected $casts = [
        'address' => 'array',
        'subtotal' => 'float',
        'shipping_cost' => 'float',
        'discount_amount' => 'float',
        'total' => 'float',
        'processing_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function refundTransactions(): HasMany
    {
        return $this->hasMany(RefundTransaction::class);
    }
}
