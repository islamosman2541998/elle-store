<?php

namespace App\Livewire\Site;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\ProductPricingService;
use App\Services\StockService;
use Livewire\Component;

class CartPage extends Component
{
    public function increaseQuantity(int $itemId): void
    {
        $item = $this->cartItem($itemId);

        if (! $item) {
            return;
        }

        $product = $item->product;
        $variant = $item->variant;

        if (! $product) {
            $this->removeItem($itemId);
            return;
        }

        $canAdd = app(StockService::class)->canAddToCart(
            product: $product,
            variant: $variant,
            requestedQuantity: 1,
            currentCartQuantity: (int) $item->quantity
        );

        if (! $canAdd) {
            $this->dispatch('site-toast',
                type: 'error',
                icon: '!',
                title: app()->getLocale() === 'ar' ? 'كمية غير متاحة' : 'Quantity unavailable',
                message: app()->getLocale() === 'ar'
                    ? 'الكمية المطلوبة أكبر من المخزون المتاح'
                    : 'The requested quantity exceeds available stock'
            );

            return;
        }

        $this->updateItemQuantity($item, (int) $item->quantity + 1);
    }

    public function decreaseQuantity(int $itemId): void
    {
        $item = $this->cartItem($itemId);

        if (! $item) {
            return;
        }

        if ((int) $item->quantity <= 1) {
            return;
        }

        $this->updateItemQuantity($item, (int) $item->quantity - 1);
    }

    public function removeItem(int $itemId): void
    {
        $item = $this->cartItem($itemId);

        if (! $item) {
            return;
        }

        $cart = $item->cart;

        $item->delete();

        if ($cart) {
            $this->recalculateCart($cart);
        }

        $this->dispatch('cart-updated');

        $this->dispatch('site-toast',
            type: 'success',
            icon: '✓',
            title: app()->getLocale() === 'ar' ? 'تم الحذف' : 'Removed',
            message: app()->getLocale() === 'ar'
                ? 'تم حذف المنتج من السلة'
                : 'Item removed from cart'
        );
    }

    public function clearCart(): void
    {
        $cart = $this->cart();

        if (! $cart) {
            return;
        }

        $cart->items()->delete();

        $this->recalculateCart($cart);

        $this->dispatch('cart-updated');

        $this->dispatch('site-toast',
            type: 'success',
            icon: '✓',
            title: app()->getLocale() === 'ar' ? 'تم تفريغ السلة' : 'Cart cleared',
            message: app()->getLocale() === 'ar'
                ? 'تم حذف كل المنتجات من السلة'
                : 'All items have been removed from your cart'
        );
    }

    private function updateItemQuantity(CartItem $item, int $quantity): void
    {
        $quantity = max(1, $quantity);

        $product = $item->product;
        $variant = $item->variant;

        if (! $product) {
            $item->delete();

            if ($item->cart) {
                $this->recalculateCart($item->cart);
            }

            return;
        }

        $pricing = $variant
            ? app(ProductPricingService::class)->getVariantPrice($variant)
            : app(ProductPricingService::class)->getProductPrice($product);

        $unitPrice = (float) $pricing['final_price'];

        $item->update([
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'subtotal' => $quantity * $unitPrice,
            'snapshot' => $this->itemSnapshot($product, $variant, $pricing, $quantity),
        ]);

        $this->recalculateCart($item->cart);

        $this->dispatch('cart-updated');
    }

    private function cartItem(int $itemId): ?CartItem
    {
        $cart = $this->cart();

        if (! $cart) {
            return null;
        }

        return CartItem::query()
            ->where('cart_id', $cart->id)
            ->with([
                'cart',
                'product.transNow',
                'product.arabicTranslation',
                'product.englishTranslation',
                'product.discounts',
                'variant.product',
                'variant.transNow',
                'variant.arabicTranslation',
                'variant.englishTranslation',
                'variant.discounts',
                'variant.attributeValues.attribute.transNow',
                'variant.attributeValues.attribute.arabicTranslation',
                'variant.attributeValues.attribute.englishTranslation',
                'variant.attributeValues.attributeValue.transNow',
                'variant.attributeValues.attributeValue.arabicTranslation',
                'variant.attributeValues.attributeValue.englishTranslation',
            ])
            ->find($itemId);
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

    private function itemSnapshot(Product $product, ?ProductVariant $variant, array $pricing, int $quantity): array
    {
        $productTranslation = $product->transNow
            ?? $product->arabicTranslation
            ?? $product->englishTranslation;

        $variantTranslation = $variant
            ? ($variant->transNow
                ?? $variant->arabicTranslation
                ?? $variant->englishTranslation)
            : null;

        return [
            'product' => [
                'id' => $product->id,
                'name' => $productTranslation?->name,
                'sku' => $product->sku,
                'barcode' => $product->barcode,
                'image' => $product->main_image,
                'price' => $product->price,
                'sale_price' => $product->sale_price,
            ],

            'variant' => $variant ? [
                'id' => $variant->id,
                'name' => $variantTranslation?->name,
                'sku' => $variant->sku,
                'barcode' => $variant->barcode,
                'image' => $variant->image,
                'price' => $variant->price,
                'sale_price' => $variant->sale_price,
                'attributes' => $this->variantAttributesSnapshot($variant),
            ] : null,

            'unit_price' => (float) $pricing['final_price'],
            'original_unit_price' => (float) $pricing['original_price'],
            'discount_amount' => (float) $pricing['discount_amount'],
            'discount_source' => $pricing['discount_source'],
            'flash_sale_item_id' => $pricing['flash_sale_item_id'],
            'product_discount_id' => $pricing['product_discount_id'],
            'quantity' => $quantity,
        ];
    }

    private function variantAttributesSnapshot(ProductVariant $variant): array
    {
        return $variant->attributeValues
            ->map(function ($item) {
                $attribute = $item->attribute;
                $value = $item->attributeValue;

                $attributeName =
                    $attribute?->transNow?->name
                    ?? $attribute?->arabicTranslation?->name
                    ?? $attribute?->englishTranslation?->name
                    ?? null;

                $valueName =
                    $value?->transNow?->value
                    ?? $value?->arabicTranslation?->value
                    ?? $value?->englishTranslation?->value
                    ?? null;

                return [
                    'attribute_id' => $item->attribute_id,
                    'attribute_value_id' => $item->attribute_value_id,
                    'attribute_name' => $attributeName,
                    'value_name' => $valueName,
                ];
            })
            ->values()
            ->toArray();
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
        $cart = $this->cart();

        $items = collect();

        if ($cart) {
            $items = $cart->items()
                ->with([
                    'product.transNow',
                    'product.arabicTranslation',
                    'product.englishTranslation',
                    'variant.transNow',
                    'variant.arabicTranslation',
                    'variant.englishTranslation',
                ])
                ->latest()
                ->get();
        }

        return view('livewire.site.cart-page', [
            'cart' => $cart,
            'items' => $items,
        ]);
    }
}