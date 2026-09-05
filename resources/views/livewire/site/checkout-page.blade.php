@php
    $currency = $storeSettings->currency_symbol ?? ($storeSettings->currency_code ?? 'EGP');

    $subtotal = (float) ($totals['subtotal'] ?? 0);
    $discountTotal = (float) ($totals['discount_total'] ?? 0);
    $shippingTotal = (float) ($totals['shipping_total'] ?? 0);
    $paymentFee = (float) ($totals['payment_fee'] ?? 0);
    $taxTotal = (float) ($totals['tax_total'] ?? 0);
    $grandTotal = (float) ($totals['grand_total'] ?? 0);
@endphp

<section class="checkout-page">
    <div class="site-container">
        @if ($orderPlaced)
            <div class="checkout-success">
                <div>
                    <svg class="h-14 w-14" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                </div>

                <h1>
                    {{ app()->getLocale() === 'ar' ? 'تم إنشاء الطلب بنجاح' : 'Order placed successfully' }}
                </h1>

                <p>
                    {{ app()->getLocale() === 'ar' ? 'رقم الطلب:' : 'Order number:' }}
                    <strong>{{ $orderNumber }}</strong>
                </p>

                <a href="{{ route('site.shop') }}">
                    {{ app()->getLocale() === 'ar' ? 'متابعة التسوق' : 'Continue shopping' }}
                </a>
            </div>
        @elseif (!$items->count())
            <div class="checkout-empty">
                <h1>
                    {{ app()->getLocale() === 'ar' ? 'لا توجد منتجات لإتمام الطلب' : 'No items to checkout' }}
                </h1>

                <p>
                    {{ app()->getLocale() === 'ar'
                        ? 'السلة فارغة، ابدأ بإضافة منتجات أولًا.'
                        : 'Your cart is empty, start by adding products first.' }}
                </p>

                <a href="{{ route('site.shop') }}">
                    {{ app()->getLocale() === 'ar' ? 'اذهب للمتجر' : 'Go to shop' }}
                </a>
            </div>
        @else
            <div class="checkout-head">
                <div>
                    <div class="checkout-breadcrumb">
                        <a href="{{ route('site.home') }}">
                            {{ app()->getLocale() === 'ar' ? 'الرئيسية' : 'Home' }}
                        </a>

                        <span>/</span>

                        <a href="{{ route('site.cart') }}">
                            {{ app()->getLocale() === 'ar' ? 'السلة' : 'Cart' }}
                        </a>

                        <span>/</span>

                        <span>
                            {{ app()->getLocale() === 'ar' ? 'إتمام الطلب' : 'Checkout' }}
                        </span>
                    </div>

                    <h1>
                        {{ app()->getLocale() === 'ar' ? 'إتمام الطلب' : 'Checkout' }}
                    </h1>

                    <p>
                        {{ app()->getLocale() === 'ar'
                            ? 'أدخل بياناتك لإرسال الطلب ومتابعته.'
                            : 'Enter your details to place and track your order.' }}
                    </p>
                </div>
            </div>

            <div class="checkout-layout">
                <div class="checkout-form-card">
                    <h2>
                        {{ app()->getLocale() === 'ar' ? 'بيانات العميل' : 'Customer Details' }}
                    </h2>

                    <div class="checkout-form-grid">
                        <div class="checkout-field">
                            <label>
                                {{ app()->getLocale() === 'ar' ? 'الاسم' : 'Name' }}
                            </label>

                            <input type="text" wire:model.defer="customer_name">
                            @error('customer_name')
                                <span>{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="checkout-field">
                            <label>
                                {{ app()->getLocale() === 'ar' ? 'رقم الهاتف' : 'Phone' }}
                            </label>

                            <input type="text" wire:model.defer="customer_phone">
                            @error('customer_phone')
                                <span>{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="checkout-field">
                            <label>
                                {{ app()->getLocale() === 'ar' ? 'البريد الإلكتروني' : 'Email' }}
                            </label>

                            <input type="email" wire:model.defer="customer_email">
                            @error('customer_email')
                                <span>{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="checkout-field">
                            <label>
                                {{ app()->getLocale() === 'ar' ? 'مدينة الشحن' : 'Shipping City' }}
                            </label>

                            <select wire:model.live="shipping_city_id">
                                <option value="">
                                    {{ app()->getLocale() === 'ar' ? 'اختر المدينة' : 'Select city' }}
                                </option>

                                @foreach ($shippingCities as $shippingCity)
                                    <option value="{{ $shippingCity->id }}">
                                        {{ $shippingCity->name }}
                                        -
                                        {{ number_format((float) $shippingCity->delivery_fee, 2) }}
                                        {{ $currency }}
                                    </option>
                                @endforeach
                            </select>

                            @error('shipping_city_id')
                                <span>{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="checkout-field">
                            <label>
                                {{ app()->getLocale() === 'ar' ? 'المدينة / المحافظة' : 'City / Governorate' }}
                            </label>

                            <input type="text" wire:model.defer="city">
                            @error('city')
                                <span>{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="checkout-field">
                            <label>
                                {{ app()->getLocale() === 'ar' ? 'المنطقة' : 'Region' }}
                            </label>

                            <input type="text" wire:model.defer="region">
                            @error('region')
                                <span>{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="checkout-field checkout-field-full">
                            <label>
                                {{ app()->getLocale() === 'ar' ? 'العنوان بالتفصيل' : 'Full Address' }}
                            </label>

                            <textarea rows="4" wire:model.defer="address"></textarea>
                            @error('address')
                                <span>{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="checkout-field checkout-field-full">
                            <label>
                                {{ app()->getLocale() === 'ar' ? 'ملاحظات الطلب' : 'Order Notes' }}
                            </label>

                            <textarea rows="3" wire:model.defer="notes"></textarea>
                        </div>
                    </div>
                    @if (!empty($savedAddresses))
                        <div class="checkout-saved-addresses">
                            <label>
                                {{ app()->getLocale() === 'ar' ? 'اختيار عنوان محفوظ' : 'Choose Saved Address' }}
                            </label>

                            <select wire:model.live="customer_address_id">
                                <option value="">
                                    {{ app()->getLocale() === 'ar' ? 'اختر عنوانًا محفوظًا' : 'Choose saved address' }}
                                </option>

                                @foreach ($savedAddresses as $address)
                                    <option value="{{ $address['id'] }}">
                                        {{ $address['display'] }}
                                        @if ($address['is_default'])
                                            {{ app()->getLocale() === 'ar' ? ' - افتراضي' : ' - Default' }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>

                            <p>
                                {{ app()->getLocale() === 'ar'
                                    ? 'عند اختيار عنوان محفوظ سيتم ملء بيانات الشحن تلقائيًا.'
                                    : 'Choosing a saved address will automatically fill shipping details.' }}
                            </p>
                        </div>
                    @endif
                    @auth('customer')
                        @if (empty($customer_address_id))
                            <div class="checkout-save-address">
                                <label class="checkout-save-address-check">
                                    <input type="checkbox" wire:model.live="save_address_to_account">

                                    <span>
                                        {{ app()->getLocale() === 'ar'
                                            ? 'حفظ هذا العنوان في حسابي لاستخدامه لاحقًا'
                                            : 'Save this address to my account for later' }}
                                    </span>
                                </label>

                                @if ($save_address_to_account)
                                    <div class="mt-4">
                                        <label>
                                            {{ app()->getLocale() === 'ar' ? 'اسم العنوان' : 'Address Label' }}
                                        </label>

                                        <input type="text" wire:model="new_address_label"
                                            placeholder="{{ app()->getLocale() === 'ar' ? 'مثال: المنزل / العمل' : 'Example: Home / Work' }}">
                                    </div>
                                @endif
                            </div>
                        @endif
                    @endauth
                    <div class="checkout-payment">
                        <h2>
                            {{ app()->getLocale() === 'ar' ? 'طريقة الدفع' : 'Payment Method' }}
                        </h2>

                        <div class="checkout-payment-grid">
                            @foreach ($paymentMethods as $method)
                                @php
                                    $paymentImage = $method['image'] ?? null;
                                    $paymentImageUrl = $paymentImage
                                        ? (\Illuminate\Support\Str::startsWith($paymentImage, ['http://', 'https://'])
                                            ? $paymentImage
                                            : asset('storage/' . $paymentImage))
                                        : null;
                                @endphp

                                <label
                                    class="checkout-payment-option {{ $payment_method === $method['key'] ? 'is-selected' : '' }}">
                                    <input type="radio" value="{{ $method['key'] }}"
                                        wire:model.live="payment_method">

                                    @if ($paymentImageUrl)
                                        <img src="{{ $paymentImageUrl }}" alt="{{ $method['name'] }}">
                                    @endif

                                    <span>
                                        {{ $method['name'] }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        @if (!empty($paymentDetails['title']) || !empty($paymentDetails['details']) || !empty($paymentDetails['instructions']))
                            <div class="checkout-payment-details">
                                @if (!empty($paymentDetails['title']))
                                    <h3>
                                        {{ $paymentDetails['title'] }}
                                    </h3>
                                @endif

                                @if (!empty($paymentDetails['details']))
                                    <div class="checkout-payment-details-box">
                                        {!! nl2br(e($paymentDetails['details'])) !!}
                                    </div>
                                @endif

                                @if (!empty($paymentDetails['instructions']))
                                    <div class="checkout-payment-instructions">
                                        <strong>
                                            {{ app()->getLocale() === 'ar' ? 'تعليمات الدفع' : 'Payment Instructions' }}
                                        </strong>

                                        <p>
                                            {!! nl2br(e($paymentDetails['instructions'])) !!}
                                        </p>
                                    </div>
                                @endif
                            </div>
                        @endif
                        @if ($this->paymentRequiresProof())
                            <div class="checkout-payment-proof">
                                <label>
                                    {{ app()->getLocale() === 'ar' ? 'صورة إيصال الدفع' : 'Payment Proof' }}
                                </label>

                                <input type="file" wire:model="payment_proof" accept="image/*">

                                @error('payment_proof')
                                    <span>{{ $message }}</span>
                                @enderror

                                <div wire:loading wire:target="payment_proof" class="checkout-uploading">
                                    {{ app()->getLocale() === 'ar' ? 'جاري رفع الصورة...' : 'Uploading...' }}
                                </div>

                                @if ($payment_proof)
                                    <p>
                                        {{ app()->getLocale() === 'ar' ? 'تم اختيار صورة الإيصال' : 'Payment proof selected' }}
                                    </p>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>

                <aside class="checkout-summary-card">
                    <h2>
                        {{ app()->getLocale() === 'ar' ? 'مراجعة الطلب' : 'Order Review' }}
                    </h2>

                    <div class="checkout-items">
                        @foreach ($items as $item)
                            @php
                                $snapshot = $item->snapshot ?? [];
                                $productSnapshot = $snapshot['product'] ?? [];
                                $variantSnapshot = $snapshot['variant'] ?? [];
                                $attributes = $variantSnapshot['attributes'] ?? [];

                                $productName =
                                    $productSnapshot['name'] ??
                                    ($item->product?->transNow?->name ??
                                        ($item->product?->arabicTranslation?->name ??
                                            ($item->product?->englishTranslation?->name ??
                                                (app()->getLocale() === 'ar' ? 'منتج' : 'Product'))));

                                $image =
                                    $variantSnapshot['image'] ??
                                    ($productSnapshot['image'] ??
                                        ($item->variant?->image ?? ($item->product?->main_image ?? null)));
                            @endphp

                            <div class="checkout-item">
                                <div class="checkout-item-image">
                                    @if ($image)
                                        <img src="{{ \App\Support\Media::url($image, 500) }}" alt="{{ $productName }}" loading="lazy">
                                    @else
                                        <span>{{ mb_substr($productName, 0, 1) }}</span>
                                    @endif
                                </div>

                                <div>
                                    <h3>{{ $productName }}</h3>

                                    @if ($attributes)
                                        <p>
                                            @foreach ($attributes as $attribute)
                                                {{ $attribute['attribute_name'] ?? '' }}:
                                                {{ $attribute['value_name'] ?? '' }}
                                                @if (!$loop->last)
                                                    /
                                                @endif
                                            @endforeach
                                        </p>
                                    @endif

                                    <small>
                                        {{ app()->getLocale() === 'ar' ? 'الكمية:' : 'Qty:' }}
                                        {{ $item->quantity }}
                                    </small>
                                </div>

                                <strong>
                                    {{ number_format((float) $item->subtotal, 2) }} {{ $currency }}
                                </strong>
                            </div>
                        @endforeach
                    </div>

                    <div class="checkout-coupon-box">
                        <label>
                            {{ app()->getLocale() === 'ar' ? 'كود الخصم' : 'Coupon Code' }}
                        </label>

                        <div>
                            <input type="text" wire:model.defer="coupon_code"
                                placeholder="{{ app()->getLocale() === 'ar' ? 'اكتب الكود' : 'Enter code' }}">

                            @if ($applied_coupon_code)
                                <button type="button" wire:click="removeCoupon">
                                    {{ app()->getLocale() === 'ar' ? 'إزالة' : 'Remove' }}
                                </button>
                            @else
                                <button type="button" wire:click="applyCoupon">
                                    {{ app()->getLocale() === 'ar' ? 'تطبيق' : 'Apply' }}
                                </button>
                            @endif
                        </div>

                        @if ($applied_coupon_code)
                            <p>
                                {{ app()->getLocale() === 'ar' ? 'تم تطبيق الكوبون:' : 'Applied coupon:' }}
                                <strong>{{ $applied_coupon_code }}</strong>
                            </p>
                        @endif
                    </div>

                    <div class="checkout-summary-lines">
                        <div>
                            <span>{{ app()->getLocale() === 'ar' ? 'الإجمالي الفرعي' : 'Subtotal' }}</span>
                            <strong>{{ number_format($subtotal, 2) }} {{ $currency }}</strong>
                        </div>

                        <div>
                            <span>{{ app()->getLocale() === 'ar' ? 'الخصم' : 'Discount' }}</span>
                            <strong>{{ number_format($discountTotal, 2) }} {{ $currency }}</strong>
                        </div>

                        <div>
                            <span>{{ app()->getLocale() === 'ar' ? 'الشحن' : 'Shipping' }}</span>
                            <strong>{{ number_format($shippingTotal, 2) }} {{ $currency }}</strong>
                        </div>

                        <div>
                            <span>{{ app()->getLocale() === 'ar' ? 'رسوم الدفع' : 'Payment Fee' }}</span>
                            <strong>{{ number_format($paymentFee, 2) }} {{ $currency }}</strong>
                        </div>

                        <div>
                            <span>{{ app()->getLocale() === 'ar' ? 'الضريبة' : 'Tax' }}</span>
                            <strong>{{ number_format($taxTotal, 2) }} {{ $currency }}</strong>
                        </div>
                    </div>

                    <div class="checkout-total">
                        <span>{{ app()->getLocale() === 'ar' ? 'الإجمالي' : 'Total' }}</span>
                        <strong>{{ number_format($grandTotal, 2) }} {{ $currency }}</strong>
                    </div>

                    <button type="button" wire:click="placeOrder" wire:loading.attr="disabled"
                        class="checkout-submit-btn">
                        <span wire:loading.remove>
                            {{ app()->getLocale() === 'ar' ? 'تأكيد الطلب' : 'Place Order' }}
                        </span>

                        <span wire:loading>
                            {{ app()->getLocale() === 'ar' ? 'جاري إنشاء الطلب...' : 'Placing order...' }}
                        </span>
                    </button>
                </aside>
            </div>
        @endif
    </div>
</section>

@if (!empty($cart) && $cart->items->count())
    @include('site.partials.tracking-event', [
        'event' => 'InitiateCheckout',
        'payload' => app(\App\Services\TrackingEventService::class)->cartPayload($cart),
    ])
@endif
