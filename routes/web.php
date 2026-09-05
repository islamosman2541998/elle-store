<?php

use App\Http\Controllers\Admin\InvoicePrintController;
use App\Http\Controllers\Site\HomeController;
use App\Http\Controllers\Site\PageController;
use App\Http\Controllers\Site\ProductController;
use App\Http\Controllers\Site\ShopController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Site\OrderController;



Route::get('/', [HomeController::class, 'index'])->name('site.home');
Route::get('/pages/{slug}', [PageController::class, 'show'])
    ->name('site.pages.show');
Route::middleware(['web', 'auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/invoices/{invoice}/print', InvoicePrintController::class)
        ->name('invoices.print');
});

Route::view('/shop', 'site.pages.shop.index')->name('site.shop');
Route::get('/product/{slug}', [ProductController::class, 'show'])->name('site.products.show');
Route::view('/cart', 'site.pages.cart.index')->name('site.cart');
Route::view('/checkout', 'site.pages.checkout.index')->name('site.checkout');
Route::get('/order/{orderNumber}', [OrderController::class, 'show'])
    ->name('site.orders.show');
Route::view('/track-order', 'site.pages.orders.track')
    ->name('site.orders.track');
Route::view('/my-orders', 'site.pages.customer.orders')
    ->name('site.customer.orders');
    Route::view('/wishlist', 'site.pages.wishlist.index')
    ->name('site.wishlist.index');
Route::middleware('guest:customer')->group(function () {
    Route::view('/customer/login', 'site.pages.customer.login')
        ->name('site.customer.login');

    Route::view('/customer/register', 'site.pages.customer.register')
        ->name('site.customer.register');
});

Route::middleware('auth:customer')->group(function () {
    Route::view('/account', 'site.pages.customer.account')
        ->name('site.customer.account');
        Route::view('/account/addresses', 'site.pages.customer.addresses')
    ->name('site.customer.addresses');
});

Route::post('/customer/logout', function () {
    auth('customer')->logout();

    request()->session()->regenerateToken();

    return redirect()->route('site.home');
})->name('site.customer.logout');
Route::get('/switch-language/{locale}', function ($locale) {
    if (! in_array($locale, ['en', 'ar'])) {
        abort(400);
    }

    session(['locale' => $locale]);

    return back();
})->name('switch.language');