<?php

namespace App\Http\Controllers;

use App\Models\SavedPaymentMethod;
use App\Services\PaymentServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * IMP-035: Card Vault — saved payment methods for authenticated users.
 *
 * Routes:
 *   GET  /profile/payment-methods/setup-intent  → setupIntent()
 *   POST /profile/payment-methods               → store()
 *   PATCH /profile/payment-methods/{pm}/default → setDefault()
 *   DELETE /profile/payment-methods/{pm}        → destroy()
 */
class PaymentMethodController extends Controller
{
    public function __construct(private PaymentServiceInterface $paymentService)
    {
    }

    /**
     * Return a Stripe SetupIntent client_secret so the frontend can
     * collect and confirm a new card via Stripe.js.
     * Creates a Stripe Customer for the user on first call.
     */
    public function setupIntent(): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        if (!$user->stripe_customer_id) {
            $customerId = $this->paymentService->createOrRetrieveCustomer(
                $user->email,
                $user->name,
            );
            $user->update(['stripe_customer_id' => $customerId]);
        }

        $intent = $this->paymentService->createSetupIntent($user->stripe_customer_id);

        return response()->json(['client_secret' => $intent['client_secret']]);
    }

    /**
     * Save a confirmed Stripe PaymentMethod to the database.
     * The payment_method_id must originate from a completed SetupIntent
     * (Stripe.js confirmSetup) — never trust client-supplied card data.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'payment_method_id' => ['required', 'string', 'regex:/^pm_[a-zA-Z0-9_]+$/'],
        ]);

        /** @var \App\Models\User $user */
        $user = auth()->user();
        $pmId = $request->input('payment_method_id');

        // Prevent saving the same card twice
        if ($user->savedPaymentMethods()->where('stripe_payment_method_id', $pmId)->exists()) {
            return redirect()->route('profile.show')
                ->with('info', 'This card is already saved to your account.');
        }

        // Retrieve card details from Stripe — never trust client-supplied last4/brand
        $pm = $this->paymentService->retrievePaymentMethod($pmId);

        // First card automatically becomes the default
        $isFirstCard = $user->savedPaymentMethods()->count() === 0;

        $user->savedPaymentMethods()->create([
            'stripe_payment_method_id' => $pm['id'],
            'last4'                    => $pm['last4'],
            'brand'                    => $pm['brand'],
            'exp_month'                => $pm['exp_month'],
            'exp_year'                 => $pm['exp_year'],
            'is_default'               => $isFirstCard,
        ]);

        return redirect()->route('profile.show')
            ->with('success', 'Card saved successfully.');
    }

    /**
     * Set a saved payment method as the checkout default.
     * Unsets is_default on all other cards for this user first.
     */
    public function setDefault(SavedPaymentMethod $pm): RedirectResponse
    {
        if ($pm->user_id !== auth()->id()) {
            abort(403);
        }

        /** @var \App\Models\User $authUser */
        $authUser = auth()->user();
        $authUser->savedPaymentMethods()->update(['is_default' => false]);
        $pm->update(['is_default' => true]);

        return redirect()->route('profile.show')
            ->with('success', 'Default card updated.');
    }

    /**
     * Detach a saved card from Stripe and remove it from the database.
     * If the deleted card was the default, promotes the next card to default.
     */
    public function destroy(SavedPaymentMethod $pm): RedirectResponse
    {
        if ($pm->user_id !== auth()->id()) {
            abort(403);
        }

        $this->paymentService->detachPaymentMethod($pm->stripe_payment_method_id);

        $wasDefault = $pm->is_default;
        $pm->delete();

        // Promote the next remaining card to default
        if ($wasDefault) {
            /** @var \App\Models\User $authUser */
            $authUser = auth()->user();
            $next = $authUser->savedPaymentMethods()->first();
            $next?->update(['is_default' => true]);
        }

        return redirect()->route('profile.show')
            ->with('success', 'Card removed from your account.');
    }
}
