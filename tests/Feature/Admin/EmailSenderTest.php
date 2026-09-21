<?php

namespace Tests\Feature\Admin;

use App\Jobs\SendAdminEmail;
use App\Mail\AdminEmail;
use App\Models\EmailLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class EmailSenderTest extends TestCase
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

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/emails')->assertRedirect(route('login'));
    }

    public function test_non_admin_cannot_access_email_sender(): void
    {
        $this->actingAs($this->makeNonAdmin())
            ->get('/admin/emails')
            ->assertForbidden();

        $this->actingAs($this->makeNonAdmin())
            ->post('/admin/emails', [
                'recipients' => 'customer@example.com',
                'subject' => 'Hello',
                'message' => 'Body',
                'body_type' => 'text',
            ])
            ->assertForbidden();
    }

    public function test_admin_can_view_email_sender(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get('/admin/emails')
            ->assertOk()
            ->assertSee('Email Sender')
            ->assertSee('Oceanovia');
    }

    public function test_recipients_are_required(): void
    {
        $this->actingAs($this->makeAdmin())
            ->from('/admin/emails')
            ->post('/admin/emails', [
                'recipients' => '',
                'subject' => 'Hello',
                'message' => 'Body',
                'body_type' => 'text',
            ])
            ->assertSessionHasErrors('recipients');
    }

    public function test_invalid_single_recipient_is_rejected(): void
    {
        $this->actingAs($this->makeAdmin())
            ->from('/admin/emails')
            ->post('/admin/emails', [
                'recipients' => 'not-an-email',
                'subject' => 'Hello',
                'message' => 'Body',
                'body_type' => 'text',
            ])
            ->assertSessionHasErrors('recipients');

        $this->assertDatabaseCount('email_logs', 0);
    }

    public function test_malformed_recipient_with_embedded_newline_is_rejected(): void
    {
        $this->actingAs($this->makeAdmin())
            ->from('/admin/emails')
            ->post('/admin/emails', [
                'recipients' => "user@example.com\r\nBcc: victim@example.com",
                'subject' => 'Hello',
                'message' => 'Body',
                'body_type' => 'text',
            ])
            ->assertSessionHasErrors('recipients');

        $this->assertDatabaseCount('email_logs', 0);
    }

    public function test_empty_subject_is_rejected(): void
    {
        $this->actingAs($this->makeAdmin())
            ->from('/admin/emails')
            ->post('/admin/emails', [
                'recipients' => 'customer@example.com',
                'subject' => '',
                'message' => 'Body',
                'body_type' => 'text',
            ])
            ->assertSessionHasErrors('subject');
    }

    public function test_empty_message_is_rejected(): void
    {
        $this->actingAs($this->makeAdmin())
            ->from('/admin/emails')
            ->post('/admin/emails', [
                'recipients' => 'customer@example.com',
                'subject' => 'Hello',
                'message' => '',
                'body_type' => 'text',
            ])
            ->assertSessionHasErrors('message');
    }

    public function test_invalid_body_type_is_rejected(): void
    {
        $this->actingAs($this->makeAdmin())
            ->from('/admin/emails')
            ->post('/admin/emails', [
                'recipients' => 'customer@example.com',
                'subject' => 'Hello',
                'message' => 'Body',
                'body_type' => 'markdown',
            ])
            ->assertSessionHasErrors('body_type');
    }

    public function test_single_recipient_flow_sends_and_is_logged(): void
    {
        Mail::fake();

        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->from('/admin/emails')
            ->post('/admin/emails', [
                'recipients' => 'customer@example.com',
                'subject' => 'Welcome deal',
                'message' => "Hi there\n\n20% off everything.",
                'body_type' => 'text',
            ])
            ->assertRedirect(route('admin.emails.index'));

        $this->assertDatabaseHas('email_logs', [
            'recipient_email' => 'customer@example.com',
            'subject' => 'Welcome deal',
            'body_type' => 'text',
            'status' => 'sent',
        ]);

        Mail::assertSent(AdminEmail::class, function (AdminEmail $mail) {
            return $mail->subject === 'Welcome deal'
                && $mail->body === "Hi there\n\n20% off everything."
                && $mail->hasTo('customer@example.com')
                && $mail->hasFrom(config('mail.from.address'));
        });

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'email.sent',
        ]);
    }

    public function test_single_recipient_flow_queues_a_job(): void
    {
        Queue::fake();
        Mail::fake();

        $this->actingAs($this->makeAdmin())
            ->post('/admin/emails', [
                'recipients' => 'customer@example.com',
                'subject' => 'Subject',
                'message' => 'Body',
                'body_type' => 'text',
            ]);

        Queue::assertPushed(SendAdminEmail::class);
        $this->assertDatabaseHas('email_logs', [
            'recipient_email' => 'customer@example.com',
            'status' => 'requested',
        ]);
    }

    public function test_multiple_recipients_are_supported_and_deduplicated(): void
    {
        Mail::fake();

        $this->actingAs($this->makeAdmin())
            ->post('/admin/emails', [
                'recipients' => "one@example.com,\ntwo@example.com one@example.com; two@example.com",
                'subject' => 'Bulk',
                'message' => 'Hello all',
                'body_type' => 'text',
            ])
            ->assertRedirect(route('admin.emails.index'));

        // 2 unique recipients => 2 rows, 2 mailable records.
        $this->assertDatabaseCount('email_logs', 2);
        $this->assertDatabaseHas('email_logs', ['recipient_email' => 'one@example.com', 'status' => 'sent']);
        $this->assertDatabaseHas('email_logs', ['recipient_email' => 'two@example.com', 'status' => 'sent']);
        Mail::assertSentCount(2);
    }

    public function test_multiple_recipients_use_one_batch_and_queue_per_recipient(): void
    {
        Queue::fake();
        Mail::fake();

        $this->actingAs($this->makeAdmin())
            ->post('/admin/emails', [
                'recipients' => 'a@example.com, b@example.com',
                'subject' => 'Bulk',
                'message' => 'Hello all',
                'body_type' => 'text',
            ]);

        Queue::assertPushed(SendAdminEmail::class, 2);

        $batchIds = EmailLog::query()->whereIn('recipient_email', ['a@example.com', 'b@example.com'])->pluck('batch_id');
        $this->assertCount(1, $batchIds->unique());
    }

    public function test_html_body_type_is_supported(): void
    {
        Mail::fake();

        $this->actingAs($this->makeAdmin())
            ->post('/admin/emails', [
                'recipients' => 'customer@example.com',
                'subject' => 'HTML mail',
                'message' => '<p>Hello <strong>there</strong></p>',
                'body_type' => 'html',
            ]);

        $this->assertDatabaseHas('email_logs', [
            'recipient_email' => 'customer@example.com',
            'body_type' => 'html',
            'status' => 'sent',
        ]);

        Mail::assertSent(AdminEmail::class, function (AdminEmail $mail) {
            return $mail->bodyType === 'html';
        });
    }

    public function test_history_page_shows_logs(): void
    {
        EmailLog::create([
            'user_id' => $this->makeAdmin()->id,
            'recipient_email' => 'customer@example.com',
            'subject' => 'Old mail',
            'message' => 'Body',
            'body_type' => 'text',
            'status' => 'failed',
            'error_message' => 'Delivery rejected by provider',
        ]);

        $this->actingAs($this->makeAdmin())
            ->get('/admin/emails')
            ->assertOk()
            ->assertSee('customer@example.com')
            ->assertSee('Old mail')
            ->assertSee('Failed')
            ->assertSee('Delivery rejected by provider');
    }
}
