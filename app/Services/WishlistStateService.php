<?php

namespace App\Services;

use App\Models\WishlistItem;
use Illuminate\Support\Facades\Auth;
use Throwable;

/**
 * Knows which products the current visitor has wishlisted.
 *
 * Bound as a singleton: the ids are fetched once per request instead of one
 * `exists` query per product card, which is what a listing page used to cost.
 */
class WishlistStateService
{
    /** @var array<int, true>|null */
    private ?array $productIds = null;

    public function has(int $productId): bool
    {
        return isset($this->productIds()[$productId]);
    }

    /** @return array<int, true> */
    private function productIds(): array
    {
        if ($this->productIds !== null) {
            return $this->productIds;
        }

        $customerId = $this->customerId();

        $ids = WishlistItem::query()
            ->when(
                $customerId,
                fn ($query) => $query->where('customer_id', $customerId),
                fn ($query) => $query->where('session_id', session()->getId())
            )
            ->pluck('product_id')
            ->all();

        return $this->productIds = array_fill_keys($ids, true);
    }

    public function flush(): void
    {
        $this->productIds = null;
    }

    public function customerId(): ?int
    {
        try {
            if (Auth::guard('customer')->check()) {
                return Auth::guard('customer')->id();
            }
        } catch (Throwable) {
            return null;
        }

        return null;
    }
}
