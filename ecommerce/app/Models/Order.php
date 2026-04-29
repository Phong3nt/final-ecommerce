<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int                             $id
 * @property int|null                        $user_id
 * @property string|null                     $guest_email
 * @property string                          $status
 * @property float                           $subtotal
 * @property float                           $shipping_cost
 * @property float                           $total
 * @property string|null                     $shipping_method
 * @property string|null                     $shipping_label
 * @property string|null                     $coupon_code
 * @property float                           $discount_amount
 * @property array|null                      $address
 * @property string|null                     $stripe_payment_intent_id
 * @property string|null                     $stripe_client_secret
 * @property bool                            $is_demo
 * @property string|null                     $ship_sim_status
 * @property \Carbon\Carbon|null             $ship_sim_started_at
 * @property \Carbon\Carbon|null             $ship_sim_updated_at
 * @property \Carbon\Carbon|null             $processing_at
 * @property \Carbon\Carbon|null             $shipped_at
 * @property \Carbon\Carbon|null             $delivered_at
 * @property \Carbon\Carbon|null             $cancelled_at
 * @property \Carbon\Carbon|null             $refunded_at
 * @property \Carbon\Carbon                  $created_at
 * @property \Carbon\Carbon                  $updated_at
 */
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
        'is_demo',
        'ship_sim_status',
        'ship_sim_started_at',
        'ship_sim_updated_at',
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
        'is_demo' => 'boolean',
        'ship_sim_started_at' => 'datetime',
        'ship_sim_updated_at' => 'datetime',
    ];

    public function scopeReal(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_demo', false);
    }

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
