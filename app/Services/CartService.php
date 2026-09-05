<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Throwable;

/**
 * Adding a product to the visitor's cart.
 *
 * Lives in a service because two places need it: the "add to cart" button on
 * product cards and the quick-view dialog.
 */
class CartService
{
    /**
     * Add a product (optionally a specific variant) to the current cart.
     *
     * @return array{ok: bool, reason: ?string} `reason` is one of
     *         `out_of_stock` or `cart_quantity` when `ok` is false.
     */
    public function add(Product $product, ?ProductVariant $variant, int $quantity): array
    {
        $quantity = max(1, $quantity);

        if (! app(StockService::class)->canAddToCart($product, $variant, $quantity)) {
            return ['ok' => false, 'reason' => 'out_of_stock'];
        }

        $cart = $this->currentCart();

        $pricing = $variant
            ? app(ProductPricingService::class)->getVariantPrice($variant)
            : app(ProductPricingService::class)->getProductPrice($product);

        $unitPrice = (float) $pricing['final_price'];

        $item = CartItem::query()
            ->where('cart_id', $cart->id)
            ->where('product_id', $product->id)
            ->when(
                $variant,
                fn ($query) => $query->where('product_variant_id', $variant->id),
                fn ($query) => $query->whereNull('product_variant_id')
            )
            ->first();

        $currentQuantity = $item ? (int) $item->quantity : 0;
        $newQuantity = $currentQuantity + $quantity;

        if (! app(StockService::class)->canAddToCart(
            product: $product,
            variant: $variant,
            requestedQuantity: $quantity,
            currentCartQuantity: $currentQuantity
        )) {
            return ['ok' => false, 'reason' => 'cart_quantity'];
        }

        $snapshot = $this->snapshot($product, $variant, $pricing, $item ? $newQuantity : $quantity);

        if ($item) {
            $item->update([
                'quantity' => $newQuantity,
                'unit_price' => $unitPrice,
                'subtotal' => $newQuantity * $unitPrice,
                'snapshot' => $snapshot,
            ]);
        } else {
            CartItem::query()->create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'product_variant_id' => $variant?->id,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'subtotal' => $quantity * $unitPrice,
                'snapshot' => $snapshot,
            ]);
        }

        $this->recalculate($cart);

        return ['ok' => true, 'reason' => null];
    }

    /** Relations the cart logic needs loaded on the product. */
    public static function productRelations(): array
    {
        return [
            'transNow',
            'arabicTranslation',
            'englishTranslation',
            'discounts',
            'variants.transNow',
            'variants.arabicTranslation',
            'variants.englishTranslation',
            'variants.discounts',
            'variants.attributeValues.attribute.transNow',
            'variants.attributeValues.attributeValue.transNow',
        ];
    }

    private function currentCart(): Cart
    {
        $customerId = null;

        try {
            $customerId = auth('customer')->id();
        } catch (Throwable) {
            $customerId = null;
        }

        return Cart::query()->firstOrCreate(
            [
                'session_id' => session()->getId(),
                'customer_id' => $customerId,
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
    }

    private function recalculate(Cart $cart): void
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

    private function snapshot(Product $product, ?ProductVariant $variant, array $pricing, int $quantity): array
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
                'attributes' => $this->variantAttributes($variant),
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

    private function variantAttributes(ProductVariant $variant): array
    {
        return $variant->attributeValues
            ->map(function ($item) {
                $attribute = $item->attribute;
                $value = $item->attributeValue;

                return [
                    'attribute_id' => $item->attribute_id,
                    'attribute_value_id' => $item->attribute_value_id,
                    'attribute_name' => $attribute?->transNow?->name
                        ?? $attribute?->arabicTranslation?->name
                        ?? $attribute?->englishTranslation?->name,
                    'value_name' => $value?->transNow?->value
                        ?? $value?->arabicTranslation?->value
                        ?? $value?->englishTranslation?->value,
                ];
            })
            ->values()
            ->toArray();
    }
}
