<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RefundTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'amount',
        'stripe_refund_id',
    ];

    protected $casts = [
        'amount' => 'float',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
