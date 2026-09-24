<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
            'role_type' => 'customer',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('verification.notice', absolute: false));
    }

    public function test_registration_ignores_stale_intended_url(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        session(['url.intended' => '/admin/users/999']);

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'intended@example.com',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
            'role_type' => 'customer',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('verification.notice', absolute: false));
    }

    public function test_registered_user_is_unverified(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'unverified@example.com',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
            'role_type' => 'customer',
        ]);

        $user = User::where('email', 'unverified@example.com')->first();

        $this->assertNotNull($user);
        $this->assertNull($user->email_verified_at);
        $this->assertFalse($user->hasVerifiedEmail());
    }

    public function test_verification_email_is_sent_upon_registration(): void
    {
        Notification::fake();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'verify@example.com',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
            'role_type' => 'customer',
        ]);

        $user = User::where('email', 'verify@example.com')->first();

        Notification::assertSentTo($user, VerifyEmail::class);
        $this->assertFalse($user->hasVerifiedEmail());
    }

    public function test_duplicate_active_email_is_rejected(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->post('/register', [
            'name' => 'First User',
            'email' => 'taken@example.com',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
            'role_type' => 'customer',
        ]);

        $this->post('/logout');

        $this->post('/register', [
            'name' => 'Second User',
            'email' => 'taken@example.com',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
            'role_type' => 'customer',
        ])->assertSessionHasErrors('email');
    }

    public function test_deleted_user_email_can_be_reused_for_new_registration(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $oldUser = User::factory()->create(['email' => 'reuse@example.com']);
        $oldId = $oldUser->id;

        $oldUser->delete();
        $this->assertSoftDeleted('users', ['id' => $oldId]);

        $response = $this->post('/register', [
            'name' => 'New User',
            'email' => 'reuse@example.com',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
            'role_type' => 'customer',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertAuthenticated();
        $response->assertRedirect(route('verification.notice', absolute: false));

        $newUser = User::withTrashed()->where('email', 'reuse@example.com')
            ->where('id', '!=', $oldId)->first();

        $this->assertNotNull($newUser);
        $this->assertNull($newUser->deleted_at);
        $this->assertNull($newUser->email_verified_at);
        $this->assertNotSame($oldId, $newUser->id);
    }

    public function test_new_account_created_with_reused_email_is_independent(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $oldUser = User::factory()->create(['email' => 'fresh@example.com']);
        $oldUser->delete();

        $this->post('/register', [
            'name' => 'Fresh User',
            'email' => 'fresh@example.com',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
            'role_type' => 'customer',
        ]);

        $newUser = User::where('email', 'fresh@example.com')->firstOrFail();

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('seller_balances', 0);
        $this->assertDatabaseCount('wishlists', 0);
        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_deleted_user_cannot_authenticate_anymore(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create([
            'email' => 'gone@example.com',
            'password' => 'Password@123',
            'email_verified_at' => now(),
        ]);
        $user->delete();

        $this->assertFalse(Auth::attempt([
            'email' => 'gone@example.com',
            'password' => 'Password@123',
        ]));
        $this->assertGuest();
    }
}
