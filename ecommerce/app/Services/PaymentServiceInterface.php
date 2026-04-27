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

    /**
     * Cancel a PaymentIntent (e.g. when the order is cancelled before capture).
     *
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function cancelPaymentIntent(string $intentId): void;

    /**
     * Issue a refund for a PaymentIntent and return the Stripe refund ID.
     *
     * @param  string $intentId    The Stripe PaymentIntent ID to refund
     * @param  int    $amountCents Amount to refund in the smallest currency unit (e.g. cents)
     * @return string              The Stripe refund ID (re_...)
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function refund(string $intentId, int $amountCents): string;

    // =========================================================================
    // IMP-035: Card Vault — SetupIntent / saved payment method helpers
    // =========================================================================

    /**
     * Find or create a Stripe Customer for the given email/name.
     * Returns the Stripe customer ID (cus_...).
     */
    public function createOrRetrieveCustomer(string $email, string $name): string;

    /**
     * Create a Stripe SetupIntent for saving a card (attached to a Customer).
     *
     * @return array{id: string, client_secret: string}
     */
    public function createSetupIntent(string $stripeCustomerId): array;

    /**
     * Detach a PaymentMethod from its Stripe Customer so it can no longer be used.
     */
    public function detachPaymentMethod(string $paymentMethodId): void;

    /**
     * Retrieve card details for a PaymentMethod from Stripe.
     *
     * @return array{id: string, last4: string, brand: string, exp_month: int, exp_year: int}
     */
    public function retrievePaymentMethod(string $paymentMethodId): array;

    /**
     * Return the payment_method ID attached to a PaymentIntent after it succeeds.
     * Used in showSuccess() to save a card when the user checked "Save this card?".
     */
    public function getPaymentIntentPaymentMethodId(string $intentId): ?string;
}
