<?php

namespace Database\Seeders;

use App\Models\Coupon;
use App\Models\Customer;
use App\Models\FlashSale;
use App\Models\FreeShippingOffer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingCity;
use App\Services\CouponService;
use App\Services\InvoiceService;
use App\Services\OrderShippingService;
use App\Services\ProductPricingService;
use App\Services\StockService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Sample trading data: coupons, promotions, customers and a week of orders.
 *
 * Money and stock are produced by the same services the storefront and the
 * dashboard use (pricing, shipping, coupons, stock), so the numbers here are
 * the application's own - not a second implementation that could drift.
 *
 * Re-runnable: it clears the demo rows it created before inserting again.
 */
class ElleDemoDataSeeder extends Seeder
{
    /** Orders are spread over this many days so the 7-day sales chart fills up. */
    private const DAYS = 7;

    private const DEMO_PHONE_PREFIX = '0109000';

    public function run(): void
    {
        DB::transaction(function (): void {
            $this->clearPreviousDemoData();

            $coupons = $this->createCoupons();
            $this->createPromotions();
            $customers = $this->createCustomers();

            $this->createOrders($customers, $coupons);
        });

        $this->command?->newLine();
        $this->command?->info('Demo data ready.');
    }

    /** Remove anything a previous run of this seeder created. */
    private function clearPreviousDemoData(): void
    {
        $demoOrderIds = Order::query()
            ->where('customer_phone', 'like', self::DEMO_PHONE_PREFIX . '%')
            ->pluck('id');

        if ($demoOrderIds->isNotEmpty()) {
            // Put back any stock those orders had taken.
            Order::query()
                ->whereIn('id', $demoOrderIds)
                ->whereNotNull('stock_deducted_at')
                ->get()
                ->each(fn (Order $order) => app(StockService::class)
                    ->restoreOrderStock($order, 'demo_data_reset'));

            DB::table('stock_movements')->whereIn('order_id', $demoOrderIds)->delete();
            DB::table('order_status_histories')->whereIn('order_id', $demoOrderIds)->delete();
            DB::table('payments')->whereIn('order_id', $demoOrderIds)->delete();
            DB::table('invoices')->whereIn('order_id', $demoOrderIds)->delete();
            DB::table('shipments')->whereIn('order_id', $demoOrderIds)->delete();
            DB::table('order_items')->whereIn('order_id', $demoOrderIds)->delete();
            Order::query()->whereIn('id', $demoOrderIds)->forceDelete();
        }

        Customer::query()->where('phone', 'like', self::DEMO_PHONE_PREFIX . '%')->forceDelete();

        Coupon::query()->whereIn('code', array_keys($this->couponDefinitions()))->forceDelete();
        FlashSale::query()->where('name_en', 'like', 'Demo%')->forceDelete();
        FreeShippingOffer::query()->where('name_en', 'like', 'Demo%')->forceDelete();
    }

    /** @return array<string, array<string, mixed>> */
    private function couponDefinitions(): array
    {
        return [
            'ELLE10' => [
                'name_ar' => 'خصم ١٠٪', 'name_en' => '10% off',
                'type' => 'percentage', 'value' => 10,
                'minimum_order_amount' => 0,
                'maximum_discount_amount' => null,
                'free_shipping' => false,
                'usage_limit' => 500,
            ],
            'ELLE20' => [
                'name_ar' => 'خصم ٢٠٪ بحد أقصى ٢٠٠', 'name_en' => '20% off, max 200',
                'type' => 'percentage', 'value' => 20,
                'minimum_order_amount' => 1000,
                'maximum_discount_amount' => 200,
                'free_shipping' => false,
                'usage_limit' => 200,
            ],
            'ELLE100' => [
                'name_ar' => 'خصم ١٠٠ جنيه', 'name_en' => '100 EGP off',
                'type' => 'fixed', 'value' => 100,
                'minimum_order_amount' => 700,
                'maximum_discount_amount' => null,
                'free_shipping' => false,
                'usage_limit' => 300,
            ],
            'SHIPFREE' => [
                'name_ar' => 'شحن مجاني', 'name_en' => 'Free shipping',
                'type' => 'fixed', 'value' => 0,
                'minimum_order_amount' => 500,
                'maximum_discount_amount' => null,
                'free_shipping' => true,
                'usage_limit' => 300,
            ],
        ];
    }

