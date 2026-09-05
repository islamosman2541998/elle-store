<?php

namespace App\Services;

use App\Models\FooterLink;
use App\Models\FooterSetting;
use App\Models\SocialLink;
use App\Models\PaymentMethodDisplay;

class FooterService
{
    public function settings(): FooterSetting
    {
        return FooterSetting::current();
    }

    public function isActive(): bool
    {
        return (bool) $this->settings()->is_active;
    }

    public function socialLinks()
    {
        if (! $this->isActive() || ! $this->settings()->show_social_links) {
            return collect();
        }

        return SocialLink::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    public function links()
    {
        if (! $this->isActive()) {
            return collect();
        }

        return FooterLink::query()
            ->with('page')
            ->where('is_active', true)
            ->orderBy('group')
            ->orderBy('sort_order')
            ->get();
    }

    public function groupedLinks()
    {
        return $this->links()->groupBy('group');
    }

    public function footerData(): array
    {
        $settings = $this->settings();

        return [
            'settings' => $settings,
            'social_links' => $this->socialLinks(),
            'payment_methods' => $this->paymentMethods(),
            'links' => $this->links(),
            'grouped_links' => $this->groupedLinks(),

            'show_social_links' => $this->isActive() && (bool) $settings->show_social_links,
            'show_payment_methods' => $this->isActive() && (bool) $settings->show_payment_methods,
            'show_newsletter' => $this->isActive() && (bool) $settings->show_newsletter,
        ];
    }
    public function paymentMethods()
    {
        if (! $this->isActive() || ! $this->settings()->show_payment_methods) {
            return collect();
        }

        return PaymentMethodDisplay::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }
}