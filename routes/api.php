<?php

use App\Http\Controllers\CarrierWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API routes
|--------------------------------------------------------------------------
|
| These are deliberately outside the "web" group: a carrier posting a status
| update has no CSRF token and no session, and must still get through while
| the storefront is closed for maintenance.
|
*/

Route::post('carriers/{company}/webhook', CarrierWebhookController::class)
    ->name('carriers.webhook')
    // Carriers retry aggressively; keep a sane ceiling per company.
    ->middleware('throttle:120,1');
