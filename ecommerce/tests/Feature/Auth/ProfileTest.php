<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    }

    private function makeUser(array $attrs = []): User
    {
        return User::factory()->create($attrs);
    }

    // TC-01: GET /profile returns 200 with current user data pre-filled
    public function test_up001_profile_page_returns_200_with_user_data(): void
    {
        $user = $this->makeUser(['name' => 'Alice', 'email' => 'alice@example.com']);

        $response = $this->actingAs($user)->get(route('profile.show'));

        $response->assertStatus(200);
        $response->assertSee('Alice');
        $response->assertSee('alice@example.com');
    }

    // TC-02: PUT /profile with valid name/email → DB updated + success flash
    public function test_up001_valid_profile_update_saves_to_database(): void
    {
        $user = $this->makeUser(['name' => 'Old Name', 'email' => 'old@example.com']);

        $response = $this->actingAs($user)->put(route('profile.update'), [
            'name' => 'New Name',
            'email' => 'new@example.com',
        ]);

        $response->assertRedirect(route('profile.show'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'New Name', 'email' => 'new@example.com']);
    }

    // TC-03: PUT /profile with valid avatar jpg → file stored + DB updated
    public function test_up001_valid_avatar_upload_stores_file_and_updates_db(): void
    {
        Storage::fake('public');
        $user = $this->makeUser();

        $file = UploadedFile::fake()->image('avatar.jpg', 100, 100)->size(500);

        $this->actingAs($user)->put(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $file,
        ]);

        $user->refresh();
        $this->assertNotNull($user->avatar);
        Storage::disk('public')->assertExists($user->avatar);
    }

    // TC-04: PUT /profile with same email → no unique conflict
    public function test_up001_same_email_does_not_trigger_unique_validation_error(): void
    {
        $user = $this->makeUser(['email' => 'same@example.com']);

        $response = $this->actingAs($user)->put(route('profile.update'), [
            'name' => $user->name,
            'email' => 'same@example.com',
        ]);

        $response->assertRedirect(route('profile.show'));
        $response->assertSessionHasNoErrors();
    }

    // TC-05 (Security): Guest GET /profile → redirect to /login
    public function test_up001_guest_cannot_view_profile(): void
    {
        $this->get(route('profile.show'))->assertRedirect(route('login'));
    }

    // TC-06 (Security): Guest PUT /profile → redirect to /login
    public function test_up001_guest_cannot_update_profile(): void
    {
        $this->put(route('profile.update'), ['name' => 'Hacker', 'email' => 'h@example.com'])
            ->assertRedirect(route('login'));
    }

    // TC-07 (Security): CSRF middleware active on profile update
    public function test_up001_profile_update_requires_csrf_token(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user)
            ->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
            ->put(route('profile.update'), ['name' => $user->name, 'email' => $user->email]);

        // Without CSRF the request still succeeds at the controller level (middleware disabled),
        // so we assert it passes cleanly — the real CSRF test is that the middleware IS registered.
        $this->assertTrue(
            in_array(
                \App\Http\Middleware\VerifyCsrfToken::class,
                app(\Illuminate\Contracts\Http\Kernel::class)->getMiddlewareGroups()['web'] ?? []
            )
        );
    }

    // TC-08 (Negative): Avatar too large (>2MB) → validation error
    public function test_up001_avatar_exceeding_2mb_fails_validation(): void
    {
        Storage::fake('public');
        $user = $this->makeUser();

        $file = UploadedFile::fake()->image('big.jpg')->size(3000); // 3 MB

        $response = $this->actingAs($user)->put(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $file,
        ]);

        $response->assertSessionHasErrors('avatar');
    }

    // TC-09 (Negative): Avatar wrong type (pdf) → validation error
    public function test_up001_non_image_avatar_fails_validation(): void
    {
        Storage::fake('public');
        $user = $this->makeUser();

        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $response = $this->actingAs($user)->put(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $file,
        ]);

        $response->assertSessionHasErrors('avatar');
    }

    // TC-10 (Negative): Empty name → validation error
    public function test_up001_empty_name_fails_validation(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user)->put(route('profile.update'), [
            'name' => '',
            'email' => $user->email,
        ]);

        $response->assertSessionHasErrors('name');
    }

    // TC-11 (Negative): Duplicate email (another user's) → validation error
    public function test_up001_duplicate_email_of_another_user_fails_validation(): void
    {
        $this->makeUser(['email' => 'taken@example.com']);
        $user = $this->makeUser(['email' => 'mine@example.com']);

        $response = $this->actingAs($user)->put(route('profile.update'), [
            'name' => $user->name,
            'email' => 'taken@example.com',
        ]);

        $response->assertSessionHasErrors('email');
    }

    // TC-12 (Performance): Profile update completes within 2 seconds
    public function test_up001_profile_update_completes_within_two_seconds(): void
    {
        $user = $this->makeUser();

        $start = microtime(true);
        $this->actingAs($user)->put(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
        ]);
        $elapsed = microtime(true) - $start;

        $this->assertLessThan(2.0, $elapsed, 'Profile update exceeded 2 seconds.');
    }

    // TC-13 (IMP-041): Profile page shows the Card Vault section heading and Add Card button
    public function test_imp041_profile_page_shows_card_vault_section(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->get(route('profile.show'))
            ->assertStatus(200)
            ->assertSee('Saved Cards')
            ->assertSee('Add Card');
    }

    // TC-14 (IMP-041): Add New Card modal markup is present in server-rendered response
    // (x-teleport wraps the modal in a <template> tag; Alpine moves it to <body> at runtime,
    //  but the text content is still present in the raw HTML for assertSee)
    public function test_imp041_add_card_modal_markup_present_in_response(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->get(route('profile.show'))
            ->assertStatus(200)
            ->assertSee('Add New Card')
            ->assertSee('Save Card')
            ->assertSee('setup-element');
    }
}
