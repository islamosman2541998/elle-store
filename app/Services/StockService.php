<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ReturnRequest;
use Illuminate\Support\Facades\DB;

class StockService
{
    public function deductOrderStock(Order $order): void
    {
        if ($order->stock_deducted_at) {
            return;
        }

        DB::transaction(function () use ($order) {
            $order->loadMissing('items');

            foreach ($order->items as $item) {
                if ($item->product_variant_id) {
                    $variant = ProductVariant::query()
                        ->whereKey($item->product_variant_id)
                        ->lockForUpdate()
                        ->first();

                    if (! $variant) {
                        continue;
                    }

                    $beforeQuantity = (int) $variant->stock_quantity;
                    $quantity = (int) $item->quantity;
                    $afterQuantity = max($beforeQuantity - $quantity, 0);

                    $variant->update([
                        'stock_quantity' => $afterQuantity,
                    ]);

                    $order->stockMovements()->create([
                        'product_id' => $item->product_id,
                        'product_variant_id' => $item->product_variant_id,
                        'order_item_id' => $item->id,
                        'user_id' => auth()->id(),
                        'type' => 'order_deduction',
                        'quantity' => -$quantity,
                        'before_quantity' => $beforeQuantity,
                        'after_quantity' => $afterQuantity,
                        'reference' => $order->order_number,
                        'notes' => 'Stock deducted after order status update.',
                    ]);

                    continue;
                }

                $product = Product::query()
                    ->whereKey($item->product_id)
                    ->lockForUpdate()
                    ->first();

                if (! $product) {
                    continue;
                }

                $beforeQuantity = (int) $product->stock_quantity;
                $quantity = (int) $item->quantity;
                $afterQuantity = max($beforeQuantity - $quantity, 0);

                $product->update([
                    'stock_quantity' => $afterQuantity,
                ]);

                $order->stockMovements()->create([
                    'product_id' => $item->product_id,
                    'product_variant_id' => null,
                    'order_item_id' => $item->id,
                    'user_id' => auth()->id(),
                    'type' => 'order_deduction',
                    'quantity' => -$quantity,
                    'before_quantity' => $beforeQuantity,
                    'after_quantity' => $afterQuantity,
                    'reference' => $order->order_number,
                    'notes' => 'Stock deducted after order status update.',
                ]);
            }

            $order->update([
                'stock_deducted_at' => now(),
            ]);
        });
    }

