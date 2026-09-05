<?php

namespace App\Http\Middleware;

use App\Models\StoreSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Closes the storefront while "store active" is switched off in the dashboard.
 *
 * The setting and its maintenance message already existed but nothing enforced
 * them, so turning the store off left it fully open and still taking orders.
 *
 * The admin panel is never blocked - otherwise the owner could not switch the
 * store back on - and signed-in staff can still browse the site to check it.
 */
class EnsureStoreIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldBypass($request)) {
            return $next($request);
        }

        $settings = StoreSetting::current();

        if ($settings->is_store_active) {
            return $next($request);
        }

        return response()
            ->view('site.pages.maintenance', ['storeSettings' => $settings], 503)
            ->header('Retry-After', '3600');
    }

    private function shouldBypass(Request $request): bool
    {
        // Never lock the owner out of the dashboard or the login screen.
        if ($request->is('admin', 'admin/*', 'livewire/*', 'up')) {
            return true;
        }

        // Staff can keep previewing the storefront while it is closed.
        return Auth::guard('web')->check();
    }
}
