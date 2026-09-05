<?php

namespace App\Livewire\Site;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentMethodDisplay;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingCity;
use App\Services\Notifications\NotificationDispatcher;
use App\Models\StoreSetting;
use App\Services\CouponService;
use App\Services\FreeShippingService;
use App\Services\InvoiceService;
use App\Services\OrderPaymentService;
use App\Services\OrderShippingService;
use App\Services\PaymentSettingsService;
use App\Services\ProductPricingService;
use App\Services\StockService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;


class CheckoutPage extends Component
{
    use WithFileUploads;

    public string $customer_name = '';
    public string $customer_email = '';
    public string $customer_phone = '';

    public string $address = '';
    public string $city = '';
    public string $region = '';
    public string $notes = '';
    public ?int $customer_address_id = null;
    public bool $save_address_to_account = false;

    public string $new_address_label = '';
    public array $savedAddresses = [];
    public ?int $shipping_city_id = null;

    public string $payment_method = 'cash_on_delivery';

    public string $coupon_code = '';
    public ?string $applied_coupon_code = null;
    public float $coupon_discount_total = 0;
    public bool $coupon_free_shipping = false;

    public $payment_proof = null;

    public bool $orderPlaced = false;
    public ?string $orderNumber = null;

    public function mount(): void
    {
        $this->prefillCustomer();
        $this->syncDefaultPaymentMethod();
        $this->prefillCustomerData();
    }

    public function updatedPaymentMethod(): void
    {
        $this->payment_proof = null;
    }

    public function applyCoupon(): void
    {
        $cart = $this->cart();

        if (! $cart || ! $cart->items()->count()) {
            return;
        }

        $code = strtoupper(trim($this->coupon_code));

        if (! $code) {
            $this->dispatch(
                'site-toast',
                type: 'error',
                icon: '!',
                title: app()->getLocale() === 'ar' ? 'كود غير صحيح' : 'Invalid coupon',
                message: app()->getLocale() === 'ar'
                    ? 'من فضلك أدخل كود الخصم'
                    : 'Please enter a coupon code'
            );

            return;
        }

        $coupon = Coupon::query()
            ->where('code', $code)
            ->first();

        if (! $coupon || ! $coupon->isValidForAmount((float) $cart->subtotal)) {
            $this->dispatch(
                'site-toast',
                type: 'error',
                icon: '!',
                title: app()->getLocale() === 'ar' ? 'كوبون غير صالح' : 'Invalid coupon',
                message: app()->getLocale() === 'ar'
                    ? 'الكوبون غير صالح أو لا ينطبق على هذا الطلب'
                    : 'Coupon is invalid or not applicable to this order'
            );

            return;
        }

        $this->applied_coupon_code = $coupon->code;
        $this->coupon_discount_total = $coupon->calculateDiscount((float) $cart->subtotal);
        $this->coupon_free_shipping = (bool) $coupon->free_shipping;

        $this->dispatch(
            'site-toast',
            type: 'success',
            icon: '✓',
            title: app()->getLocale() === 'ar' ? 'تم تطبيق الكوبون' : 'Coupon applied',
            message: app()->getLocale() === 'ar'
                ? 'تم تطبيق كود الخصم بنجاح'
                : 'Coupon has been applied successfully'
        );
    }

    public function removeCoupon(): void
    {
        $this->coupon_code = '';
        $this->applied_coupon_code = null;
        $this->coupon_discount_total = 0;
        $this->coupon_free_shipping = false;
    }

