<?php

namespace App\Livewire\Site;

use App\Models\WishlistItem;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;
use Throwable;

class WishlistCounter extends Component
{
    public int $count = 0;

    public function mount(): void
    {
        $this->refreshWishlistCount();
    }

    #[On('wishlist-updated')]
    public function refreshWishlistCount(): void
    {
        $this->count = $this->getWishlistCount();
    }

    private function getWishlistCount(): int
    {
        $customerId = $this->customerId();

        return WishlistItem::query()
            ->when(
                $customerId,
                fn ($query) => $query->where('customer_id', $customerId),
                fn ($query) => $query->where('session_id', session()->getId())
            )
            ->count();
    }

    private function customerId(): ?int
    {
        try {
            if (Auth::guard('customer')->check()) {
                return Auth::guard('customer')->id();
            }
        } catch (Throwable $e) {
            return null;
        }

        return null;
    }

    public function render()
    {
        return view('livewire.site.wishlist-counter');
    }
}