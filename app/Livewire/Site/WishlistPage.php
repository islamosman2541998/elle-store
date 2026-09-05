<?php

namespace App\Livewire\Site;

use App\Models\WishlistItem;
use Livewire\Attributes\On;
use Livewire\Component;

class WishlistPage extends Component
{
    #[On('wishlist-updated')]
    public function refreshWishlist(): void
    {
        //
    }

    public function removeFromWishlist(int $itemId): void
    {
        $item = WishlistItem::query()
            ->whereKey($itemId)
            ->when(
                $this->customerId(),
                fn($query) => $query->where('customer_id', $this->customerId()),
                fn($query) => $query->where('session_id', session()->getId())
            )
            ->first();

        if (! $item) {
            return;
        }

        $item->delete();

        $this->dispatch('wishlist-updated');

        $this->dispatch(
            'site-toast',
            type: 'success',
            icon: '✓',
            title: app()->getLocale() === 'ar' ? 'تم الحذف' : 'Removed',
            message: app()->getLocale() === 'ar'
                ? 'تم حذف المنتج من قائمة الرغبات'
                : 'Product removed from wishlist'
        );
    }

    private function wishlistItems()
    {
        $customerId = $this->customerId();

        return WishlistItem::query()
            ->with([
                'product.transNow',
                'product.arabicTranslation',
                'product.englishTranslation',
                'product.brand.transNow',
                'product.brand.arabicTranslation',
                'product.brand.englishTranslation',
                'product.category.transNow',
                'product.category.arabicTranslation',
                'product.category.englishTranslation',
                'product.discounts',
            ])
            ->when(
                $customerId,
                fn($query) => $query->where('customer_id', $customerId),
                fn($query) => $query->where('session_id', session()->getId())
            )
            ->latest()
            ->get();
    }

    private function customerId(): ?int
    {
        try {
            if (auth('customer')->check()) {
                return auth('customer')->id();
            }
        } catch (\Throwable $e) {
            return null;
        }

        return null;
    }
    public function render()
    {
        return view('livewire.site.wishlist-page', [
            'items' => $this->wishlistItems(),
        ]);
    }
}