<?php

namespace App\Livewire\Site;

use App\Models\Customer;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\WishlistItem;
use App\Services\GuestDataMergeService;

class CustomerLogin extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public function login(): void
    {
        $this->resetErrorBag();

        $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => app()->getLocale() === 'ar'
                ? 'اكتب البريد الإلكتروني'
                : 'Email is required',

            'email.email' => app()->getLocale() === 'ar'
                ? 'صيغة البريد الإلكتروني غير صحيحة'
                : 'Invalid email address',

            'password.required' => app()->getLocale() === 'ar'
                ? 'اكتب كلمة المرور'
                : 'Password is required',
        ]);

        $email = mb_strtolower(trim($this->email));

        $customer = Customer::query()
            ->where('email', $email)
            ->first();

        if (! $customer) {
            $this->addError(
                'email',
                app()->getLocale() === 'ar'
                    ? 'هذا البريد الإلكتروني غير مسجل'
                    : 'This email is not registered'
            );

            return;
        }

        if (! $customer->is_active) {
            $this->addError(
                'login',
                app()->getLocale() === 'ar'
                    ? 'هذا الحساب غير مفعل حاليًا'
                    : 'This account is currently inactive'
            );

            return;
        }

        if (! Hash::check($this->password, $customer->password)) {
            $this->addError(
                'password',
                app()->getLocale() === 'ar'
                    ? 'كلمة المرور غير صحيحة'
                    : 'Incorrect password'
            );

            return;
        }

        $guestSessionId = session()->getId();

        auth('customer')->login($customer, $this->remember);

        app(GuestDataMergeService::class)->merge($customer->id, $guestSessionId);

        session()->regenerate();

        $this->dispatch('cart-updated');
        $this->dispatch('wishlist-updated');

        $this->redirectRoute('site.customer.account');
    }
    private function mergeGuestWishlist(int $customerId, string $guestSessionId): void
    {
        $guestItems = WishlistItem::query()
            ->whereNull('customer_id')
            ->where('session_id', $guestSessionId)
            ->get();

        foreach ($guestItems as $guestItem) {
            $exists = WishlistItem::query()
                ->where('customer_id', $customerId)
                ->where('product_id', $guestItem->product_id)
                ->exists();

            if ($exists) {
                $guestItem->delete();
                continue;
            }

            $guestItem->update([
                'customer_id' => $customerId,
                'session_id' => null,
            ]);
        }
    }

    private function mergeGuestCart(int $customerId, string $guestSessionId): void
    {
        $guestCart = Cart::query()
            ->whereNull('customer_id')
            ->where('session_id', $guestSessionId)
            ->where('status', 'active')
            ->with('items')
            ->first();

        if (! $guestCart) {
            return;
        }

        $customerCart = Cart::query()->firstOrCreate(
            [
                'customer_id' => $customerId,
                'session_id' => $guestSessionId,
                'status' => 'active',
            ],
            [
                'subtotal' => 0,
                'discount_total' => 0,
                'shipping_total' => 0,
                'tax_total' => 0,
                'grand_total' => 0,
                'last_activity_at' => now(),
            ]
        );

        foreach ($guestCart->items as $guestItem) {
            $existingItem = CartItem::query()
                ->where('cart_id', $customerCart->id)
                ->where('product_id', $guestItem->product_id)
                ->where('product_variant_id', $guestItem->product_variant_id)
                ->first();

            if ($existingItem) {
                $newQuantity = (int) $existingItem->quantity + (int) $guestItem->quantity;

                $existingItem->update([
                    'quantity' => $newQuantity,
                    'subtotal' => $newQuantity * (float) $existingItem->unit_price,
                ]);

                $guestItem->delete();
                continue;
            }

            $guestItem->update([
                'cart_id' => $customerCart->id,
            ]);
        }

        $this->recalculateCart($customerCart);

        $guestCart->items()->delete();
        $guestCart->delete();
    }

    private function recalculateCart(Cart $cart): void
    {
        $subtotal = (float) $cart->items()->sum('subtotal');

        $cart->update([
            'subtotal' => $subtotal,
            'grand_total' => $subtotal
                - (float) $cart->discount_total
                + (float) $cart->shipping_total
                + (float) $cart->tax_total,
            'last_activity_at' => now(),
        ]);
    }
    public function render()
    {
        return view('livewire.site.customer-login');
    }
}