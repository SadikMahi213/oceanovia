<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MailConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_mailer_is_derived_from_environment(): void
    {
        $this->assertSame(env('MAIL_MAILER'), config('mail.default'));
    }

    public function test_resend_mailer_is_registered(): void
    {
        $this->assertSame('resend', config('mail.mailers.resend.transport'));
    }

    public function test_resend_api_key_comes_from_environment_only(): void
    {
        $this->assertSame(env('RESEND_API_KEY'), config('services.resend.key'));
    }

    public function test_from_address_and_name_come_from_environment(): void
    {
        $this->assertSame(env('MAIL_FROM_ADDRESS'), config('mail.from.address'));
        $this->assertSame(env('MAIL_FROM_NAME', config('app.name')), config('mail.from.name'));
    }

    public function test_api_key_is_not_leaked_to_the_admin_email_sender_page(): void
    {
        $admin = User::factory()->create(['role_type' => 'admin', 'email_verified_at' => now()]);

        $response = $this->actingAs($admin)->get('/admin/emails');

        $response->assertOk();
        $response->assertDontSee(config('services.resend.key'));

        // No frontend script source should reference the Resend API key.
        $this->assertFalse(str_contains($response->getContent(), 'RESEND_API_KEY'));
    }
}
