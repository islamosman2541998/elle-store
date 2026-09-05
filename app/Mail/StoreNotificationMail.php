<?php

namespace App\Mail;

use App\Models\StoreSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StoreNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public StoreSetting $storeSettings;

    public function __construct(
        public string $subjectLine,
        public string $bodyText,
        public ?string $actionUrl = null,
        public ?string $actionLabel = null,
    ) {
        $this->storeSettings = StoreSetting::current();
    }

    public function envelope(): Envelope
    {
        // A reachable Reply-To makes the message look like real
        // correspondence rather than a one-way blast, which is one of the
        // signals inbox providers weigh.
        $replyTo = $this->storeSettings->admin_notification_email
            ?: $this->storeSettings->email;

        return new Envelope(
            subject: $this->subjectLine,
            replyTo: $replyTo ? [$replyTo] : [],
        );
    }

    public function content(): Content
    {
        // The plain-text part is not decoration: an HTML-only message is a
        // long-standing spam signal, and Gmail in particular scores it.
        return new Content(
            view: 'emails.store-notification',
            text: 'emails.store-notification-text',
            with: [
                'storeSettings' => $this->storeSettings,
                'subjectLine' => $this->subjectLine,
                'bodyText' => $this->bodyText,
                'actionUrl' => $this->actionUrl,
                'actionLabel' => $this->actionLabel,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}