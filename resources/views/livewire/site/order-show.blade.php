@php
    $currency = $storeSettings->currency_symbol ?? ($storeSettings->currency_code ?? 'EGP');
@endphp

<section class="order-show-page">
    <div class="site-container">
        @if (!$verified)
            <div class="order-verify-card">
                <h1>
                    {{ app()->getLocale() === 'ar' ? 'التحقق من الطلب' : 'Verify Order' }}
                </h1>

                <p>
                    {{ app()->getLocale() === 'ar'
                        ? 'من فضلك أدخل رقم الهاتف المستخدم في الطلب لعرض التفاصيل.'
                        : 'Please enter the phone number used in this order to view details.' }}
                </p>

                <div class="order-verify-form">
                    <input type="text" wire:model.defer="phone"
                        placeholder="{{ app()->getLocale() === 'ar' ? 'رقم الهاتف' : 'Phone number' }}">

                    <button type="button" wire:click="verifyOrder" wire:loading.attr="disabled">
                        <span wire:loading.remove>
                            {{ app()->getLocale() === 'ar' ? 'عرض الطلب' : 'View Order' }}
                        </span>

                        <span wire:loading>
                            {{ app()->getLocale() === 'ar' ? 'جاري التحقق...' : 'Checking...' }}
                        </span>
                    </button>
                </div>

                @error('phone')
                    <span class="order-verify-error">{{ $message }}</span>
                @enderror
            </div>
        @elseif (!$order)
            <div class="order-verify-card">
                <h1>
                    {{ app()->getLocale() === 'ar' ? 'الطلب غير موجود' : 'Order not found' }}
                </h1>

                <p>
                    {{ app()->getLocale() === 'ar' ? 'لم نتمكن من العثور على هذا الطلب.' : 'We could not find this order.' }}
                </p>

                <a href="{{ route('site.shop') }}">
                    {{ app()->getLocale() === 'ar' ? 'العودة للمتجر' : 'Back to shop' }}
                </a>
            </div>
        @else
            <div class="order-show-head">
                <div>
                    <div class="order-show-breadcrumb">
                        <a href="{{ route('site.home') }}">
                            {{ app()->getLocale() === 'ar' ? 'الرئيسية' : 'Home' }}
                        </a>

                        <span>/</span>

                        <span>
                            {{ app()->getLocale() === 'ar' ? 'تفاصيل الطلب' : 'Order Details' }}
                        </span>
                    </div>

                    <h1>
                        {{ app()->getLocale() === 'ar' ? 'تفاصيل الطلب' : 'Order Details' }}
                    </h1>

                    <p>
                        {{ app()->getLocale() === 'ar' ? 'رقم الطلب:' : 'Order number:' }}
                        <strong>{{ $order->order_number }}</strong>
                    </p>
                </div>

                <div class="order-show-badges">
                    <span class="order-badge {{ $this->statusColor($order->status) }}">
                        {{ $this->statusLabel($order->status) }}
                    </span>

                    <span class="order-badge {{ $this->paymentStatusColor($order->payment_status) }}">
                        {{ $this->paymentStatusLabel($order->payment_status) }}
                    </span>
                </div>
            </div>
            <div class="order-timeline-card">
                <div class="order-timeline-title">
                    <h2>
                        {{ app()->getLocale() === 'ar' ? 'حالة الطلب' : 'Order Status' }}
                    </h2>

                    <p>
                        {{ app()->getLocale() === 'ar' ? 'تابع مراحل طلبك خطوة بخطوة' : 'Track your order progress step by step' }}
                    </p>
                </div>

                <div class="order-timeline">
                    @foreach ($this->timelineSteps($order) as $step)
                        <div
                            class="order-timeline-step
                    {{ $step['done'] ? 'is-done' : '' }}
                    {{ $step['active'] ? 'is-active' : '' }}
                    {{ $step['danger'] ? 'is-danger' : '' }}">
                            <div class="order-timeline-dot">
                                @if ($step['done'])
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="3"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                @else
                                    <span></span>
                                @endif
                            </div>

                            <div class="order-timeline-content">
                                <strong>{{ $step['label'] }}</strong>

                                @if ($step['date'])
                                    <span>{{ $step['date']->format('Y-m-d H:i') }}</span>
                                @else
                                    <span>-</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="order-show-layout">
                <div class="order-main-card">
                    <h2>
                        {{ app()->getLocale() === 'ar' ? 'المنتجات' : 'Items' }}
                    </h2>

                    <div class="order-items-list">
                        @foreach ($order->items as $item)
                            @php
                                $snapshot = $item->snapshot ?? [];
                                $productSnapshot = $snapshot['product'] ?? [];
                                $variantSnapshot = $snapshot['variant'] ?? [];
                                $attributes = $variantSnapshot['attributes'] ?? [];

                                $productName =
                                    $item->product_name ??
                                    ($productSnapshot['name'] ?? (app()->getLocale() === 'ar' ? 'منتج' : 'Product'));

                                $image =
                                    $variantSnapshot['image'] ??
                                    ($productSnapshot['image'] ??
                                        ($item->variant?->image ?? ($item->product?->main_image ?? null)));
                            @endphp

                            <div class="order-item-card">
                                <div class="order-item-image">
                                    @if ($image)
                                        <img src="{{ \App\Support\Media::url($image, 500) }}" alt="{{ $productName }}" loading="lazy">
                                    @else
                                        <span>{{ mb_substr($productName, 0, 1) }}</span>
                                    @endif
                                </div>

                                <div class="order-item-content">
                                    <h3>{{ $productName }}</h3>

                                    @if ($attributes)
                                        <div class="order-item-attrs">
                                            @foreach ($attributes as $attribute)
                                                <span>
                                                    {{ $attribute['attribute_name'] ?? '' }}:
                                                    {{ $attribute['value_name'] ?? '' }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif

                                    <p>
                                        {{ app()->getLocale() === 'ar' ? 'الكمية:' : 'Qty:' }}
                                        {{ $item->quantity }}
                                    </p>
                                </div>

                                <div class="order-item-price">
                                    <span>
                                        {{ number_format((float) $item->unit_price, 2) }} {{ $currency }}
                                    </span>

                                    <strong>
                                        {{ number_format((float) $item->subtotal, 2) }} {{ $currency }}
                                    </strong>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <aside class="order-side">
                    <div class="order-summary-card">
                        <h2>
                            {{ app()->getLocale() === 'ar' ? 'ملخص الطلب' : 'Order Summary' }}
                        </h2>

                        <div class="order-summary-lines">
                            <div>
                                <span>{{ app()->getLocale() === 'ar' ? 'الإجمالي الفرعي' : 'Subtotal' }}</span>
                                <strong>{{ number_format((float) $order->subtotal, 2) }} {{ $currency }}</strong>
                            </div>

                            <div>
                                <span>{{ app()->getLocale() === 'ar' ? 'الخصم' : 'Discount' }}</span>
                                <strong>{{ number_format((float) $order->discount_total, 2) }}
                                    {{ $currency }}</strong>
                            </div>

                            <div>
                                <span>{{ app()->getLocale() === 'ar' ? 'الشحن' : 'Shipping' }}</span>
                                <strong>{{ number_format((float) $order->shipping_total, 2) }}
                                    {{ $currency }}</strong>
                            </div>

                            <div>
                                <span>{{ app()->getLocale() === 'ar' ? 'رسوم الدفع' : 'Payment fee' }}</span>
                                <strong>{{ number_format((float) $order->payment_fee, 2) }}
                                    {{ $currency }}</strong>
                            </div>

                            <div>
                                <span>{{ app()->getLocale() === 'ar' ? 'الضريبة' : 'Tax' }}</span>
                                <strong>{{ number_format((float) $order->tax_total, 2) }} {{ $currency }}</strong>
                            </div>
                        </div>

                        <div class="order-total">
                            <span>{{ app()->getLocale() === 'ar' ? 'الإجمالي' : 'Total' }}</span>
                            <strong>{{ number_format((float) $order->grand_total, 2) }} {{ $currency }}</strong>
                        </div>
                    </div>

                    <div class="order-info-card">
                        <h2>
                            {{ app()->getLocale() === 'ar' ? 'بيانات الدفع' : 'Payment Details' }}
                        </h2>

                        <div class="order-info-lines">
                            <div>
                                <span>{{ app()->getLocale() === 'ar' ? 'وسيلة الدفع' : 'Payment method' }}</span>
                                <strong>{{ $this->paymentMethodLabel($order->payment_method) }}</strong>
                            </div>

                            <div>
                                <span>{{ app()->getLocale() === 'ar' ? 'حالة الدفع' : 'Payment status' }}</span>
                                <strong>{{ $this->paymentStatusLabel($order->payment_status) }}</strong>
                            </div>

                            @foreach ($order->payments as $payment)
                                @if ($payment->payment_proof)
                                    <div>
                                        <span>{{ app()->getLocale() === 'ar' ? 'إيصال الدفع' : 'Payment proof' }}</span>
                                        <a href="{{ asset('storage/' . $payment->payment_proof) }}" target="_blank">
                                            {{ app()->getLocale() === 'ar' ? 'عرض الإيصال' : 'View proof' }}
                                        </a>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>

                    <div class="order-info-card">
                        <h2>
                            {{ app()->getLocale() === 'ar' ? 'بيانات الشحن' : 'Shipping Details' }}
                        </h2>

                        @php
                            $shipping = $order->shipping_address_snapshot ?? [];
                        @endphp

                        <div class="order-info-lines">
                            <div>
                                <span>{{ app()->getLocale() === 'ar' ? 'الاسم' : 'Name' }}</span>
                                <strong>{{ $shipping['name'] ?? $order->customer_name }}</strong>
                            </div>

                            <div>
                                <span>{{ app()->getLocale() === 'ar' ? 'الهاتف' : 'Phone' }}</span>
                                <strong>{{ $shipping['phone'] ?? $order->customer_phone }}</strong>
                            </div>

                            <div>
                                <span>{{ app()->getLocale() === 'ar' ? 'المدينة' : 'City' }}</span>
                                <strong>{{ $shipping['city'] ?? '-' }}</strong>
                            </div>

                            <div>
                                <span>{{ app()->getLocale() === 'ar' ? 'العنوان' : 'Address' }}</span>
                                <strong>{{ $shipping['address'] ?? '-' }}</strong>
                            </div>
                        </div>
                    </div>

                    {{-- "Show tracking to customer" is a store setting; honour it. --}}
                    @if ($order->shipments->count() && ($storeSettings->show_tracking_to_customer ?? true))
                        <div class="order-info-card">
                            <h2>
                                {{ app()->getLocale() === 'ar' ? 'الشحنات' : 'Shipments' }}
                            </h2>

                            <div class="order-info-lines">
                                @foreach ($order->shipments as $shipment)
                                    <div>
                                        <span>{{ app()->getLocale() === 'ar' ? 'رقم الشحنة' : 'Shipment' }}</span>
                                        <strong>{{ $shipment->shipment_number }}</strong>
                                    </div>

                                    @if ($shipment->tracking_url)
                                        <div>
                                            <span>{{ app()->getLocale() === 'ar' ? 'التتبع' : 'Tracking' }}</span>
                                            <a href="{{ $shipment->tracking_url }}" target="_blank">
                                                {{ app()->getLocale() === 'ar' ? 'تتبع الشحنة' : 'Track shipment' }}
                                            </a>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif
                </aside>
            </div>
        @endif
    </div>
</section>

@if (!empty($purchaseTracking))
    @include('site.partials.tracking-event', [
        'event' => 'Purchase',
        'payload' => $purchaseTracking['payload'],
        'eventId' => $purchaseTracking['eventId'],
    ])
@endif
