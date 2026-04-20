<?php

namespace App\Jobs;

use App\Mail\NewOrderAdminMail;
use App\Models\AdminNotification;
use App\Models\Order;
use App\Models\User;
use App\Services\FirebaseService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class NotifyAdminOfNewOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly Order $order)
    {
    }

    public function handle(): void
    {
        AdminNotification::create([
            'order_id' => $this->order->id,
            'message' => "New order #{$this->order->id} received.",
        ]);

        // IMP-017: push real-time notification to Firebase RTDB so the admin bell
        // fires on('value') immediately instead of waiting for the 30-second poll.
        $unreadCount = AdminNotification::whereNull('read_at')->count();
        app(FirebaseService::class)->pushAdminNotification($this->order->id, $unreadCount);

        $admins = User::whereHas('roles', fn ($q) => $q->where('name', 'admin'))->get();
        foreach ($admins as $admin) {
            Mail::to($admin->email)->send(new NewOrderAdminMail($this->order));
        }
    }
}
