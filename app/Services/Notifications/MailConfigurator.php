<?php

namespace App\Services\Notifications;

use App\Models\StoreSetting;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Points Laravel's mailer at the SMTP details saved in the dashboard.
 *
 * The .env values stay as the fallback, so an install that has not filled the
 * dashboard in keeps working exactly as before.
 */
class MailConfigurator
{
    private bool $applied = false;

    public function apply(): void
    {
        if ($this->applied) {
            return;
        }

        $this->applied = true;

        try {
            $settings = StoreSetting::current();
        } catch (Throwable) {
            // Fresh install, no settings table yet: keep the .env config.
            return;
        }

        if (blank($settings->mail_host) && blank($settings->mail_from_address)) {
            return;
        }

        $mailer = $settings->mail_mailer ?: config('mail.default', 'smtp');

        if (filled($settings->mail_host)) {
            Config::set("mail.mailers.{$mailer}.transport", $mailer === 'log' ? 'log' : 'smtp');
            Config::set("mail.mailers.{$mailer}.host", $settings->mail_host);
            Config::set("mail.mailers.{$mailer}.port", $settings->mail_port ?: 587);
            Config::set("mail.mailers.{$mailer}.username", $settings->mail_username);
            Config::set("mail.mailers.{$mailer}.password", $settings->mail_password);

            // Laravel expects null rather than an empty string for "no TLS".
            Config::set(
                "mail.mailers.{$mailer}.encryption",
                filled($settings->mail_encryption) && $settings->mail_encryption !== 'none'
                    ? $settings->mail_encryption
                    : null
            );
        }

        Config::set('mail.default', $mailer);

        if (filled($settings->mail_from_address)) {
            Config::set('mail.from.address', $settings->mail_from_address);
        }

        if (filled($settings->mail_from_name)) {
            Config::set('mail.from.name', $settings->mail_from_name);
        }

        // Drop any mailer already built from the old config.
        Mail::purge($mailer);
    }

    /** Force the next apply() to run again (used after saving settings). */
    public function reset(): void
    {
        $this->applied = false;
    }

    /** True when there is enough configuration to actually send mail. */
    public function isConfigured(): bool
    {
        $this->apply();

        $mailer = config('mail.default');

        if ($mailer === 'log' || $mailer === 'array') {
            return true;
        }

        return filled(config("mail.mailers.{$mailer}.host"))
            && filled(config('mail.from.address'));
    }
}
