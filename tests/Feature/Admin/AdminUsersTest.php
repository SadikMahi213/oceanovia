<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
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

    public function test_admin_can_delete_a_user(): void
    {
        $admin = $this->makeAdmin();
        $target = $this->makeRegisteredUser();

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $target))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success', 'User deleted successfully.');

        $this->assertSoftDeleted('users', ['id' => $target->id]);
    }

    public function test_non_admin_cannot_delete_a_user(): void
    {
        $target = $this->makeRegisteredUser();

        $this->actingAs($this->makeNonAdmin())
            ->delete(route('admin.users.destroy', $target))
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $target->id, 'deleted_at' => null]);
    }

    public function test_guest_cannot_delete_a_user(): void
    {
        $target = $this->makeRegisteredUser();

        $this->delete(route('admin.users.destroy', $target))
            ->assertRedirect(route('login'));

        $this->assertDatabaseHas('users', ['id' => $target->id, 'deleted_at' => null]);
    }

    public function test_admin_cannot_delete_own_account(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $admin))
            ->assertRedirect()
            ->assertSessionHas('error', 'You cannot delete your own account.');

        $this->assertNotSoftDeleted('users', ['id' => $admin->id]);
    }

    public function test_deleted_user_disappears_from_admin_list(): void
    {
        $admin = $this->makeAdmin();
        $target = $this->makeRegisteredUser();

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $target))
            ->assertRedirect(route('admin.users.index'));

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertDontSee('jane@example.com');
    }

    public function test_deleting_a_user_preserves_their_financial_history(): void
    {
        $admin = $this->makeAdmin();
        $user = $this->makeRegisteredUser();

        $order = Order::create([
            'user_id' => $user->id,
            'subtotal' => 100,
            'shipping_cost' => 10,
            'tax' => 5,
            'discount' => 0,
            'total' => 115,
            'status' => 'confirmed',
            'payment_method' => 'card',
            'payment_status' => 'paid',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $user))
            ->assertRedirect(route('admin.users.index'));

        $this->assertSoftDeleted('users', ['id' => $user->id]);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'user_id' => $user->id]);
    }

    public function test_backend_failure_returns_error_flash_and_keeps_user(): void
    {
        $admin = $this->makeAdmin();
        $target = $this->makeRegisteredUser();

        User::deleting(function () {
            throw new \RuntimeException('simulated delete failure');
        });

        try {
            $this->actingAs($admin)
                ->delete(route('admin.users.destroy', $target))
                ->assertRedirect()
                ->assertSessionHas('error', 'Could not delete the user. No changes were made.');

            $this->assertDatabaseHas('users', ['id' => $target->id, 'deleted_at' => null]);
        } finally {
            User::flushEventListeners();
        }
    }
}
