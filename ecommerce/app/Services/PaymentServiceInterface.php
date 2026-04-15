<?php

namespace App\Services;

interface PaymentServiceInterface
{
    /**
     * Create a payment intent and return its id and client_secret.
     *
     * @param  int    $amountCents  Amount in the smallest currency unit (e.g. cents)
     * @param  string $currency     ISO 4217 currency code (e.g. 'usd')
     * @param  array  $metadata     Key-value metadata to attach to the intent
     * @return array{id: string, client_secret: string}
     */
    public function createPaymentIntent(int $amountCents, string $currency, array $metadata): array;

    /**
     * Verify a Stripe webhook signature and return the decoded event object.
     *
     * @throws \UnexpectedValueException  If the payload is invalid
     * @throws \Stripe\Exception\SignatureVerificationException  If the sig is invalid
     */
    public function constructWebhookEvent(string $payload, string $sigHeader, string $secret): object;
}
