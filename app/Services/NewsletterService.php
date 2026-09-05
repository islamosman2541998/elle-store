<?php

namespace App\Services;

use App\Models\NewsletterSubscriber;
use Illuminate\Validation\ValidationException;

class NewsletterService
{
    public function subscribe(string $email, ?string $name = null, string $source = 'footer'): NewsletterSubscriber
    {
        $email = strtolower(trim($email));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages([
                'email' => __('admin.invalid_email_address'),
            ]);
        }

        $subscriber = NewsletterSubscriber::withTrashed()
            ->where('email', $email)
            ->first();

        if ($subscriber) {
            if ($subscriber->trashed()) {
                $subscriber->restore();
            }

            $subscriber->update([
                'name' => $name ?: $subscriber->name,
                'status' => 'subscribed',
                'subscribed_at' => now(),
                'unsubscribed_at' => null,
                'source' => $source,
            ]);

            return $subscriber->fresh();
        }

        return NewsletterSubscriber::create([
            'email' => $email,
            'name' => $name,
            'status' => 'subscribed',
            'subscribed_at' => now(),
            'source' => $source,
        ]);
    }

    public function unsubscribe(string $email): ?NewsletterSubscriber
    {
        $email = strtolower(trim($email));

        $subscriber = NewsletterSubscriber::query()
            ->where('email', $email)
            ->first();

        if (! $subscriber) {
            return null;
        }

        $subscriber->unsubscribe();

        return $subscriber->fresh();
    }
}