<?php

namespace App\Services;

use App\Models\FooterLink;
use App\Models\FooterSetting;
use App\Models\NavigationLink;
use App\Models\PaymentMethodDisplay;
use App\Models\SocialLink;
use App\Models\StoreSetting;

/**
 * The header/footer chrome shared by every storefront view.
 *
 * This is bound as a singleton, so each block of data is loaded at most once
 * per request no matter how many partials ask for it. Before this existed the
 * `site.*` view composer re-ran seven queries for every partial it matched -
 * including one per product card - which cost ~230 queries on the homepage.
 */
class StorefrontChromeService
{
    private ?StoreSetting $storeSettings = null;

    private ?array $navigation = null;

    private ?FooterSetting $footerSettings = null;

    private bool $footerSettingsLoaded = false;

    private $footerLinks = null;

    private $socialLinks = null;

    private $paymentMethods = null;

    public function storeSettings(): StoreSetting
    {
        return $this->storeSettings ??= StoreSetting::current();
    }

    /** @return array{header_links: mixed, mobile_links: mixed} */
    public function navigation(): array
    {
        if ($this->navigation !== null) {
            return $this->navigation;
        }

        // resolved_url reads page / category / brand, so eager load them or
        // every single link costs an extra query while rendering the menu.
        $links = NavigationLink::query()
            ->where('is_active', true)
            ->whereIn('location', ['header', 'mobile'])
            ->with(['page', 'category', 'brand'])
            ->orderBy('sort_order')
            ->get();

        $headerLinks = $links->where('location', 'header')->values();
        $mobileLinks = $links->where('location', 'mobile')->values();

        if ($mobileLinks->isEmpty()) {
            $mobileLinks = $headerLinks;
        }

        return $this->navigation = [
            'header_links' => $headerLinks,
            'mobile_links' => $mobileLinks,
        ];
    }

    public function footerSettings(): ?FooterSetting
    {
        if (! $this->footerSettingsLoaded) {
            $this->footerSettings = FooterSetting::query()
                ->where('is_active', true)
                ->first();

            $this->footerSettingsLoaded = true;
        }

        return $this->footerSettings;
    }

    public function footerLinks()
    {
        return $this->footerLinks ??= FooterLink::query()
            ->where('is_active', true)
            ->with('page')
            ->orderBy('sort_order')
            ->get();
    }

    public function socialLinks()
    {
        return $this->socialLinks ??= SocialLink::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    public function paymentMethods()
    {
        return $this->paymentMethods ??= PaymentMethodDisplay::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    /** Everything the site layout needs, shaped for View::composer. */
    public function viewData(): array
    {
        return [
            'storeSettings' => $this->storeSettings(),
            'navigation' => $this->navigation(),
            'footerSettings' => $this->footerSettings(),
            'footerLinks' => $this->footerLinks(),
            'socialLinks' => $this->socialLinks(),
            'paymentMethods' => $this->paymentMethods(),
        ];
    }

    /** Drop everything so the next read hits the database again. */
    public function flush(): void
    {
        $this->storeSettings = null;
        $this->navigation = null;
        $this->footerSettings = null;
        $this->footerSettingsLoaded = false;
        $this->footerLinks = null;
        $this->socialLinks = null;
        $this->paymentMethods = null;
    }
}
