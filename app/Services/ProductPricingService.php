<?php

namespace App\Services;

use App\Models\FlashSaleItem;
use App\Models\Product;
use App\Models\ProductDiscount;
use App\Models\ProductVariant;

class ProductPricingService
{
    public function getProductPrice(Product $product): array
    {
        $originalPrice = (float) $product->price;

        $flashSaleItem = $this->getActiveFlashSaleItem(
            productId: $product->id,
            variantId: null
        );

        if ($flashSaleItem) {
            $flashSalePrice = $flashSaleItem->calculateSalePrice($originalPrice);

            return [
                'original_price' => $originalPrice,
                'final_price' => $flashSalePrice,
                'discount_amount' => $flashSaleItem->calculateDiscountAmount($originalPrice),
                'discount_source' => 'flash_sale',
                'flash_sale_item_id' => $flashSaleItem->id,
                'product_discount_id' => null,
            ];
        }

        $productDiscount = $this->getActiveProductDiscount(
            productId: $product->id,
            variantId: null
        );

        if ($productDiscount) {
            $discountPrice = $productDiscount->calculateSalePrice($originalPrice);

            return [
                'original_price' => $originalPrice,
                'final_price' => $discountPrice,
                'discount_amount' => $productDiscount->calculateDiscountAmount($originalPrice),
                'discount_source' => 'product_discount',
                'flash_sale_item_id' => null,
                'product_discount_id' => $productDiscount->id,
            ];
        }

        $regularSalePrice = $product->sale_price
            ? (float) $product->sale_price
            : null;

        if ($regularSalePrice && $regularSalePrice < $originalPrice) {
            return [
                'original_price' => $originalPrice,
                'final_price' => $regularSalePrice,
                'discount_amount' => round($originalPrice - $regularSalePrice, 2),
                'discount_source' => 'regular_sale',
                'flash_sale_item_id' => null,
                'product_discount_id' => null,
            ];
        }

        return [
            'original_price' => $originalPrice,
            'final_price' => $originalPrice,
            'discount_amount' => 0,
            'discount_source' => null,
            'flash_sale_item_id' => null,
            'product_discount_id' => null,
        ];
    }

    public function getVariantPrice(ProductVariant $variant): array
    {
        $product = $variant->product;

        $originalPrice = $variant->price
            ? (float) $variant->price
            : (float) $product?->price;

        $flashSaleItem = $this->getActiveFlashSaleItem(
            productId: $variant->product_id,
            variantId: $variant->id
        );

        if (! $flashSaleItem) {
            $flashSaleItem = $this->getActiveFlashSaleItem(
                productId: $variant->product_id,
                variantId: null
            );
        }

        if ($flashSaleItem) {
            $flashSalePrice = $flashSaleItem->calculateSalePrice($originalPrice);

            return [
                'original_price' => $originalPrice,
                'final_price' => $flashSalePrice,
                'discount_amount' => $flashSaleItem->calculateDiscountAmount($originalPrice),
                'discount_source' => 'flash_sale',
                'flash_sale_item_id' => $flashSaleItem->id,
                'product_discount_id' => null,
            ];
        }

        $productDiscount = $this->getActiveProductDiscount(
            productId: $variant->product_id,
            variantId: $variant->id
        );

        if (! $productDiscount) {
            $productDiscount = $this->getActiveProductDiscount(
                productId: $variant->product_id,
                variantId: null
            );
        }

        if ($productDiscount) {
            $discountPrice = $productDiscount->calculateSalePrice($originalPrice);

            return [
                'original_price' => $originalPrice,
                'final_price' => $discountPrice,
                'discount_amount' => $productDiscount->calculateDiscountAmount($originalPrice),
                'discount_source' => 'product_discount',
                'flash_sale_item_id' => null,
                'product_discount_id' => $productDiscount->id,
            ];
        }

        $regularSalePrice = $variant->sale_price
            ? (float) $variant->sale_price
            : ($product?->sale_price ? (float) $product->sale_price : null);

        if ($regularSalePrice && $regularSalePrice < $originalPrice) {
            return [
                'original_price' => $originalPrice,
                'final_price' => $regularSalePrice,
                'discount_amount' => round($originalPrice - $regularSalePrice, 2),
                'discount_source' => 'regular_sale',
                'flash_sale_item_id' => null,
                'product_discount_id' => null,
            ];
        }

        return [
            'original_price' => $originalPrice,
            'final_price' => $originalPrice,
            'discount_amount' => 0,
            'discount_source' => null,
            'flash_sale_item_id' => null,
            'product_discount_id' => null,
        ];
    }

    protected function getActiveFlashSaleItem(int $productId, ?int $variantId): ?FlashSaleItem
    {
        return FlashSaleItem::query()
            ->where('product_id', $productId)
            ->where('product_variant_id', $variantId)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('quantity_limit')
                    ->orWhereColumn('sold_count', '<', 'quantity_limit');
            })
            ->whereHas('flashSale', function ($query) {
                $query->running();
            })
            ->latest()
            ->first();
    }

    protected function getActiveProductDiscount(int $productId, ?int $variantId): ?ProductDiscount
    {
        return ProductDiscount::query()
            ->where('product_id', $productId)
            ->where('product_variant_id', $variantId)
            ->running()
            ->orderByDesc('priority')
            ->latest()
            ->first();
    }
}