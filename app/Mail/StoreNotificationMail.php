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
        return new Envelope(
            subject: $this->subjectLine,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.store-notification',
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