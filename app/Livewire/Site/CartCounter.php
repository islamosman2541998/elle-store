<?php

namespace App\Livewire\Site;

use App\Models\Cart;
use Livewire\Attributes\On;
use Livewire\Component;

class CartCounter extends Component
{
    public int $count = 0;

    public function mount(): void
    {
        $this->refreshCartCount();
    }

    #[On('cart-updated')]
    public function refreshCartCount(): void
    {
        $this->count = $this->getCartCount();
    }

    private function getCartCount(): int
    {
        $cart = $this->cart();

        if (! $cart) {
            return 0;
        }

        return (int) $cart->items()->sum('quantity');
    }

    private function cart(): ?Cart
    {
        $sessionId = session()->getId();

        $customerId = null;

        try {
            $customerId = auth('customer')->id();
        } catch (\Throwable $e) {
            $customerId = null;
        }

        return Cart::query()
            ->where('session_id', $sessionId)
            ->where('customer_id', $customerId)
            ->where('status', 'active')
            ->first();
    }

    public function render()
    {
        return view('livewire.site.cart-counter');
    }
}