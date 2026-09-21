<?php

namespace App\Jobs;

use App\Mail\AdminEmail;
use App\Models\EmailLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SendAdminEmail implements ShouldQueue
{
    use Dispatchable, Queueable;

    /**
     * Number of attempts before the job is given up.
     */
    public $tries = 3;

    /**
     * Seconds to wait before retrying a failed attempt.
     */
    public $backoff = [10, 60];

    public function __construct(
        public EmailLog $emailLog,
    ) {}

    public function handle(): void
    {
        $log = $this->emailLog;

        // Idempotency guard: never re-send an email already recorded as sent.
        if ($log->status === 'sent') {
            return;
        }

        try {
            $mailable = new AdminEmail(
                $log->subject,
                $log->message,
                $log->body_type,
                $log->recipient_name,
            );

            $sent = Mail::to($log->recipient_email)->send($mailable);

            $messageId = null;
            if ($sent && method_exists($sent, 'getMessageId')) {
                $headers = $sent->getOriginalMessage()->getHeaders();
                $messageId = $headers->has('X-Resend-Email-ID')
                    ? $headers->get('X-Resend-Email-ID')->getBody()
                    : $sent->getMessageId();
            }

            EmailLog::where('id', $log->id)->update([
                'status' => 'sent',
                'provider_message_id' => $messageId,
                'sent_at' => now(),
                'error_message' => null,
            ]);
        } catch (\Throwable $e) {
            // Record the failure accurately; never expose credentials.
            EmailLog::where('id', $log->id)->update([
                'status' => 'failed',
                'error_message' => Str::limit($e->getMessage(), 1000),
            ]);

            Log::error('Admin email delivery failed', [
                'email_log_id' => $log->id,
                'recipient' => $log->recipient_email,
                'exception' => $e,
            ]);

            throw $e;
        }
    }
}
