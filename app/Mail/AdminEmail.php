<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminEmail extends Mailable
{
    use SerializesModels;

    /**
     * Create a new message instance.
     *
     * The parent Mailable already declares $subject, so the subject is kept
     * in its own property and copied over in the constructor.
     */
    public function __construct(
        public string $mailSubject,
        public string $body,
        public string $bodyType = 'text',
        public ?string $recipientName = null,
    ) {
        $this->subject = $mailSubject;
    }

    public function envelope(): Envelope
    {
        // From comes from configuration (config/mail.php) so the sender
        // identity can never be spoofed by request input.
        return new Envelope(
            subject: $this->mailSubject,
            from: new Address(
                (string) config('mail.from.address'),
                (string) config('mail.from.name'),
            ),
        );
    }

    public function content(): Content
    {
        return new Content(
            with: [
                'subject' => $this->mailSubject,
            ],
            view: $this->bodyType === 'html' ? 'emails.admin-email-html' : null,
            text: $this->bodyType === 'html' ? null : 'emails.admin-email',
        );
    }
}
