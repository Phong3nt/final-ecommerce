<?php

namespace App\Services;

use Stripe\StripeClient;
use Stripe\Webhook;

class StripePaymentService implements PaymentServiceInterface
{
    private StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }

    public function createPaymentIntent(int $amountCents, string $currency, array $metadata): array
    {
        $intent = $this->stripe->paymentIntents->create([
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
}
