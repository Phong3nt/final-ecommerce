<?php

namespace Tests\Feature;

use App\Models\SavedPaymentMethod;
use App\Models\User;
use App\Services\PaymentServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * IMP-035: Card Vault — saved payment methods tests.
 *
 * All Stripe API calls are mocked via PaymentServiceInterface.
 */
class SavedPaymentMethodTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function mockPaymentService(array $expectations = []): void
    {
        $this->mock(PaymentServiceInterface::class, function ($mock) use ($expectations) {
            foreach ($expectations as $method => $return) {
                $mock->shouldReceive($method)->andReturn($return);
            }
        });
    }

    private function checkoutSession(): array
    {
        return [
            'cart' => [1 => ['name' => 'Widget', 'price' => 20.00, 'quantity' => 1]],
            'checkout.address' => [
                'id' => 1, 'name' => 'Jane Doe',
                'address_line1' => '123 Main St', 'address_line2' => null,
                'city' => 'Springfield', 'state' => 'IL',
                'postal_code' => '62701', 'country' => 'US',
            ],
            'checkout.shipping' => [
                'method' => 'standard', 'label' => 'Standard Shipping', 'cost' => 5.00,
            ],
        ];
    }

    private function savedCard(User $user, bool $isDefault = false, string $pmId = 'pm_test_abc123'): SavedPaymentMethod
    {
        return SavedPaymentMethod::create([
            'user_id'                  => $user->id,
            'stripe_payment_method_id' => $pmId,
            'last4'                    => '4242',
            'brand'                    => 'visa',
            'exp_month'                => 12,
            'exp_year'                 => 2028,
            'is_default'               => $isDefault,
        ]);
    }

    // =========================================================================
    // TC-01: GET setup-intent redirects guests to login
    // =========================================================================

    /** @test */
    public function imp035_guest_redirected_from_setup_intent(): void
    {
        $this->get(route('payment-methods.setup-intent'))
             ->assertRedirect(route('login'));
    }

    // =========================================================================
    // TC-02: Auth user gets setup intent JSON {client_secret}
    // =========================================================================

    /** @test */
    public function imp035_auth_user_gets_setup_intent_client_secret(): void
    {
        $user = User::factory()->create(['stripe_customer_id' => 'cus_existing']);

        $this->mockPaymentService([
            'createSetupIntent' => ['id' => 'seti_test', 'client_secret' => 'seti_secret_test'],
        ]);

        $this->actingAs($user)
             ->getJson(route('payment-methods.setup-intent'))
             ->assertOk()
             ->assertJsonStructure(['client_secret'])
             ->assertJson(['client_secret' => 'seti_secret_test']);
    }

    // =========================================================================
    // TC-03: When user has no Stripe Customer, one is created on setup-intent
    // =========================================================================

    /** @test */
    public function imp035_setup_intent_creates_stripe_customer_if_missing(): void
    {
        $user = User::factory()->create(['stripe_customer_id' => null]);

        $this->mock(PaymentServiceInterface::class, function ($mock) {
            $mock->shouldReceive('createOrRetrieveCustomer')
                 ->once()
                 ->andReturn('cus_new123');
            $mock->shouldReceive('createSetupIntent')
                 ->with('cus_new123')
                 ->andReturn(['id' => 'seti_test', 'client_secret' => 'seti_secret']);
        });

        $this->actingAs($user)
             ->getJson(route('payment-methods.setup-intent'))
             ->assertOk();

        $this->assertDatabaseHas('users', [
            'id'                 => $user->id,
            'stripe_customer_id' => 'cus_new123',
        ]);
    }

    // =========================================================================
    // TC-04: Guest cannot POST to payment-methods.store
    // =========================================================================

    /** @test */
    public function imp035_guest_cannot_store_payment_method(): void
    {
        $this->post(route('payment-methods.store'), ['payment_method_id' => 'pm_test_abc'])
             ->assertRedirect(route('login'));
    }

    // =========================================================================
    // TC-05: Auth user can store a new payment method
    // =========================================================================

    /** @test */
    public function imp035_auth_user_can_store_payment_method(): void
    {
        $user = User::factory()->create();

        $this->mockPaymentService([
            'retrievePaymentMethod' => [
                'id' => 'pm_test_xyz', 'last4' => '4242',
                'brand' => 'visa', 'exp_month' => 12, 'exp_year' => 2028,
            ],
        ]);

        $this->actingAs($user)
             ->post(route('payment-methods.store'), ['payment_method_id' => 'pm_test_xyz'])
             ->assertRedirect(route('profile.show'))
             ->assertSessionHas('success');

        $this->assertDatabaseHas('saved_payment_methods', [
            'user_id'                  => $user->id,
            'stripe_payment_method_id' => 'pm_test_xyz',
            'last4'                    => '4242',
            'brand'                    => 'visa',
            'is_default'               => true, // first card → auto-default
        ]);
    }

    // =========================================================================
    // TC-06: First saved card is automatically set as default
    // =========================================================================

    /** @test */
    public function imp035_first_saved_card_becomes_default(): void
    {
        $user = User::factory()->create();

        $this->mockPaymentService([
            'retrievePaymentMethod' => [
                'id' => 'pm_first', 'last4' => '1111',
                'brand' => 'mastercard', 'exp_month' => 6, 'exp_year' => 2027,
            ],
        ]);

        $this->actingAs($user)
             ->post(route('payment-methods.store'), ['payment_method_id' => 'pm_first']);

        $this->assertDatabaseHas('saved_payment_methods', [
            'stripe_payment_method_id' => 'pm_first',
            'is_default'               => true,
        ]);
    }

    // =========================================================================
    // TC-07: Duplicate PM not saved twice — returns info flash
    // =========================================================================

    /** @test */
    public function imp035_duplicate_pm_not_saved_twice(): void
    {
        $user = User::factory()->create();
        $this->savedCard($user, true, 'pm_dupe');

        $this->actingAs($user)
             ->post(route('payment-methods.store'), ['payment_method_id' => 'pm_dupe'])
             ->assertRedirect(route('profile.show'))
             ->assertSessionHas('info');

        $this->assertDatabaseCount('saved_payment_methods', 1);
    }

    // =========================================================================
    // TC-08: User can set a saved card as default
    // =========================================================================

    /** @test */
    public function imp035_user_can_set_default_card(): void
    {
        $user = User::factory()->create();
        $default  = $this->savedCard($user, true,  'pm_default');
        $secondary = $this->savedCard($user, false, 'pm_secondary');

        $this->actingAs($user)
             ->patch(route('payment-methods.setDefault', $secondary))
             ->assertRedirect(route('profile.show'))
             ->assertSessionHas('success');

        $this->assertDatabaseHas('saved_payment_methods', [
            'id'         => $secondary->id,
            'is_default' => true,
        ]);
        $this->assertDatabaseHas('saved_payment_methods', [
            'id'         => $default->id,
            'is_default' => false,
        ]);
    }

    // =========================================================================
    // TC-09: User cannot set another user's card as default (403)
    // =========================================================================

    /** @test */
    public function imp035_user_cannot_set_another_users_card_as_default(): void
    {
        $owner  = User::factory()->create();
        $other  = User::factory()->create();
        $card = $this->savedCard($owner, true);

        $this->actingAs($other)
             ->patch(route('payment-methods.setDefault', $card))
             ->assertForbidden();
    }

    // =========================================================================
    // TC-10: User can delete their saved card
    // =========================================================================

    /** @test */
    public function imp035_user_can_delete_saved_card(): void
    {
        $user = User::factory()->create();
        $card = $this->savedCard($user, false);

        $this->mockPaymentService([
            'detachPaymentMethod' => null,
        ]);

        $this->actingAs($user)
             ->delete(route('payment-methods.destroy', $card))
             ->assertRedirect(route('profile.show'))
             ->assertSessionHas('success');

        $this->assertDatabaseMissing('saved_payment_methods', ['id' => $card->id]);
    }

    // =========================================================================
    // TC-11: User cannot delete another user's card (403)
    // =========================================================================

    /** @test */
    public function imp035_user_cannot_delete_another_users_card(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $card  = $this->savedCard($owner);

        $this->actingAs($other)
             ->delete(route('payment-methods.destroy', $card))
             ->assertForbidden();
    }

    // =========================================================================
    // TC-12: Deleting default card promotes next card to default
    // =========================================================================

    /** @test */
    public function imp035_deleting_default_card_promotes_next(): void
    {
        $user    = User::factory()->create();
        $default = $this->savedCard($user, true,  'pm_default');
        $next    = $this->savedCard($user, false, 'pm_next');

        $this->mockPaymentService(['detachPaymentMethod' => null]);

        $this->actingAs($user)
             ->delete(route('payment-methods.destroy', $default));

        $this->assertDatabaseHas('saved_payment_methods', [
            'id'         => $next->id,
            'is_default' => true,
        ]);
    }

    // =========================================================================
    // TC-13: Profile page shows saved cards section for auth user
    // =========================================================================

    /** @test */
    public function imp035_profile_page_shows_saved_cards_section(): void
    {
        $user = User::factory()->create();
        $card = $this->savedCard($user, true);

        $this->actingAs($user)
             ->get(route('profile.show'))
             ->assertOk()
             ->assertSee('Saved Cards')
             ->assertSee('4242')
             ->assertSee('Default');
    }

    // =========================================================================
    // TC-14: Checkout review passes savedPaymentMethods to view
    // =========================================================================

    /** @test */
    public function imp035_checkout_review_passes_saved_cards_to_view(): void
    {
        $user = User::factory()->create();
        $card = $this->savedCard($user, true);

        $this->mockPaymentService([
            'createPaymentIntent' => ['id' => 'pi_test', 'client_secret' => 'pi_test_secret'],
        ]);

        $this->actingAs($user)
             ->withSession($this->checkoutSession())
             ->get(route('checkout.review'))
             ->assertOk()
             ->assertViewHas('savedPaymentMethods')
             ->assertSee('4242');
    }

    // =========================================================================
    // TC-15: Invalid pm_id format rejected by store validation
    // =========================================================================

    /** @test */
    public function imp035_store_rejects_invalid_payment_method_id_format(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
             ->post(route('payment-methods.store'), ['payment_method_id' => 'not_a_pm_id'])
             ->assertSessionHasErrors('payment_method_id');
    }
}
