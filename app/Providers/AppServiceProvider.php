<?php

namespace App\Providers;

use App\Models\FooterLink;
use App\Models\FooterSetting;
use App\Models\NavigationLink;
use App\Models\PaymentMethodDisplay;
use App\Models\SocialLink;
use App\Models\Shipment;
use App\Models\StoreSetting;
use App\Models\WishlistItem;
use App\Services\Carriers\ShipmentSyncService;
use App\Services\Notifications\MailConfigurator;
use App\Services\StorefrontChromeService;
use App\Services\WishlistStateService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /** Models whose contents are cached inside StorefrontChromeService. */
    private const CHROME_MODELS = [
        StoreSetting::class,
        NavigationLink::class,
        FooterSetting::class,
        FooterLink::class,
        SocialLink::class,
        PaymentMethodDisplay::class,
    ];

    public function register(): void
    {
        $this->app->singleton(StorefrontChromeService::class);
        $this->app->singleton(WishlistStateService::class);
        $this->app->singleton(MailConfigurator::class);
    }

    public function boot(): void
    {
        // The composer matches `site.*`, which includes partials rendered once
        // per product card. The service memoises, so this stays at one set of
        // queries per request however often the composer fires.
        // `livewire.site.*` is listed too: Livewire renders its component views
        // in their own scope, so without it $storeSettings was undefined there
        // and the currency silently fell back to "EGP" instead of "ج.م".
        View::composer(['site.*', 'livewire.site.*'], function ($view) {
            $view->with($this->app->make(StorefrontChromeService::class)->viewData());
        });

        // If any of that data is edited mid-request (the admin panel saves and
        // then re-renders), drop the memo so nothing stale is shown.
        foreach (self::CHROME_MODELS as $model) {
            $model::saved($this->flushChrome(...));
            $model::deleted($this->flushChrome(...));
        }

        // Adding or removing a wishlist item must invalidate the memoised ids.
        WishlistItem::saved($this->flushWishlist(...));
        WishlistItem::deleted($this->flushWishlist(...));

        // Hand a new shipment straight to the carrier when that carrier is
        // configured for it. Failures are recorded on the shipment; they never
        // stop the shipment from being created.
        Shipment::created(function (Shipment $shipment): void {
            $company = $shipment->company;

            if (! $company?->usesApi() || ! $company->auto_create_shipment) {
                return;
            }

            if (filled($shipment->carrier_shipment_id)) {
                return;
            }

            $this->app->make(ShipmentSyncService::class)->create($shipment);
        });

        // Saving the settings must re-point the mailer straight away.
        StoreSetting::saved(function (): void {
            $this->app->make(MailConfigurator::class)->reset();
        });
    }

    private function flushWishlist(): void
    {
        $this->app->make(WishlistStateService::class)->flush();
    }

    private function flushChrome(Model $model): void
    {
        $this->app->make(StorefrontChromeService::class)->flush();

        if ($model instanceof StoreSetting) {
            StoreSetting::flushCurrent();
        }
    }
}