    public function placeOrder(): void
    {
        $rules = [
            'customer_name' => ['required', 'string', 'max:190'],
            'customer_email' => ['nullable', 'email', 'max:190'],
            'customer_phone' => ['required', 'string', 'max:50'],
            'address' => ['required', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:190'],
            'region' => ['nullable', 'string', 'max:190'],
            'shipping_city_id' => ['required', 'integer', 'exists:shipping_cities,id'],
            'payment_method' => ['required', 'string', 'max:100'],
        ];

        if ($this->paymentRequiresProof()) {
            $rules['payment_proof'] = ['required', 'image', 'max:4096'];
        }

        $this->validate($rules, [
            'customer_name.required' => app()->getLocale() === 'ar' ? 'اسم العميل مطلوب' : 'Customer name is required',
            'customer_phone.required' => app()->getLocale() === 'ar' ? 'رقم الهاتف مطلوب' : 'Phone number is required',
            'address.required' => app()->getLocale() === 'ar' ? 'العنوان مطلوب' : 'Address is required',
            'city.required' => app()->getLocale() === 'ar' ? 'المدينة مطلوبة' : 'City is required',
            'shipping_city_id.required' => app()->getLocale() === 'ar' ? 'من فضلك اختر مدينة الشحن' : 'Please select a shipping city',
            'payment_proof.required' => app()->getLocale() === 'ar' ? 'صورة إيصال الدفع مطلوبة' : 'Payment proof is required',
            'payment_proof.image' => app()->getLocale() === 'ar' ? 'إيصال الدفع يجب أن يكون صورة' : 'Payment proof must be an image',
        ]);

        if (! app(PaymentSettingsService::class)->isPaymentMethodEnabled($this->payment_method)) {
            $this->dispatch(
                'site-toast',
                type: 'error',
                icon: '!',
                title: app()->getLocale() === 'ar' ? 'وسيلة دفع غير متاحة' : 'Payment unavailable',
                message: app()->getLocale() === 'ar'
                    ? 'وسيلة الدفع المختارة غير متاحة حاليًا'
                    : 'Selected payment method is currently unavailable'
            );

            return;
        }

        $cart = $this->cart();

        if (! $cart || ! $cart->items()->count()) {
            $this->dispatch(
                'site-toast',
                type: 'error',
                icon: '!',
                title: app()->getLocale() === 'ar' ? 'السلة فارغة' : 'Empty cart',
                message: app()->getLocale() === 'ar'
                    ? 'لا توجد منتجات في السلة'
                    : 'There are no items in your cart'
            );

            return;
        }

        try {
            DB::transaction(function () use ($cart) {
                $cart->loadMissing([
                    'items.product.transNow',
                    'items.product.arabicTranslation',
                    'items.product.englishTranslation',
                    'items.product.discounts',
                    'items.variant.product',
                    'items.variant.transNow',
                    'items.variant.arabicTranslation',
                    'items.variant.englishTranslation',
                    'items.variant.discounts',
                    'items.variant.attributeValues.attribute.transNow',
                    'items.variant.attributeValues.attribute.arabicTranslation',
                    'items.variant.attributeValues.attribute.englishTranslation',
                    'items.variant.attributeValues.attributeValue.transNow',
                    'items.variant.attributeValues.attributeValue.arabicTranslation',
                    'items.variant.attributeValues.attributeValue.englishTranslation',
                ]);

                $this->validateCartStock($cart);

                $pricingItems = $this->preparePricingItems($cart);

                $subtotal = collect($pricingItems)->sum('subtotal');

               $customer = $this->resolveCustomer();

$customerAddressId = $this->resolveCheckoutAddressId($customer);

$city = ShippingCity::query()->find($this->shipping_city_id);

                $orderNumber = $this->generateOrderNumber();

                $order = Order::query()->create([
                    'order_number' => $orderNumber,
                    'customer_id' => $customer->id,
                    'customer_address_id' => $customerAddressId,
                    'customer_name' => $this->customer_name,
                    'customer_email' => $this->customer_email ?: null,
                    'customer_phone' => $this->customer_phone,
                    'shipping_address_snapshot' => [
                        'name' => $this->customer_name,
                        'email' => $this->customer_email ?: null,
                        'phone' => $this->customer_phone,
                        'country' => 'Egypt',
                        'city' => $this->city,
                        'region' => $this->region ?: null,
                        'address' => $this->address,
                        'notes' => $this->notes ?: null,
                    ],
                    'status' => 'pending',
                    'payment_status' => 'unpaid',
                    'payment_method' => $this->payment_method,
                    'payment_fee' => 0,
                    'coupon_used_at' => null,
                    'subtotal' => $subtotal,
                    'discount_total' => 0,
                    'shipping_total' => 0,
                    'shipping_city_id' => $city?->id,
                    'shipping_zone_id' => $city?->shipping_zone_id,
                    'shipping_discount_source' => null,
                    'free_shipping_offer_id' => null,
                    'tax_total' => 0,
                    'grand_total' => $subtotal,
                    'coupon_code' => null,
                    'customer_notes' => $this->notes ?: null,
                    'admin_notes' => null,
                ]);

                foreach ($pricingItems as $pricingItem) {
                    $item = $pricingItem['item'];
                    $pricing = $pricingItem['pricing'];
                    $snapshot = $pricingItem['snapshot'];

                    $productSnapshot = $snapshot['product'] ?? [];
                    $variantSnapshot = $snapshot['variant'] ?? [];

                    $order->items()->create([
                        'product_id' => $item->product_id,
                        'product_variant_id' => $item->product_variant_id,
                        'product_name' => $productSnapshot['name'] ?? null,
                        'variant_name' => $this->variantDisplayName($variantSnapshot),
                        'sku' => $variantSnapshot['sku'] ?? $productSnapshot['sku'] ?? null,
                        'original_unit_price' => (float) $pricing['original_price'],
                        'discount_amount' => (float) $pricing['discount_amount'],
                        'discount_source' => $pricing['discount_source'],
                        'flash_sale_item_id' => $pricing['flash_sale_item_id'],
                        'flash_sale_counted_at' => null,
                        'product_discount_id' => $pricing['product_discount_id'],
                        'quantity' => (int) $item->quantity,
                        'unit_price' => (float) $pricing['final_price'],
                        'subtotal' => (int) $item->quantity * (float) $pricing['final_price'],
                        'snapshot' => $snapshot,
                    ]);
                }

                if ($this->applied_coupon_code) {
                    app(CouponService::class)->applyToOrder($order->fresh(), $this->applied_coupon_code);
                    app(CouponService::class)->markCouponAsUsedForOrder($order->fresh());
                }

                app(OrderShippingService::class)->applyShippingToOrder(
                    $order->fresh(),
                    $this->shipping_city_id
                );

                app(OrderPaymentService::class)->applyPaymentMethodToOrder(
                    $order->fresh(),
                    $this->payment_method
                );

                $order = $order->fresh();

                $paymentProofPath = null;

                if ($this->payment_proof) {
                    $paymentProofPath = $this->payment_proof->store('payment-proofs', 'public');
                }

                Payment::query()->create([
                    'order_id' => $order->id,
                    'method' => $this->payment_method,
                    'status' => $this->paymentRequiresProof() ? 'pending_review' : 'pending',
                    'amount' => (float) $order->grand_total,
                    'transaction_reference' => null,
                    'payment_proof' => $paymentProofPath,
                    'paid_at' => null,
                    'notes' => null,
                ]);

                try {
                    $invoice = app(InvoiceService::class)->createForOrder($order->fresh());
                } catch (\Throwable $e) {
                    report($e);
                }

                $cart->update([
                    'status' => 'converted',
                    'last_activity_at' => now(),
                ]);

                session()->put('last_order_number', $orderNumber);

                $this->orderPlaced = true;
                $this->orderNumber = $orderNumber;

                $this->dispatch('cart-updated');
            });

            $this->dispatch(
                'site-toast',
                type: 'success',
                icon: '✓',
                title: app()->getLocale() === 'ar' ? 'تم إنشاء الطلب' : 'Order placed',
                message: app()->getLocale() === 'ar'
                    ? 'تم إنشاء طلبك بنجاح'
                    : 'Your order has been placed successfully'
            );
            $createdOrder = Order::query()
                ->where('order_number', $this->orderNumber)
                ->first();

            if ($createdOrder) {
                app(NotificationDispatcher::class)->adminNewOrder($createdOrder);
                app(NotificationDispatcher::class)->customerNewOrder($createdOrder);

                $latestInvoice = $createdOrder->invoices()->latest('id')->first();

                if ($latestInvoice) {
                    app(NotificationDispatcher::class)->customerInvoiceCreated($latestInvoice);
                }

                if ($createdOrder->payments()->whereNotNull('payment_proof')->exists()) {
                    app(NotificationDispatcher::class)->adminNewPayment($createdOrder);
                }
            }

            $this->redirectRoute('site.orders.show', [
                'orderNumber' => $this->orderNumber,
            ]);
        } catch (\Throwable $e) {
            report($e);

            $this->dispatch(
                'site-toast',
                type: 'error',
                icon: '!',
                title: app()->getLocale() === 'ar' ? 'لم يتم إنشاء الطلب' : 'Order failed',
                message: $e->getMessage() ?: (
                    app()->getLocale() === 'ar'
                    ? 'حدث خطأ أثناء إنشاء الطلب'
                    : 'Something went wrong while placing the order'
                )
            );
        }
    }

    public function paymentRequiresProof(): bool
    {
        return app(PaymentSettingsService::class)
            ->requiresPaymentProof($this->payment_method);
    }

    public function checkoutTotals(): array
    {
        $cart = $this->cart();

        $subtotal = $cart ? (float) $cart->subtotal : 0;

        $couponDiscount = $this->currentCouponDiscount($subtotal);

        $shippingTotal = $this->calculatedShippingTotal($subtotal);

        $paymentFee = 0;

        if ($this->payment_method === 'cash_on_delivery') {
            $paymentFee = app(PaymentSettingsService::class)->cashOnDeliveryFee();
        }

        $taxTotal = 0;

        $grandTotal = max(
            $subtotal - $couponDiscount + $shippingTotal + $paymentFee + $taxTotal,
            0
        );

        return [
            'subtotal' => $subtotal,
            'discount_total' => $couponDiscount,
            'shipping_total' => $shippingTotal,
            'payment_fee' => $paymentFee,
            'tax_total' => $taxTotal,
            'grand_total' => $grandTotal,
        ];
    }

    private function currentCouponDiscount(float $subtotal): float
    {
        if (! $this->applied_coupon_code) {
            return 0;
        }

        $coupon = Coupon::query()
            ->where('code', strtoupper(trim($this->applied_coupon_code)))
            ->first();

        if (! $coupon || ! $coupon->isValidForAmount($subtotal)) {
            return 0;
        }

        $this->coupon_free_shipping = (bool) $coupon->free_shipping;

        return $coupon->calculateDiscount($subtotal);
    }

    private function calculatedShippingTotal(float $subtotal): float
    {
        if (! $this->shipping_city_id) {
            return 0;
        }

        $settings = StoreSetting::current();

        if (! $settings->shipping_enabled) {
            return 0;
        }

        $city = ShippingCity::query()
            ->with('zone')
            ->find($this->shipping_city_id);

        if (! $city) {
            return 0;
        }

        $shippingFee = $city->calculateDeliveryFee($subtotal);

        if (
            $settings->global_free_shipping_enabled
            && (
                $settings->global_free_shipping_minimum === null
                || $subtotal >= (float) $settings->global_free_shipping_minimum
            )
        ) {
            return 0;
        }

        if ($this->coupon_free_shipping) {
            return 0;
        }

        $freeShippingOffer = app(FreeShippingService::class)->getValidOffer(
            orderTotal: $subtotal,
            shippingCityId: $city->id,
            shippingZoneId: $city->shipping_zone_id
        );

        if ($freeShippingOffer) {
            return 0;
        }

        return (float) $shippingFee;
    }

    private function validateCartStock(Cart $cart): void
    {
        foreach ($cart->items as $item) {
            $product = $item->product;
            $variant = $item->variant;

            if (! $product || ! $product->is_active) {
                throw new \Exception(
                    app()->getLocale() === 'ar'
                        ? 'يوجد منتج غير متاح في السلة'
                        : 'There is an unavailable product in your cart'
                );
            }

            if ($variant && ! $variant->is_active) {
                throw new \Exception(
                    app()->getLocale() === 'ar'
                        ? 'يوجد اختيار غير متاح في السلة'
                        : 'There is an unavailable variant in your cart'
                );
            }

            $canAdd = app(StockService::class)->canAddToCart(
                product: $product,
                variant: $variant,
                requestedQuantity: (int) $item->quantity,
                currentCartQuantity: 0
            );

            if (! $canAdd) {
                throw new \Exception(
                    app()->getLocale() === 'ar'
                        ? 'بعض الكميات في السلة غير متوفرة حاليًا'
                        : 'Some quantities in your cart are not available'
                );
            }
        }
    }

    private function preparePricingItems(Cart $cart): array
    {
        $items = [];

        foreach ($cart->items as $item) {
            $product = $item->product;
            $variant = $item->variant;

            $pricing = $variant
                ? app(ProductPricingService::class)->getVariantPrice($variant)
                : app(ProductPricingService::class)->getProductPrice($product);

            $snapshot = $this->itemSnapshot(
                product: $product,
                variant: $variant,
                pricing: $pricing,
                quantity: (int) $item->quantity
            );

            $items[] = [
                'item' => $item,
                'pricing' => $pricing,
                'snapshot' => $snapshot,
                'subtotal' => (int) $item->quantity * (float) $pricing['final_price'],
            ];
        }

        return $items;
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

    private function variantDisplayName(?array $variantSnapshot): ?string
    {
        if (! $variantSnapshot) {
            return null;
        }

        if (! empty($variantSnapshot['name'])) {
            return $variantSnapshot['name'];
        }

        $attributes = $variantSnapshot['attributes'] ?? [];

        if (! $attributes) {
            return null;
        }

        return collect($attributes)
            ->map(function ($attribute) {
                return trim(($attribute['attribute_name'] ?? '') . ': ' . ($attribute['value_name'] ?? ''));
            })
            ->filter()
            ->implode(' / ');
    }

    private function resolveCustomer(): Customer
    {
        $name = trim($this->customer_name);
        $email = filled($this->customer_email)
            ? mb_strtolower(trim($this->customer_email))
            : null;

        $phone = filled($this->customer_phone)
            ? trim($this->customer_phone)
            : null;

        try {
            $authCustomerId = auth('customer')->id();

            if ($authCustomerId) {
                $customer = Customer::query()->find($authCustomerId);

                if ($customer) {
                    $data = [
                        'name' => $name ?: $customer->name,
                    ];

                    if ($email && $this->canUseCustomerEmail($email, $customer->id)) {
                        $data['email'] = $email;
                    }

                    if ($phone && $this->canUseCustomerPhone($phone, $customer->id)) {
                        $data['phone'] = $phone;
                    }

                    $customer->update($data);

                    return $customer;
                }
            }
        } catch (\Throwable $e) {
            //
        }

        $customerByEmail = $email
            ? Customer::query()->where('email', $email)->first()
            : null;

        $customerByPhone = $phone
            ? Customer::query()->where('phone', $phone)->first()
            : null;


        $customer = $customerByEmail ?: $customerByPhone;

        if ($customer) {
            $data = [
                'name' => $name ?: $customer->name,
            ];

            if ($email && $this->canUseCustomerEmail($email, $customer->id)) {
                $data['email'] = $email;
            }

            if ($phone && $this->canUseCustomerPhone($phone, $customer->id)) {
                $data['phone'] = $phone;
            }

            $customer->update($data);

            return $customer;
        }

        return Customer::query()->create([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'password' => bcrypt(Str::random(32)),
            'is_active' => true,
            'accepts_marketing' => false,
        ]);
    }
    private function canUseCustomerEmail(string $email, ?int $ignoreCustomerId = null): bool
    {
        return ! Customer::query()
            ->where('email', $email)
            ->when($ignoreCustomerId, fn($query) => $query->whereKeyNot($ignoreCustomerId))
            ->exists();
    }

    private function canUseCustomerPhone(string $phone, ?int $ignoreCustomerId = null): bool
    {
        return ! Customer::query()
            ->where('phone', $phone)
            ->when($ignoreCustomerId, fn($query) => $query->whereKeyNot($ignoreCustomerId))
            ->exists();
    }

   private function resolveCheckoutAddressId(Customer $customer): ?int
{
    if ($this->customer_address_id) {
        $addressId = CustomerAddress::query()
            ->where('customer_id', $customer->id)
            ->whereKey($this->customer_address_id)
            ->value('id');

        if ($addressId) {
            return $addressId;
        }
    }

    try {
        $isLoggedCustomer = auth('customer')->check();
    } catch (\Throwable $e) {
        $isLoggedCustomer = false;
    }

    if ($isLoggedCustomer && ! $this->save_address_to_account) {
        return null;
    }

    return $this->createCheckoutAddress($customer)->id;
}

private function createCheckoutAddress(Customer $customer): CustomerAddress
{
    $hasDefaultAddress = $customer->addresses()
        ->where('is_default', true)
        ->exists();

    return CustomerAddress::query()->create([
        'customer_id' => $customer->id,
        'label' => trim($this->new_address_label) ?: (
            app()->getLocale() === 'ar' ? 'عنوان الطلب' : 'Checkout Address'
        ),
        'name' => $this->customer_name,
        'phone' => $this->customer_phone,
        'country' => 'Egypt',
        'city' => $this->city,
        'area' => $this->region ?: null,
        'street' => $this->address,
        'building' => null,
        'floor' => null,
        'apartment' => null,
        'landmark' => null,
        'notes' => $this->notes ?: null,
        'latitude' => null,
        'longitude' => null,
        'is_default' => ! $hasDefaultAddress,
    ]);
}

    private function generateOrderNumber(): string
    {
        do {
            $orderNumber = 'ORD-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
        } while (Order::query()->where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }

    private function cart(): ?Cart
    {
        $sessionId = session()->getId();

        return Cart::query()
            ->where('session_id', $sessionId)
            ->where('customer_id', $this->customerId())
            ->where('status', 'active')
            ->with([
                'items.product.transNow',
                'items.product.arabicTranslation',
                'items.product.englishTranslation',
                'items.product.discounts',
                'items.variant.product',
                'items.variant.transNow',
                'items.variant.arabicTranslation',
                'items.variant.englishTranslation',
                'items.variant.discounts',
                'items.variant.attributeValues.attribute.transNow',
                'items.variant.attributeValues.attribute.arabicTranslation',
                'items.variant.attributeValues.attribute.englishTranslation',
                'items.variant.attributeValues.attributeValue.transNow',
                'items.variant.attributeValues.attributeValue.arabicTranslation',
                'items.variant.attributeValues.attributeValue.englishTranslation',
            ])
            ->first();
    }

    private function customerId(): ?int
    {
        try {
            return auth('customer')->id();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function prefillCustomer(): void
    {
        try {
            $customer = auth('customer')->user();

            if (! $customer) {
                return;
            }

            $this->customer_name = $customer->name ?? '';
            $this->customer_email = $customer->email ?? '';
            $this->customer_phone = $customer->phone ?? '';
        } catch (\Throwable $e) {
            //
        }
    }

    private function syncDefaultPaymentMethod(): void
    {
        $methods = $this->paymentMethods();

        if ($methods->count() && ! $methods->contains('key', $this->payment_method)) {
            $this->payment_method = $methods->first()['key'];
        }
    }

    public function paymentMethods(): Collection
    {
        $enabledMethods = app(PaymentSettingsService::class)->availablePaymentMethods();

        $displayMethods = PaymentMethodDisplay::query()
            ->where('is_active', true)
            ->whereIn('key', array_keys($enabledMethods))
            ->orderBy('sort_order')
            ->get()
            ->keyBy('key');

        return collect($enabledMethods)
            ->map(function ($label, $key) use ($displayMethods) {
                $display = $displayMethods->get($key);

                return [
                    'key' => $key,
                    'name' => $display?->name ?? $label,
                    'image' => $display?->image,
                    'icon' => $display?->icon,
                ];
            })
            ->values();
    }

    public function shippingCities(): Collection
    {
        return ShippingCity::query()
            ->where('is_active', true)
            ->with('zone')
            ->orderBy('sort_order')
            ->get();
    }
    public function selectedPaymentDetails(): array
    {
        $settings = StoreSetting::current();

        $locale = app()->getLocale();

        $paymentInstructions = $locale === 'ar'
            ? $settings->payment_instructions_ar
            : $settings->payment_instructions_en;

        return match ($this->payment_method) {
            'bank_transfer' => [
                'title' => $locale === 'ar' ? 'بيانات التحويل البنكي' : 'Bank Transfer Details',
                'details' => $locale === 'ar'
                    ? $settings->bank_account_details_ar
                    : $settings->bank_account_details_en,
                'instructions' => $paymentInstructions,
                'requires_proof' => $this->paymentRequiresProof(),
            ],

            'wallet_transfer' => [
                'title' => $locale === 'ar' ? 'بيانات تحويل المحفظة' : 'Wallet Transfer Details',
                'details' => $locale === 'ar'
                    ? $settings->wallet_details_ar
                    : $settings->wallet_details_en,
                'instructions' => $paymentInstructions,
                'requires_proof' => $this->paymentRequiresProof(),
            ],

            'cash_on_delivery' => [
                'title' => $locale === 'ar' ? 'الدفع عند الاستلام' : 'Cash on Delivery',
                'details' => (float) $settings->cash_on_delivery_fee > 0
                    ? (
                        $locale === 'ar'
                        ? 'يتم إضافة رسوم دفع عند الاستلام بقيمة ' . number_format((float) $settings->cash_on_delivery_fee, 2) . ' ' . ($settings->currency_symbol ?? 'EGP')
                        : 'A cash on delivery fee of ' . number_format((float) $settings->cash_on_delivery_fee, 2) . ' ' . ($settings->currency_symbol ?? 'EGP') . ' will be added.'
                    )
                    : null,
                'instructions' => $paymentInstructions,
                'requires_proof' => false,
            ],

            default => [
                'title' => null,
                'details' => null,
                'instructions' => $paymentInstructions,
                'requires_proof' => false,
            ],
        };
    }
    private function prefillCustomerData(): void
    {
        try {
            $customer = auth('customer')->user();
        } catch (\Throwable $e) {
            $customer = null;
        }

        if (! $customer) {
            return;
        }

        if (property_exists($this, 'customer_name')) {
            $this->customer_name = $this->customer_name ?: (string) $customer->name;
        }

        if (property_exists($this, 'customer_email')) {
            $this->customer_email = $this->customer_email ?: (string) $customer->email;
        }

        if (property_exists($this, 'customer_phone')) {
            $this->customer_phone = $this->customer_phone ?: (string) $customer->phone;
        }

        $this->savedAddresses = CustomerAddress::query()
            ->where('customer_id', $customer->id)
            ->orderByDesc('is_default')
            ->latest()
            ->get()
            ->map(fn(CustomerAddress $address) => [
                'id' => $address->id,
                'label' => $address->label ?: (app()->getLocale() === 'ar' ? 'عنوان' : 'Address'),
                'name' => $address->name,
                'phone' => $address->phone,
                'country' => $address->country,
                'city' => $address->city,
                'area' => $address->area,
                'street' => $address->street,
                'building' => $address->building,
                'floor' => $address->floor,
                'apartment' => $address->apartment,
                'landmark' => $address->landmark,
                'notes' => $address->notes,
                'is_default' => (bool) $address->is_default,
                'display' => collect([
                    $address->label,
                    $address->city,
                    $address->area,
                    $address->street,
                ])->filter()->implode(' - '),
            ])
            ->toArray();

        $defaultAddress = collect($this->savedAddresses)
            ->firstWhere('is_default', true);

        if ($defaultAddress) {
            $this->customer_address_id = $defaultAddress['id'];
            $this->applySavedAddress($defaultAddress['id']);
        }
    }

    public function updatedCustomerAddressId($value): void
    {
        if ($value) {
            $this->applySavedAddress((int) $value);
        }
    }

    public function applySavedAddress(int $addressId): void
    {
        $address = collect($this->savedAddresses)
            ->firstWhere('id', $addressId);

        if (! $address) {
            return;
        }

        if (property_exists($this, 'customer_name') && ! empty($address['name'])) {
            $this->customer_name = $address['name'];
        }

        if (property_exists($this, 'customer_phone') && ! empty($address['phone'])) {
            $this->customer_phone = $address['phone'];
        }
$this->city = $address['city'] ?? $this->city;
$this->region = $address['area'] ?? $this->region;
$this->save_address_to_account = false;
$this->new_address_label = '';
        if (property_exists($this, 'shipping_address')) {
            $this->shipping_address = $this->formatSavedAddress($address);
        }

        if (property_exists($this, 'address')) {
            $this->address = $this->formatSavedAddress($address);
        }

        if (property_exists($this, 'notes') && ! empty($address['notes'])) {
            $this->notes = $address['notes'];
        }

        if (property_exists($this, 'customer_notes') && ! empty($address['notes'])) {
            $this->customer_notes = $address['notes'];
        }
    }

    private function formatSavedAddress(array $address): string
    {
        return collect([
            $address['country'] ?? null,
            $address['city'] ?? null,
            $address['area'] ?? null,
            $address['street'] ?? null,
            ! empty($address['building']) ? (app()->getLocale() === 'ar' ? 'مبنى ' : 'Building ') . $address['building'] : null,
            ! empty($address['floor']) ? (app()->getLocale() === 'ar' ? 'دور ' : 'Floor ') . $address['floor'] : null,
            ! empty($address['apartment']) ? (app()->getLocale() === 'ar' ? 'شقة ' : 'Apartment ') . $address['apartment'] : null,
            $address['landmark'] ?? null,
        ])->filter()->implode(' - ');
    }



private function checkoutAddressText(): ?string
{
    if (property_exists($this, 'shipping_address') && filled($this->shipping_address)) {
        return trim($this->shipping_address);
    }

    if (property_exists($this, 'address') && filled($this->address)) {
        return trim($this->address);
    }

    if (property_exists($this, 'customer_address') && filled($this->customer_address)) {
        return trim($this->customer_address);
    }

    return null;
}

private function checkoutCityName(): string
{
    if (property_exists($this, 'shipping_city_id') && filled($this->shipping_city_id)) {
        $city = ShippingCity::query()->find($this->shipping_city_id);

        if ($city) {
            return $city->name_ar
                ?? $city->name_en
                ?? $city->name
                ?? 'غير محدد';
        }
    }

    if (property_exists($this, 'city') && filled($this->city)) {
        return trim($this->city);
    }

    return app()->getLocale() === 'ar' ? 'غير محدد' : 'Not specified';
}

private function checkoutCustomerName($customer): string
{
    if (property_exists($this, 'customer_name') && filled($this->customer_name)) {
        return trim($this->customer_name);
    }

    if (property_exists($this, 'name') && filled($this->name)) {
        return trim($this->name);
    }

    return $customer->name;
}

private function checkoutCustomerPhone($customer): string
{
    if (property_exists($this, 'customer_phone') && filled($this->customer_phone)) {
        return trim($this->customer_phone);
    }

    if (property_exists($this, 'phone') && filled($this->phone)) {
        return trim($this->phone);
    }

    return $customer->phone;
}

private function checkoutNotes(): ?string
{
    if (property_exists($this, 'customer_notes') && filled($this->customer_notes)) {
        return trim($this->customer_notes);
    }

    if (property_exists($this, 'notes') && filled($this->notes)) {
        return trim($this->notes);
    }

    return null;
}
    public function render()
    {
        $cart = $this->cart();

        $items = $cart?->items ?? collect();

        return view('livewire.site.checkout-page', [
            'cart' => $cart,
            'items' => $items,
            'paymentMethods' => $this->paymentMethods(),
            'shippingCities' => $this->shippingCities(),
            'totals' => $this->checkoutTotals(),
            'paymentDetails' => $this->selectedPaymentDetails(),
        ]);
    }
}