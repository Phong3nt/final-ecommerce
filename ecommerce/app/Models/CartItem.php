<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * IMP-015: Persisted cart item for authenticated users.
 *
 * @property int $id
 * @property int $user_id
 * @property int $product_id
 * @property int $quantity
 */
class CartItem extends Model
{
    protected $fillable = ['user_id', 'product_id', 'quantity'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
