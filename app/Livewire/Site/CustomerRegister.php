<?php

namespace App\Livewire\Site;

use App\Models\Customer;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\WishlistItem;
use App\Services\GuestDataMergeService;

class CustomerRegister extends Component
{
    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $accepts_marketing = false;

    public function register(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('customers', 'email')],
            'phone' => ['required', 'string', 'max:50', Rule::unique('customers', 'phone')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'name.required' => app()->getLocale() === 'ar' ? 'الاسم مطلوب' : 'Name is required',
            'email.required' => app()->getLocale() === 'ar' ? 'البريد الإلكتروني مطلوب' : 'Email is required',
            'email.email' => app()->getLocale() === 'ar' ? 'صيغة البريد الإلكتروني غير صحيحة' : 'Invalid email address',
            'email.unique' => app()->getLocale() === 'ar' ? 'هذا البريد مستخدم بالفعل' : 'This email is already used',
            'phone.required' => app()->getLocale() === 'ar' ? 'رقم الهاتف مطلوب' : 'Phone is required',
            'phone.unique' => app()->getLocale() === 'ar' ? 'رقم الهاتف مستخدم بالفعل' : 'This phone is already used',
            'password.required' => app()->getLocale() === 'ar' ? 'كلمة المرور مطلوبة' : 'Password is required',
            'password.min' => app()->getLocale() === 'ar' ? 'كلمة المرور يجب ألا تقل عن 8 أحرف' : 'Password must be at least 8 characters',
            'password.confirmed' => app()->getLocale() === 'ar' ? 'تأكيد كلمة المرور غير مطابق' : 'Password confirmation does not match',
        ]);

        $customer = Customer::query()->create([
            'name' => trim($this->name),
            'email' => mb_strtolower(trim($this->email)),
            'phone' => trim($this->phone),
            'password' => Hash::make($this->password),
            'is_active' => true,
            'accepts_marketing' => $this->accepts_marketing,
        ]);

        $guestSessionId = session()->getId();

        auth('customer')->login($customer);

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
        return view('livewire.site.customer-register');
    }
}