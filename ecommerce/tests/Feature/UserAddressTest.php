<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * UP-002 — As a user, I want to manage my saved addresses so checkout is faster.
 *
 * Acceptance criteria:
 *   - CRUD for multiple addresses
 *   - One address can be set as default
 *   - Default address pre-filled at checkout
 */
class UserAddressTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        return User::factory()->create();
    }

    private function validAddress(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Jane Doe',
            'address_line1' => '123 Main St',
            'address_line2' => null,
            'city' => 'Springfield',
            'state' => 'IL',
            'postal_code' => '62701',
            'country' => 'US',
        ], $overrides);
    }

    // TC-01: Guest is redirected to login when visiting addresses page
    public function test_up002_guest_redirected_to_login(): void
    {
        $this->get(route('addresses.index'))
            ->assertRedirect(route('login'));
    }

    // TC-02: Authenticated user can view their addresses (200)
    public function test_up002_auth_user_sees_addresses_page(): void
    {
        $user = $this->makeUser();
        $address = UserAddress::factory()->create(['user_id' => $user->id, 'name' => 'Home Address']);

        $this->actingAs($user)
            ->get(route('addresses.index'))
            ->assertStatus(200)
            ->assertSee('Home Address');
    }

    // TC-03: User with no addresses sees empty state
    public function test_up002_user_with_no_addresses_sees_empty_state(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->get(route('addresses.index'))
            ->assertStatus(200)
            ->assertSee('no saved addresses', false);
    }

    // TC-04: User can add a new address; it is persisted to DB
    public function test_up002_user_can_add_new_address(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->post(route('addresses.store'), $this->validAddress())
            ->assertRedirect(route('addresses.index'));

        $this->assertDatabaseHas('user_addresses', [
            'user_id' => $user->id,
            'address_line1' => '123 Main St',
            'city' => 'Springfield',
        ]);
    }

    // TC-05: name is required on store
    public function test_up002_name_is_required_on_store(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->post(route('addresses.store'), $this->validAddress(['name' => '']))
            ->assertSessionHasErrors('name');
    }

    // TC-06: User can update their own address
    public function test_up002_user_can_update_own_address(): void
    {
        $user = $this->makeUser();
        $address = UserAddress::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->put(route('addresses.update', $address), $this->validAddress(['city' => 'Portland']))
            ->assertRedirect(route('addresses.index'));

        $this->assertDatabaseHas('user_addresses', [
            'id' => $address->id,
            'city' => 'Portland',
        ]);
    }

    // TC-07: User gets 403 when updating another user's address
    public function test_up002_user_cannot_update_another_users_address(): void
    {
        $owner = $this->makeUser();
        $other = $this->makeUser();
        $address = UserAddress::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($other)
            ->put(route('addresses.update', $address), $this->validAddress())
            ->assertForbidden();
    }

    // TC-08: User can delete their own address
    public function test_up002_user_can_delete_own_address(): void
    {
        $user = $this->makeUser();
        $address = UserAddress::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->delete(route('addresses.destroy', $address))
            ->assertRedirect(route('addresses.index'));

        $this->assertDatabaseMissing('user_addresses', ['id' => $address->id]);
    }

    // TC-09: User gets 403 when deleting another user's address
    public function test_up002_user_cannot_delete_another_users_address(): void
    {
        $owner = $this->makeUser();
        $other = $this->makeUser();
        $address = UserAddress::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($other)
            ->delete(route('addresses.destroy', $address))
            ->assertForbidden();
    }

    // TC-10: User can set an address as default; it is marked is_default=true in DB
    public function test_up002_user_can_set_address_as_default(): void
    {
        $user = $this->makeUser();
        $address = UserAddress::factory()->create(['user_id' => $user->id, 'is_default' => false]);

        $this->actingAs($user)
            ->patch(route('addresses.setDefault', $address))
            ->assertRedirect(route('addresses.index'));

        $this->assertDatabaseHas('user_addresses', [
            'id' => $address->id,
            'is_default' => true,
        ]);
    }

    // TC-11: Setting a new default unsets all other addresses' is_default
    public function test_up002_setting_default_unsets_other_defaults(): void
    {
        $user = $this->makeUser();
        $old = UserAddress::factory()->create(['user_id' => $user->id, 'is_default' => true]);
        $new = UserAddress::factory()->create(['user_id' => $user->id, 'is_default' => false]);

        $this->actingAs($user)
            ->patch(route('addresses.setDefault', $new));

        $this->assertDatabaseHas('user_addresses', ['id' => $old->id, 'is_default' => false]);
        $this->assertDatabaseHas('user_addresses', ['id' => $new->id, 'is_default' => true]);
    }

    // TC-12: Default address is listed first (pre-filled at checkout via is_default ordering)
    public function test_up002_default_address_is_returned_first(): void
    {
        $user = $this->makeUser();
        UserAddress::factory()->create(['user_id' => $user->id, 'is_default' => false, 'name' => 'Work']);
        UserAddress::factory()->create(['user_id' => $user->id, 'is_default' => true, 'name' => 'Home Default']);

        $addresses = $user->addresses()->get();

        $this->assertEquals('Home Default', $addresses->first()->name);
    }
}