    /** @return array<string, Coupon> */
    private function createCoupons(): array
    {
        $coupons = [];

        foreach ($this->couponDefinitions() as $code => $definition) {
            $coupons[$code] = Coupon::query()->create($definition + [
                'code' => $code,
                'usage_limit_per_customer' => null,
                'used_count' => 0,
                'is_active' => true,
                'starts_at' => now()->subMonth(),
                'expires_at' => now()->addMonths(3),
            ]);
        }

        $this->command?->info('Coupons: ' . implode(', ', array_keys($coupons)));

        return $coupons;
    }

    private function createPromotions(): void
    {
        $flashSale = FlashSale::query()->create([
            'name_ar' => 'عروض نهاية الأسبوع',
            'name_en' => 'Demo weekend flash sale',
            'starts_at' => now()->subDays(2),
            'ends_at' => now()->addDays(5),
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Product::query()
            ->where('is_active', true)
            ->inRandomOrder()
            ->limit(4)
            ->get()
            ->each(function (Product $product) use ($flashSale): void {
                $flashSale->items()->create([
                    'product_id' => $product->id,
                    'discount_type' => 'percentage',
                    'discount_value' => 20,
                    'quantity_limit' => 25,
                    'sold_count' => 0,
                    'is_active' => true,
                ]);
            });

        FreeShippingOffer::query()->create([
            'name_ar' => 'شحن مجاني فوق ١٥٠٠ جنيه',
            'name_en' => 'Demo free shipping over 1500',
            'minimum_order_amount' => 1500,
            'starts_at' => now()->subWeek(),
            'ends_at' => now()->addMonth(),
            'is_active' => true,
            'priority' => 1,
        ]);

        $this->command?->info('Promotions: 1 flash sale (4 products), 1 free shipping offer');
    }

    /** @return array<int, Customer> */
    private function createCustomers(): array
    {
        $names = [
            'سارة محمد', 'نورهان أحمد', 'مريم علي', 'هبة خالد',
            'أميرة سيد', 'دينا مصطفى', 'ياسمين طارق', 'رانيا حسن',
        ];

        $customers = [];

        foreach ($names as $index => $name) {
            $customers[] = Customer::query()->create([
                'name' => $name,
                'email' => 'demo' . ($index + 1) . '@elle-demo.test',
                'phone' => self::DEMO_PHONE_PREFIX . str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                'password' => bcrypt('Passw0rd!23'),
                'is_active' => true,
                'created_at' => now()->subDays(random_int(7, 60)),
            ]);
        }

        $this->command?->info('Customers: ' . count($customers));

        return $customers;
    }

    /**
     * @param  array<int, Customer>  $customers
     * @param  array<string, Coupon>  $coupons
     */
    private function createOrders(array $customers, array $coupons): void
    {
        $cities = ShippingCity::query()->where('is_active', true)->get();

        $products = Product::query()
            ->where('is_active', true)
            ->with(['variants' => fn ($q) => $q->where('is_active', true), 'transNow', 'arabicTranslation'])
            ->get()
            ->filter(fn (Product $p) => $p->variants->isNotEmpty())
            ->values();

        // status => how many orders, spread across the last DAYS days
        $plan = [
            'delivered' => 9,
            'shipped' => 3,
            'processing' => 3,
            'confirmed' => 3,
            'pending' => 4,
            'cancelled' => 2,
        ];

        $couponCodes = ['ELLE10', 'ELLE20', 'ELLE100', 'SHIPFREE', null, null];

        $created = 0;
        $bar = $this->command?->getOutput()->createProgressBar(array_sum($plan));
        $bar?->start();

        foreach ($plan as $status => $count) {
            for ($i = 0; $i < $count; $i++) {
                $placedAt = now()
                    ->subDays(random_int(0, self::DAYS - 1))
                    ->setTime(random_int(9, 22), random_int(0, 59));

                $this->createOrder(
                    customer: $customers[array_rand($customers)],
                    products: $products,
                    city: $cities->random(),
                    couponCode: $couponCodes[array_rand($couponCodes)],
                    status: $status,
                    placedAt: $placedAt,
                );

                $created++;
                $bar?->advance();
            }
        }

        $bar?->finish();

        $this->command?->newLine();
        $this->command?->info("Orders: {$created}");
    }

    private function createOrder(
        Customer $customer,
        $products,
        ShippingCity $city,
        ?string $couponCode,
        string $status,
        \Illuminate\Support\Carbon $placedAt,
    ): void {
        $order = Order::query()->create([
            'order_number' => 'ORD-' . $placedAt->format('Ymd') . '-' . strtoupper(Str::random(6)),
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'customer_phone' => $customer->phone,
            'shipping_address_snapshot' => [
                'address' => 'عنوان تجريبي، ' . $city->name_ar,
                'city' => $city->name_ar,
            ],
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'payment_method' => 'cash_on_delivery',
            'subtotal' => 0,
            'discount_total' => 0,
            'shipping_total' => 0,
            'tax_total' => 0,
            'grand_total' => 0,
            'created_at' => $placedAt,
            'updated_at' => $placedAt,
        ]);

        // ---- line items, priced by the app's own pricing service ----------
        $subtotal = 0.0;

        foreach ($products->random(random_int(1, 3)) as $product) {
            $variant = $product->variants->random();
            $quantity = random_int(1, 3);

            $pricing = app(ProductPricingService::class)->getVariantPrice($variant);
            $unitPrice = (float) $pricing['final_price'];
            $lineTotal = $unitPrice * $quantity;

            $translation = $product->transNow ?? $product->arabicTranslation;

            $order->items()->create([
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'product_name' => $translation?->name ?? $product->sku,
                'variant_name' => $variant->transNow?->name,
                'sku' => $variant->sku ?? $product->sku,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'subtotal' => $lineTotal,
                'created_at' => $placedAt,
                'updated_at' => $placedAt,
            ]);

            $subtotal += $lineTotal;
        }

        $order->update(['subtotal' => $subtotal, 'grand_total' => $subtotal]);

        // ---- shipping and coupon, via the services the dashboard uses -----
        app(OrderShippingService::class)->applyShippingToOrder($order->fresh(), $city->id);

        if ($couponCode) {
            try {
                app(CouponService::class)->applyToOrder($order->fresh(), $couponCode);
                app(CouponService::class)->markCouponAsUsedForOrder($order->fresh());
            } catch (\Throwable) {
                // Coupon rules (minimum order value) legitimately reject some
                // baskets - leave that order without a discount.
            }
        }

        $order = $order->fresh();

        // ---- status, stock and paperwork ---------------------------------
        $timestamps = ['updated_at' => $placedAt];

        if (in_array($status, ['confirmed', 'processing', 'shipped', 'delivered'], true)) {
            $timestamps['confirmed_at'] = $placedAt;
        }

        if (in_array($status, ['shipped', 'delivered'], true)) {
            $timestamps['shipped_at'] = (clone $placedAt)->addDay();
        }

        if ($status === 'delivered') {
            $timestamps['delivered_at'] = (clone $placedAt)->addDays(2);
            $timestamps['payment_status'] = 'paid';
        }

        if ($status === 'cancelled') {
            $timestamps['cancelled_at'] = (clone $placedAt)->addHours(3);
        }

        $order->update(['status' => $status] + $timestamps);

        // Stock leaves the warehouse from "confirmed" onwards - the same rule
        // the dashboard applies when the owner changes an order's status.
        if (in_array($status, ['confirmed', 'processing', 'shipped', 'delivered'], true)) {
            app(StockService::class)->deductOrderStock($order->fresh());
        }

        $order->statusHistories()->create([
            'from_status' => 'pending',
            'to_status' => $status,
            'note' => 'Demo data',
            'created_at' => $placedAt,
            'updated_at' => $placedAt,
        ]);

        $order->payments()->create([
            'method' => 'cash_on_delivery',
            'status' => $status === 'delivered' ? 'paid' : 'pending',
            'amount' => (float) $order->fresh()->grand_total,
            'paid_at' => $status === 'delivered' ? (clone $placedAt)->addDays(2) : null,
            'created_at' => $placedAt,
            'updated_at' => $placedAt,
        ]);

        try {
            app(InvoiceService::class)->createForOrder($order->fresh());
        } catch (\Throwable) {
            // Invoicing is optional for demo rows.
        }

        // Keep the created_at we asked for; services above touch updated_at.
        DB::table('orders')->where('id', $order->id)->update([
            'created_at' => $placedAt,
        ]);
    }
}
