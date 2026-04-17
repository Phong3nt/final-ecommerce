<?php

namespace App\Services;

use Stripe\StripeClient;
use Stripe\Webhook;

class StripePaymentService implements PaymentServiceInterface
{
    private ?StripeClient $stripe = null;

    public function __construct()
    {
        // Lazy: instantiated only when a Stripe method is called
    }

    private function client(): StripeClient
    {
        if ($this->stripe === null) {
            $secret = config('services.stripe.secret');
            if (empty($secret)) {
                throw new \RuntimeException('Stripe secret key is not configured. Set STRIPE_SECRET in .env');
            }
            $this->stripe = new StripeClient($secret);
        }

        return $this->stripe;
    }

    public function createPaymentIntent(int $amountCents, string $currency, array $metadata): array
    {
        $intent = $this->client()->paymentIntents->create([
            'amount' => $amountCents,
            'currency' => $currency,
            'metadata' => $metadata,
            'automatic_payment_methods' => ['enabled' => true],
        ]);

        return [
            'id' => $intent->id,
            'client_secret' => $intent->client_secret,
        ];
    }

    public function constructWebhookEvent(string $payload, string $sigHeader, string $secret): object
    {
        return Webhook::constructEvent($payload, $sigHeader, $secret);
    }

    public function cancelPaymentIntent(string $intentId): void
    {
        $this->client()->paymentIntents->cancel($intentId);
    }

    public function refund(string $intentId, int $amountCents): string
    {
        $refund = $this->client()->refunds->create([
            'payment_intent' => $intentId,
            'amount' => $amountCents,
        ]);

        return $refund->id;
    }
}
