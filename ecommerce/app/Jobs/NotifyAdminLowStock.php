<?php

namespace App\Jobs;

use App\Models\AdminNotification;
use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotifyAdminLowStock implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly Product $product)
    {
    }

    public function handle(): void
    {
        AdminNotification::create([
            'order_id' => null,
            'message'  => "Low stock alert: \"{$this->product->name}\" has only {$this->product->stock} unit(s) remaining (threshold: {$this->product->low_stock_threshold}).",
        ]);
    }
}
