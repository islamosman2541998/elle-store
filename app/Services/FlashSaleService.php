<?php

namespace App\Services;

use App\Models\Order;
use App\Models\FlashSaleItem;
use Illuminate\Support\Facades\DB;

class FlashSaleService
{
    public function countFlashSaleItemsForOrder(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $order->loadMissing('items');

            foreach ($order->items as $item) {
                if (! $item->flash_sale_item_id) {
                    continue;
                }

                if ($item->flash_sale_counted_at) {
                    continue;
                }

                $flashSaleItem = FlashSaleItem::query()
                    ->whereKey($item->flash_sale_item_id)
                    ->lockForUpdate()
                    ->first();

                if (! $flashSaleItem) {
                    continue;
                }

                $flashSaleItem->increment('sold_count', (int) $item->quantity);

                $item->update([
                    'flash_sale_counted_at' => now(),
                ]);
            }
        });
    }
}