    public function restoreOrderStock(Order $order, string $type = 'order_cancelled_restore'): void
    {
        if (! $order->stock_deducted_at) {
            return;
        }

        DB::transaction(function () use ($order, $type) {
            $order->loadMissing('items');

            foreach ($order->items as $item) {
                if ($item->product_variant_id) {
                    $variant = ProductVariant::query()
                        ->whereKey($item->product_variant_id)
                        ->lockForUpdate()
                        ->first();

                    if (! $variant) {
                        continue;
                    }

                    $beforeQuantity = (int) $variant->stock_quantity;
                    $quantity = (int) $item->quantity;
                    $afterQuantity = $beforeQuantity + $quantity;

                    $variant->update([
                        'stock_quantity' => $afterQuantity,
                    ]);

                    $order->stockMovements()->create([
                        'product_id' => $item->product_id,
                        'product_variant_id' => $item->product_variant_id,
                        'order_item_id' => $item->id,
                        'user_id' => auth()->id(),
                        'type' => $type,
                        'quantity' => $quantity,
                        'before_quantity' => $beforeQuantity,
                        'after_quantity' => $afterQuantity,
                        'reference' => $order->order_number,
                        'notes' => 'Stock restored after order cancellation or return.',
                    ]);

                    continue;
                }

                $product = Product::query()
                    ->whereKey($item->product_id)
                    ->lockForUpdate()
                    ->first();

                if (! $product) {
                    continue;
                }

                $beforeQuantity = (int) $product->stock_quantity;
                $quantity = (int) $item->quantity;
                $afterQuantity = $beforeQuantity + $quantity;

                $product->update([
                    'stock_quantity' => $afterQuantity,
                ]);

                $order->stockMovements()->create([
                    'product_id' => $item->product_id,
                    'product_variant_id' => null,
                    'order_item_id' => $item->id,
                    'user_id' => auth()->id(),
                    'type' => $type,
                    'quantity' => $quantity,
                    'before_quantity' => $beforeQuantity,
                    'after_quantity' => $afterQuantity,
                    'reference' => $order->order_number,
                    'notes' => 'Stock restored after order cancellation or return.',
                ]);
            }

            $order->update([
                'stock_deducted_at' => null,
            ]);
        });
    }
    public function adjustStock(
        int $productId,
        ?int $variantId,
        int $quantity,
        string $type = 'manual_adjustment',
        ?string $reference = null,
        ?string $notes = null
    ): void {
        DB::transaction(function () use ($productId, $variantId, $quantity, $type, $reference, $notes) {
            if ($variantId) {
                $variant = ProductVariant::query()
                    ->whereKey($variantId)
                    ->lockForUpdate()
                    ->first();

                if (! $variant) {
                    return;
                }

                $beforeQuantity = (int) $variant->stock_quantity;
                $afterQuantity = max($beforeQuantity + $quantity, 0);

                $variant->update([
                    'stock_quantity' => $afterQuantity,
                ]);

                $variant->stockMovements()->create([
                    'product_id' => $productId,
                    'product_variant_id' => $variantId,
                    'user_id' => auth()->id(),
                    'type' => $type,
                    'quantity' => $quantity,
                    'before_quantity' => $beforeQuantity,
                    'after_quantity' => $afterQuantity,
                    'reference' => $reference,
                    'notes' => $notes,
                ]);

                return;
            }

            $product = Product::query()
                ->whereKey($productId)
                ->lockForUpdate()
                ->first();

            if (! $product) {
                return;
            }

            $beforeQuantity = (int) $product->stock_quantity;
            $afterQuantity = max($beforeQuantity + $quantity, 0);

            $product->update([
                'stock_quantity' => $afterQuantity,
            ]);

            $product->stockMovements()->create([
                'product_id' => $productId,
                'product_variant_id' => null,
                'user_id' => auth()->id(),
                'type' => $type,
                'quantity' => $quantity,
                'before_quantity' => $beforeQuantity,
                'after_quantity' => $afterQuantity,
                'reference' => $reference,
                'notes' => $notes,
            ]);
        });
    }
    public function restoreReturnRequestStock(ReturnRequest $returnRequest): void
    {
        if ($returnRequest->stock_restored_at) {
            return;
        }

        DB::transaction(function () use ($returnRequest) {
            $returnRequest->loadMissing('items');

            foreach ($returnRequest->items as $item) {
                if ($item->product_variant_id) {
                    $variant = ProductVariant::query()
                        ->whereKey($item->product_variant_id)
                        ->lockForUpdate()
                        ->first();

                    if (! $variant) {
                        continue;
                    }

                    $beforeQuantity = (int) $variant->stock_quantity;
                    $quantity = (int) $item->quantity;
                    $afterQuantity = $beforeQuantity + $quantity;

                    $variant->update([
                        'stock_quantity' => $afterQuantity,
                    ]);

                    $variant->stockMovements()->create([
                        'product_id' => $item->product_id,
                        'product_variant_id' => $item->product_variant_id,
                        'order_id' => $returnRequest->order_id,
                        'order_item_id' => $item->order_item_id,
                        'user_id' => auth()->id(),
                        'type' => 'return',
                        'quantity' => $quantity,
                        'before_quantity' => $beforeQuantity,
                        'after_quantity' => $afterQuantity,
                        'reference' => $returnRequest->return_number,
                        'notes' => 'Stock restored after return request.',
                    ]);

                    continue;
                }

                $product = Product::query()
                    ->whereKey($item->product_id)
                    ->lockForUpdate()
                    ->first();

                if (! $product) {
                    continue;
                }

                $beforeQuantity = (int) $product->stock_quantity;
                $quantity = (int) $item->quantity;
                $afterQuantity = $beforeQuantity + $quantity;

                $product->update([
                    'stock_quantity' => $afterQuantity,
                ]);

                $product->stockMovements()->create([
                    'product_id' => $item->product_id,
                    'product_variant_id' => null,
                    'order_id' => $returnRequest->order_id,
                    'order_item_id' => $item->order_item_id,
                    'user_id' => auth()->id(),
                    'type' => 'return',
                    'quantity' => $quantity,
                    'before_quantity' => $beforeQuantity,
                    'after_quantity' => $afterQuantity,
                    'reference' => $returnRequest->return_number,
                    'notes' => 'Stock restored after return request.',
                ]);
            }

            $returnRequest->update([
                'stock_restored_at' => now(),
            ]);
        });
    }
    public function availableQuantity(Product $product, ?ProductVariant $variant = null): ?int
    {
        if (! $product->manage_stock) {
            return null;
        }

        return $variant
            ? (int) $variant->stock_quantity
            : (int) $product->stock_quantity;
    }

    public function isInStock(Product $product, ?ProductVariant $variant = null): bool
    {
        if (! $product->manage_stock) {
            return true;
        }

        if ($product->allow_backorder) {
            return true;
        }

        return ($this->availableQuantity($product, $variant) ?? 0) > 0;
    }

    public function canAddToCart(
        Product $product,
        ?ProductVariant $variant,
        int $requestedQuantity,
        int $currentCartQuantity = 0
    ): bool {
        if (! $product->manage_stock) {
            return true;
        }

        if ($product->allow_backorder) {
            return true;
        }

        $availableQuantity = $this->availableQuantity($product, $variant) ?? 0;

        return $availableQuantity >= ($requestedQuantity + $currentCartQuantity);
    }

    public function stockLabel(Product $product, ?ProductVariant $variant = null): string
    {
        if (! $product->manage_stock) {
            return app()->getLocale() === 'ar' ? 'متوفر' : 'In stock';
        }

        if ($product->allow_backorder) {
            return app()->getLocale() === 'ar' ? 'متاح للطلب' : 'Available on backorder';
        }

        $availableQuantity = $this->availableQuantity($product, $variant) ?? 0;

        if ($availableQuantity > 0) {
            return app()->getLocale() === 'ar' ? 'متوفر' : 'In stock';
        }

        return app()->getLocale() === 'ar' ? 'نفد المخزون' : 'Out of stock';
    }
}