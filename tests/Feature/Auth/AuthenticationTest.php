<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_supplier_is_redirected_to_supplier_dashboard_after_login(): void
    {
        $supplier = User::factory()->create(['role_type' => 'supplier']);

        $response = $this->post('/login', [
            'email' => $supplier->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $this->assertSame('supplier', auth()->user()->role_type);
        $response->assertRedirect('/supplier/dashboard');
    }

    public function test_seller_is_redirected_to_seller_dashboard_after_login(): void
    {
        $seller = User::factory()->create(['role_type' => 'seller']);

        $response = $this->post('/login', [
            'email' => $seller->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $this->assertSame('seller', auth()->user()->role_type);
        $response->assertRedirect('/seller/dashboard');
    }

    public function test_customer_is_redirected_to_customer_dashboard_after_login(): void
    {
        $customer = User::factory()->create(['role_type' => 'customer']);

        $response = $this->post('/login', [
            'email' => $customer->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $this->assertSame('customer', auth()->user()->role_type);
        $response->assertRedirect('/dashboard');
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
