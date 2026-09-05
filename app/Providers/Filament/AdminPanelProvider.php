<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\DashboardStatsOverview;
use App\Filament\Widgets\LatestOrdersTable;
use App\Filament\Widgets\OrdersSalesChart;
use App\Filament\Widgets\OrdersStatusChart;
use App\Filament\Widgets\TopSellingProductsChart;
use App\Http\Middleware\SetLocale;
use App\Models\StoreSetting;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Enums\ThemeMode;
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationGroup;
use Filament\Notifications\Notification;
use Filament\Pages;
use Filament\Pages\BasePage;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\Validation\ValidationException;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function boot(): void
    {
        // Filament reports nothing by default when a form fails validation, so
        // an error on a collapsed section or an unopened tab made the save
        // button look broken. Surface it, and say which fields are at fault.
        BasePage::$reportValidationErrorUsing = function (ValidationException $exception): void {
            $fields = collect($exception->validator->errors()->messages())
                ->keys()
                ->map(fn (string $key) => (string) str($key)->afterLast('.'))
                ->unique()
                ->implode(', ');

            Notification::make()
                ->title(app()->getLocale() === 'ar'
                    ? 'لم يتم الحفظ — راجعي الحقول التالية'
                    : 'Not saved - please check these fields')
                ->body($fields)
                ->danger()
                ->persistent()
                ->send();
        };
    }

    /**
     * Starting theme for the panel.
     *
     * Reads the store setting, but never lets a missing or unreachable
     * settings table stop the panel from booting (fresh install, migrations).
     */
    private function defaultThemeMode(): ThemeMode
    {
        try {
            return StoreSetting::current()->dashboard_dark_mode_default
                ? ThemeMode::Dark
                : ThemeMode::System;
        } catch (\Throwable) {
            return ThemeMode::System;
        }
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->userMenuItems([
                MenuItem::make()
                    ->label('العربية')
                    ->icon('heroicon-o-language')
                    ->url(fn() => route('switch.language', ['locale' => 'ar'])),

                MenuItem::make()
                    ->label('English')
                    ->icon('heroicon-o-language')
                    ->url(fn() => route('switch.language', ['locale' => 'en'])),
            ])
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->font('Cairo')
            // "Dark mode by default" is a store setting; without this it was a
            // toggle in the dashboard that changed nothing. Staff can still
            // switch themes themselves - this only sets the starting point.
            ->defaultThemeMode($this->defaultThemeMode())
            ->colors([
                'primary' => Color::Amber,
            ])
            
            ->brandName(fn() => StoreSetting::current()->store_name_en ?: config('app.name'))
            ->brandLogo(function () {
                $logo = StoreSetting::current()->dashboard_logo;

                return $logo ? asset('storage/' . $logo) : null;
            })
            ->favicon(function () {
                $favicon = StoreSetting::current()->dashboard_favicon;

                return $favicon ? asset('storage/' . $favicon) : null;
            })
            // The login page is the one screen with no topbar and no user
            // menu, so it had no way to switch theme at all. Scoped to the
            // login page: every other screen already has the switcher.
            ->renderHook(
                PanelsRenderHook::BODY_START,
                fn () => Blade::render(
                    '<div class="fi-login-theme-switcher"><x-filament-panels::theme-switcher /></div>'
                ),
                scopes: \Filament\Pages\Auth\Login::class,
            )
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn() => '
                    <script>
                        document.documentElement.setAttribute("lang", "' . app()->getLocale() . '");
                        document.documentElement.setAttribute("dir", "' . (app()->getLocale() === 'ar' ? 'rtl' : 'ltr') . '");
                    </script>
                ' . Blade::render("@include('filament.dashboard-appearance')")
            )
            // Sidebar groups, ordered by how often the shop actually uses them:
            // daily operations first, one-off configuration last.
            ->navigationGroups([
                NavigationGroup::make(fn() => __('admin.sales')),
                NavigationGroup::make(fn() => __('admin.catalog')),
                NavigationGroup::make(fn() => __('admin.inventory_management')),
                NavigationGroup::make(fn() => __('admin.customers_management')),
                NavigationGroup::make(fn() => __('admin.shipping_delivery')),
                NavigationGroup::make(fn() => __('admin.promotions')),
                NavigationGroup::make(fn() => __('admin.content_management')),
                NavigationGroup::make(fn() => __('admin.storefront_settings')),
                NavigationGroup::make(fn() => __('admin.reports_logs')),
                NavigationGroup::make(fn() => __('admin.system_settings')),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                DashboardStatsOverview::class,
                OrdersSalesChart::class,
                OrdersStatusChart::class,
                TopSellingProductsChart::class,
                LatestOrdersTable::class,

            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                SetLocale::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}