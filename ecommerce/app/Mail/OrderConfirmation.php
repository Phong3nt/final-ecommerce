<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    private static array $estimatedDelivery = [
        'standard' => '5–7 business days',
        'express'  => '1–2 business days',
    ];

    public function __construct(public readonly Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Order Confirmation #' . $this->order->id,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.order-confirmation',
            with: [
                'order'             => $this->order,
                'estimatedDelivery' => self::$estimatedDelivery[$this->order->shipping_method] ?? 'To be confirmed',
            ],
        );
    }
}
