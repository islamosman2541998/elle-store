<?php

namespace App\Livewire\Site;

use App\Models\Product;
use App\Services\CartService;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class AddToCartButton extends Component
{
    public int $productId;

    #[Reactive]
    public ?int $variantId = null;

    #[Reactive]
    public ?int $quantity = 1;

    public bool $added = false;

    public function mount(int $productId, ?int $variantId = null, ?int $quantity = 1): void
    {
        $this->productId = $productId;
        $this->variantId = $variantId;
        $this->quantity = max(1, (int) ($quantity ?: 1));
    }

    public function addToCart(): void
    {
        $requestedQuantity = max(1, (int) ($this->quantity ?: 1));

        $this->quantity = $requestedQuantity;

        $product = Product::query()
            ->with(CartService::productRelations())
            ->where('is_active', true)
            ->findOrFail($this->productId);

        $activeVariants = $product->variants
            ->where('is_active', true)
            ->values();

        $variant = null;

        if ($this->variantId) {
            $variant = $activeVariants->firstWhere('id', $this->variantId);

            if (! $variant) {
                $this->toast(
                    'error',
                    '!',
                    ar: ['اختيار غير صحيح', 'هذا الاختيار غير متاح حاليًا'],
                    en: ['Invalid option', 'This selected option is not available']
                );

                return;
            }
        }

        // One active variant means there is nothing to choose - use it rather
        // than sending the customer to the product page.
        if (! $variant && $activeVariants->count() === 1) {
            $variant = $activeVariants->first();
        }

        // Several variants and none picked yet: open the quick-view dialog so
        // colour and size can be chosen without leaving this page.
        if (! $variant && $activeVariants->count() > 1) {
            $this->dispatch('open-quick-view', productId: $product->id);

            return;
        }

        $result = app(CartService::class)->add($product, $variant, $requestedQuantity);

        if (! $result['ok']) {
            $result['reason'] === 'cart_quantity'
                ? $this->toast(
                    'error',
                    '!',
                    ar: ['كمية غير متاحة', 'الكمية الموجودة في السلة مع الكمية المطلوبة أكبر من المخزون المتاح'],
                    en: ['Quantity unavailable', 'The cart quantity plus requested quantity exceeds available stock']
                )
                : $this->toast(
                    'error',
                    '!',
                    ar: ['غير متوفر', 'الكمية المطلوبة غير متوفرة حاليًا'],
                    en: ['Unavailable', 'The requested quantity is not currently available']
                );

            return;
        }

        $this->added = true;

        $this->dispatch('cart-updated');
        $this->dispatch('cart-updated')->to(CartCounter::class);

        $this->toast(
            'success',
            '✓',
            ar: ['تمت الإضافة', 'تم إضافة المنتج إلى السلة'],
            en: ['Added', 'Product has been added to cart']
        );
    }

    /**
     * @param  array{0: string, 1: string}  $ar
     * @param  array{0: string, 1: string}  $en
     */
    private function toast(string $type, string $icon, array $ar, array $en): void
    {
        [$title, $message] = app()->getLocale() === 'ar' ? $ar : $en;

        $this->dispatch('site-toast', type: $type, icon: $icon, title: $title, message: $message);
    }

    public function render()
    {
        return view('livewire.site.add-to-cart-button');
    }
}
