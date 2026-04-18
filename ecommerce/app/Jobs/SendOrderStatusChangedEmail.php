<?php

namespace App\Jobs;

use App\Mail\OrderStatusChanged;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendOrderStatusChangedEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly Order $order)
    {
    }

    public function handle(): void
    {
        $this->order->loadMissing(['user', 'items']);

        Mail::to($this->order->user->email)->send(new OrderStatusChanged($this->order));
    }
}
