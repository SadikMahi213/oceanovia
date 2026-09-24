<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\UserNotification;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminAccountControlsTest extends TestCase
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

    private function makeUser(string $role = 'customer', string $status = 'active'): User
    {
        return User::factory()->create([
            'role_type' => $role,
            'status' => $status,
            'email_verified_at' => now(),
        ]);
    }

    private function makeNotificationFor(User $user): UserNotification
    {
        return UserNotification::create([
            'id' => (string) Str::uuid(),
            'type' => 'new_user',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => ['name' => 'Jane', 'email' => 'jane@example.com', 'role_type' => 'customer'],
            'title' => 'New user registered: Jane',
            'icon' => 'user',
            'link' => route('admin.users.show', ['user' => 1]),
        ]);
    }

    // ─── Verification ─────────────────────────────────────────────────────

    public function test_admin_can_verify_an_unverified_user(): void
    {
        $admin = $this->makeAdmin();
        $target = $this->makeUser();
        $target->forceFill(['email_verified_at' => null])->save();

        $this->actingAs($admin)
            ->patch(route('admin.users.verify', $target))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertNotNull($target->fresh()->email_verified_at);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.verified',
            'resource_type' => User::class,
            'resource_id' => $target->id,
        ]);
    }

    public function test_admin_verifying_an_already_verified_user_is_idempotent(): void
    {
        $admin = $this->makeAdmin();
        $target = $this->makeUser();

        $this->actingAs($admin)
            ->patch(route('admin.users.verify', $target))
            ->assertRedirect()
            ->assertSessionHas('info');

        $this->assertNotNull($target->fresh()->email_verified_at);
    }

    public function test_non_admin_cannot_verify_users(): void
    {
        $target = $this->makeUser();
        $target->forceFill(['email_verified_at' => null])->save();

        $this->actingAs($this->makeNonAdmin())
            ->patch(route('admin.users.verify', $target))
            ->assertForbidden();

        $this->assertNull($target->fresh()->email_verified_at);
    }

    public function test_guest_cannot_verify_users(): void
    {
        $target = $this->makeUser();
        $target->forceFill(['email_verified_at' => null])->save();

        $this->patch(route('admin.users.verify', $target))
            ->assertRedirect(route('login'));

        $this->assertNull($target->fresh()->email_verified_at);
    }

    // ─── Status changes ───────────────────────────────────────────────────

    public function test_admin_can_suspend_an_active_user(): void
    {
        $admin = $this->makeAdmin();
        $target = $this->makeUser();

        $this->actingAs($admin)
            ->patch(route('admin.users.update-status', $target), ['status' => 'suspended', 'reason' => 'Violation'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', ['id' => $target->id, 'status' => 'suspended']);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.status.suspended',
            'resource_type' => User::class,
            'resource_id' => $target->id,
        ]);
    }

    public function test_admin_can_deactivate_an_active_user(): void
    {
        $admin = $this->makeAdmin();
        $target = $this->makeUser();

        $this->actingAs($admin)
            ->patch(route('admin.users.update-status', $target), ['status' => 'inactive', 'reason' => null])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', ['id' => $target->id, 'status' => 'inactive']);
    }

    public function test_admin_can_reactivate_a_suspended_or_inactive_user(): void
    {
        $admin = $this->makeAdmin();
        $suspended = $this->makeUser(status: 'suspended');
        $inactive = $this->makeUser(status: 'inactive');

        $this->actingAs($admin)
            ->patch(route('admin.users.update-status', $suspended), ['status' => 'active'])
            ->assertSessionHas('success');

        $this->actingAs($admin)
            ->patch(route('admin.users.update-status', $inactive), ['status' => 'active'])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', ['id' => $suspended->id, 'status' => 'active']);
        $this->assertDatabaseHas('users', ['id' => $inactive->id, 'status' => 'active']);
    }

    public function test_admin_cannot_change_own_status(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->patch(route('admin.users.update-status', $admin), ['status' => 'suspended'])
            ->assertRedirect()
            ->assertSessionHas('error', 'You cannot change the status of your own account.');

        $this->assertDatabaseHas('users', ['id' => $admin->id, 'status' => 'active']);
    }

    public function test_status_update_rejects_invalid_status(): void
    {
        $admin = $this->makeAdmin();
        $target = $this->makeUser();

        $this->actingAs($admin)
            ->patch(route('admin.users.update-status', $target), ['status' => 'banned'])
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('users', ['id' => $target->id, 'status' => 'active']);
    }

    public function test_non_admin_cannot_change_user_status(): void
    {
        $target = $this->makeUser();

        $this->actingAs($this->makeNonAdmin())
            ->patch(route('admin.users.update-status', $target), ['status' => 'suspended'])
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $target->id, 'status' => 'active']);
    }

    public function test_inline_edit_cannot_change_own_status_or_role(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->post(route('admin.inline.save'), ['model' => 'User', 'id' => $admin->id, 'field' => 'status', 'value' => 'suspended'])
            ->assertStatus(422);

        $this->actingAs($admin)
            ->post(route('admin.inline.save'), ['model' => 'User', 'id' => $admin->id, 'field' => 'role_type', 'value' => 'customer'])
            ->assertStatus(422);

        $this->assertDatabaseHas('users', ['id' => $admin->id, 'status' => 'active', 'role_type' => 'admin']);
    }

    // ─── Server-side enforcement (EnsureAccountStatus) ────────────────────

    public function test_suspended_customer_is_logged_out_of_protected_routes(): void
    {
        $user = $this->makeUser('customer', 'suspended');

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => 'Your account has been suspended. Please contact support.']);

        $this->assertGuest();
    }

    public function test_inactive_customer_is_logged_out_of_protected_routes(): void
    {
        $user = $this->makeUser('customer', 'inactive');

        $this->actingAs($user)
            ->get('/account/dashboard')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_suspended_seller_cannot_access_seller_dashboard(): void
    {
        $user = $this->makeUser('seller', 'suspended');

        $this->actingAs($user)
            ->get('/seller/dashboard')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_suspended_supplier_cannot_access_supplier_dashboard(): void
    {
        $user = $this->makeUser('supplier', 'suspended');

        $this->actingAs($user)
            ->get('/supplier/dashboard')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_reactivated_user_regains_access(): void
    {
        $admin = $this->makeAdmin();
        $user = $this->makeUser('customer', 'suspended');

        $this->actingAs($user->fresh())
            ->get('/dashboard')
            ->assertRedirect(route('login'));

        $this->actingAs($admin)
            ->patch(route('admin.users.update-status', $user), ['status' => 'active'])
            ->assertSessionHas('success');

        $this->assertSame('active', $user->fresh()->status);

        $this->actingAs($user->fresh())
            ->get('/dashboard')
            ->assertOk();
    }

    // ─── Admin notifications (new-user alerts) ────────────────────────────

    public function test_new_registration_notifies_active_admins_in_app(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::where('email', 'admin@oceanovia.com')->firstOrFail();
        UserNotification::query()->delete();

        $this->post('/register', [
            'name' => 'New Person',
            'email' => 'newperson@example.com',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
            'role_type' => 'customer',
        ])->assertSessionHasNoErrors();

        $newUser = User::where('email', 'newperson@example.com')->firstOrFail();

        $this->assertDatabaseHas('user_notifications', [
            'type' => 'new_user',
            'notifiable_type' => User::class,
            'notifiable_id' => $admin->id,
        ]);

        $notification = UserNotification::first();

        $this->assertNotNull($notification);
        $this->assertStringContainsString('New user registered: New Person', $notification->title);
        $this->assertStringContainsString((string) $newUser->id, $notification->link);
        $this->assertSame('newperson@example.com', data_get($notification->data, 'email'));
    }

    public function test_admin_notifications_page_shows_alert_with_user_link(): void
    {
        $admin = $this->makeAdmin();
        $target = $this->makeUser();
        $notification = UserNotification::create([
            'id' => (string) Str::uuid(),
            'type' => 'new_user',
            'notifiable_type' => User::class,
            'notifiable_id' => $admin->id,
            'data' => ['name' => $target->name, 'email' => $target->email, 'role_type' => $target->role_type],
            'title' => 'New user registered: '.$target->name,
            'icon' => 'user',
            'link' => route('admin.users.show', $target),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.notifications.index'))
            ->assertOk()
            ->assertSee('New user registered: '.$target->name)
            ->assertSee(route('admin.users.show', $target));
    }

    public function test_admin_can_mark_a_notification_as_read(): void
    {
        $admin = $this->makeAdmin();
        $notification = $this->makeNotificationFor($admin);

        $this->assertNull($notification->read_at);

        $this->actingAs($admin)
            ->post(route('admin.notifications.read', $notification))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_admin_can_mark_all_notifications_as_read(): void
    {
        $admin = $this->makeAdmin();
        $this->makeNotificationFor($admin);
        $this->makeNotificationFor($admin);

        $this->actingAs($admin)
            ->post(route('admin.notifications.read-all'))
            ->assertRedirect();

        $this->assertDatabaseMissing('user_notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $admin->id,
            'read_at' => null,
        ]);
    }

    public function test_admin_cannot_mark_another_admins_notification_as_read(): void
    {
        $adminA = $this->makeAdmin();
        $adminB = $this->makeAdmin();
        $notification = $this->makeNotificationFor($adminB);

        $this->actingAs($adminA)
            ->post(route('admin.notifications.read', $notification))
            ->assertForbidden();

        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_admin_dashboard_shows_recent_notifications(): void
    {
        $admin = $this->makeAdmin();
        $notification = $this->makeNotificationFor($admin);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee($notification->title);
    }

    public function test_notification_bell_is_visible_to_admins_only(): void
    {
        $admin = $this->makeAdmin();
        $notification = $this->makeNotificationFor($admin);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Notifications')
            ->assertSee('Mark all as read');
    }

    public function test_notification_bell_is_hidden_from_non_admin_users(): void
    {
        $customer = $this->makeNonAdmin();

        $this->actingAs($customer)
            ->get($customer->getDashboardRoute())
            ->assertOk()
            ->assertDontSee('Mark all as read');
    }
}
