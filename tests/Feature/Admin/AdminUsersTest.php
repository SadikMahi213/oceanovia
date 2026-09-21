<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUsersTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): User
    {
        return User::factory()->create([
            'role_type' => 'admin',
            'email_verified_at' => now(),
        ]);
    }

    private function makeNonAdmin(): User
    {
        return User::factory()->create([
            'role_type' => 'customer',
            'email_verified_at' => now(),
        ]);
    }

    private function makeRegisteredUser(): User
    {
        return User::factory()->create([
            'name' => 'Jane',
            'lastname' => 'Doe',
            'username' => 'janedoe',
            'email' => 'jane@example.com',
            'phone' => '+1 555 010-9999',
            'role_type' => 'customer',
            'status' => 'active',
            'date_of_birth' => '1990-05-20',
            'gender' => 'female',
            'country' => 'United States',
            'city' => 'Austin',
            'state' => 'TX',
            'postal_code' => '73301',
            'last_login_at' => now(),
            'last_login_ip' => '192.168.1.10',
            'notification_preferences' => ['order_updates', 'promotions'],
        ]);
    }

    public function test_guest_is_redirected_to_login_for_index_and_show(): void
    {
        $user = $this->makeRegisteredUser();

        $this->get('/admin/users')->assertRedirect(route('login'));
        $this->get("/admin/users/{$user->id}")->assertRedirect(route('login'));
    }

    public function test_non_admin_cannot_access_admin_users(): void
    {
        $user = $this->makeRegisteredUser();

        $this->actingAs($this->makeNonAdmin())
            ->get('/admin/users')
            ->assertForbidden();

        $this->actingAs($this->makeNonAdmin())
            ->get("/admin/users/{$user->id}")
            ->assertForbidden();
    }

    public function test_admin_sees_full_registration_information_in_user_list(): void
    {
        $this->makeRegisteredUser();

        $response = $this->actingAs($this->makeAdmin())
            ->get('/admin/users')
            ->assertOk();

        $response->assertSee('Jane Doe');
        $response->assertSee('janedoe');
        $response->assertSee('jane@example.com');
        $response->assertSee('+1 555 010-9999');
    }

    public function test_admin_sees_complete_user_information_on_details_page(): void
    {
        $user = $this->makeRegisteredUser();

        $response = $this->actingAs($this->makeAdmin())
            ->get("/admin/users/{$user->id}")
            ->assertOk();

        $response->assertSee('#'.$user->id);
        $response->assertSee('Jane Doe');
        $response->assertSee('jane@example.com');
        $response->assertSee('+1 555 010-9999');
        $response->assertSee('United States');
        $response->assertSee('Austin');
        $response->assertSee('TX');
        $response->assertSee('73301');
        $response->assertSee('female');
        $response->assertSee('May 20, 1990');
        $response->assertSee('192.168.1.10');
        $response->assertSee('Order Updates, Promotions');
    }

    public function test_admin_users_show_returns_404_for_unknown_user(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get('/admin/users/99999')
            ->assertNotFound();
    }
}
