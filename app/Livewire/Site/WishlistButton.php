<?php

namespace App\Livewire\Site;

use App\Models\Product;
use App\Models\WishlistItem;
use App\Services\WishlistStateService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Throwable;

class WishlistButton extends Component
{
    public int $productId;

    public bool $wished = false;

    public function mount(int $productId): void
    {
        $this->productId = $productId;
        $this->wished = $this->isWished();
    }

    public function toggleWishlist(): void
    {
        $product = Product::query()
            ->where('is_active', true)
            ->findOrFail($this->productId);

        $existing = $this->wishlistQuery()
            ->where('product_id', $product->id)
            ->first();

        if ($existing) {
            $existing->delete();

            $this->wished = false;

            $this->dispatch('wishlist-updated');
            $this->dispatch('wishlist-updated')->to(WishlistCounter::class);

            $this->dispatch('site-toast',
                type: 'success',
                icon: '✓',
                title: app()->getLocale() === 'ar' ? 'تم الحذف' : 'Removed',
                message: app()->getLocale() === 'ar'
                    ? 'تم حذف المنتج من المفضلة'
                    : 'Product has been removed from wishlist'
            );

            return;
        }

        WishlistItem::query()->create([
            'customer_id' => $this->customerId(),
            'session_id' => $this->customerId() ? null : session()->getId(),
            'product_id' => $product->id,
        ]);

        $this->wished = true;

        $this->dispatch('wishlist-updated');
        $this->dispatch('wishlist-updated')->to(WishlistCounter::class);

        $this->dispatch('site-toast',
            type: 'success',
            icon: '♥',
            title: app()->getLocale() === 'ar' ? 'تمت الإضافة' : 'Added',
            message: app()->getLocale() === 'ar'
                ? 'تم إضافة المنتج إلى المفضلة'
                : 'Product has been added to wishlist'
        );
    }

    private function isWished(): bool
    {
        // Reads the visitor's wishlist once per request rather than running an
        // `exists` query for every product card on the page.
        return app(WishlistStateService::class)->has($this->productId);
    }

    private function wishlistQuery()
    {
        $customerId = $this->customerId();

        return WishlistItem::query()
            ->when(
                $customerId,
                fn ($query) => $query->where('customer_id', $customerId),
                fn ($query) => $query->where('session_id', session()->getId())
            );
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
        return view('livewire.site.wishlist-button');
    }
}