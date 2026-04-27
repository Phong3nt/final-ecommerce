<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutAddressTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function validAddressData(array $overrides = []): array
    {
        return array_merge([
            'name'          => 'Jane Doe',
            'address_line1' => '123 Main St',
            'address_line2' => null,
            'city'          => 'Springfield',
            'state'         => 'IL',
            'postal_code'   => '62701',
            'country'       => 'US',
        ], $overrides);
    }

    // -------------------------------------------------------------------------
    // TC-01: GET returns 200 for authenticated user
    // -------------------------------------------------------------------------

    /** @test */
    public function cp001_address_page_returns_200_for_auth_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
             ->withSession(['cart' => [1 => ['name' => 'Product', 'price' => 10.00, 'quantity' => 1]]])
             ->get(route('checkout.address'))
             ->assertOk();
    }

    // -------------------------------------------------------------------------
    // TC-02: Guest is redirected to login
    // -------------------------------------------------------------------------

    /** @test */
    public function cp001_guest_is_redirected_to_login(): void
    {
        $this->get(route('checkout.address'))
             ->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // TC-03: Auth user sees their saved addresses on the page
    // -------------------------------------------------------------------------

    /** @test */
    public function cp001_auth_user_sees_saved_addresses(): void
    {
        $user    = User::factory()->create();
        $address = UserAddress::factory()->create([
            'user_id' => $user->id,
            'name'    => 'John Smith',
        ]);

        $this->actingAs($user)
             ->withSession(['cart' => [1 => ['name' => 'Product', 'price' => 10.00, 'quantity' => 1]]])
             ->get(route('checkout.address'))
             ->assertOk()
             ->assertSee('John Smith')
             ->assertSee($address->address_line1);
    }

    // -------------------------------------------------------------------------
    // TC-04: User with no saved addresses sees only the new address form
    // -------------------------------------------------------------------------

    /** @test */
    public function cp001_user_with_no_addresses_sees_new_address_form(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
             ->withSession(['cart' => [1 => ['name' => 'Product', 'price' => 10.00, 'quantity' => 1]]])
             ->get(route('checkout.address'))
             ->assertOk()
             ->assertSee('new-address-form', false) // class name in the HTML
             ->assertDontSee('saved-addresses');
    }

    // -------------------------------------------------------------------------
    // TC-05: Valid new address is stored in checkout session
    // -------------------------------------------------------------------------

    /** @test */
    public function cp001_valid_address_stored_in_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
             ->post(route('checkout.address.store'), $this->validAddressData())
             ->assertRedirect(route('checkout.shipping'));

        $this->assertEquals(
            '123 Main St',
            session('checkout.address.address_line1')
        );
    }

    // -------------------------------------------------------------------------
    // TC-06: New address is persisted to user_addresses table
    // -------------------------------------------------------------------------

    /** @test */
    public function cp001_new_address_saved_to_database(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
             ->post(route('checkout.address.store'), $this->validAddressData());

        $this->assertDatabaseHas('user_addresses', [
            'user_id'       => $user->id,
            'address_line1' => '123 Main St',
            'city'          => 'Springfield',
        ]);
    }

    // -------------------------------------------------------------------------
    // TC-07: Selecting a saved address stores it in session
    // -------------------------------------------------------------------------

    /** @test */
    public function cp001_selecting_saved_address_stores_it_in_session(): void
    {
        $user    = User::factory()->create();
        $address = UserAddress::factory()->create([
            'user_id'       => $user->id,
            'address_line1' => '456 Oak Ave',
            'city'          => 'Portland',
        ]);

        $this->actingAs($user)
             ->post(route('checkout.address.store'), ['address_id' => $address->id])
             ->assertRedirect(route('checkout.shipping'));

        $this->assertEquals('456 Oak Ave', session('checkout.address.address_line1'));
        $this->assertEquals($address->id, session('checkout.address.id'));
    }

    // -------------------------------------------------------------------------
    // TC-08: name field required
    // -------------------------------------------------------------------------

    /** @test */
    public function cp001_name_is_required(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
             ->post(route('checkout.address.store'), $this->validAddressData(['name' => '']))
             ->assertSessionHasErrors('name');
    }

    // -------------------------------------------------------------------------
    // TC-09: address_line1 required
    // -------------------------------------------------------------------------

    /** @test */
    public function cp001_address_line1_is_required(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
             ->post(route('checkout.address.store'), $this->validAddressData(['address_line1' => '']))
             ->assertSessionHasErrors('address_line1');
    }

    // -------------------------------------------------------------------------
    // TC-10: city required
    // -------------------------------------------------------------------------

    /** @test */
    public function cp001_city_is_required(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
             ->post(route('checkout.address.store'), $this->validAddressData(['city' => '']))
             ->assertSessionHasErrors('city');
    }

    // -------------------------------------------------------------------------
    // TC-11: postal_code required
    // -------------------------------------------------------------------------

    /** @test */
    public function cp001_postal_code_is_required(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
             ->post(route('checkout.address.store'), $this->validAddressData(['postal_code' => '']))
             ->assertSessionHasErrors('postal_code');
    }

    // -------------------------------------------------------------------------
    // TC-12: country required
    // -------------------------------------------------------------------------

    /** @test */
    public function cp001_country_is_required(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
             ->post(route('checkout.address.store'), $this->validAddressData(['country' => '']))
             ->assertSessionHasErrors('country');
    }
}
