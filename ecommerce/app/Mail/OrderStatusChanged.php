<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderStatusChanged extends Mailable
{
    use Queueable, SerializesModels;

    private static array $statusLabels = [
        'processing' => 'Being Processed',
        'shipped' => 'Shipped',
        'delivered' => 'Delivered',
    ];

    public function __construct(public readonly Order $order)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Order #' . $this->order->id . ' Status Update',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.order-status-changed',
            with: [
                'order' => $this->order,
                'statusLabel' => self::$statusLabels[$this->order->status] ?? ucfirst($this->order->status),
            ],
        );
    }
}
