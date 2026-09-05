@php
    $currency = $storeSettings->currency_symbol ?? $storeSettings->currency_code ?? 'EGP';
@endphp

<section class="customer-orders-page">
    <div class="site-container">
        <div class="customer-orders-head">
            <div>
                <div class="customer-orders-breadcrumb">
                    <a href="{{ route('site.home') }}">
                        {{ app()->getLocale() === 'ar' ? 'الرئيسية' : 'Home' }}
                    </a>

                    <span>/</span>

                    <span>
                        {{ app()->getLocale() === 'ar' ? 'طلباتي' : 'My Orders' }}
                    </span>
                </div>

                <h1>
                    {{ app()->getLocale() === 'ar' ? 'طلباتي' : 'My Orders' }}
                </h1>

                <p>
                    {{ app()->getLocale() === 'ar'
                        ? 'تابع كل طلباتك وحالة الدفع والشحن من مكان واحد.'
                        : 'Track all your orders, payment status, and shipping from one place.' }}
                </p>
            </div>

            <a href="{{ route('site.orders.track') }}">
                {{ app()->getLocale() === 'ar' ? 'تتبع طلب برقم الطلب' : 'Track by order number' }}
            </a>
        </div>

        @if (! $verified && ! $isAuthenticatedCustomer)
            <div class="customer-orders-verify-card">
                <div class="customer-orders-verify-icon">
                    <svg class="h-10 w-10" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 0 0-8 0v4M5 11h14l-1 10H6L5 11Z" />
                    </svg>
                </div>

                <h2>
                    {{ app()->getLocale() === 'ar' ? 'اعرض طلباتك' : 'View Your Orders' }}
                </h2>

                <p>
                    {{ app()->getLocale() === 'ar'
                        ? 'أدخل رقم الهاتف او البريد الإلكتروني المستخدمين أثناء الطلب لعرض جميع طلباتك.'
                        : 'Enter the phone number or email used during checkout to view all your orders.' }}
                </p>

                <div class="customer-orders-verify-form">
                    <div>
                        <label>
                            {{ app()->getLocale() === 'ar' ? 'رقم الهاتف' : 'Phone Number' }}
                        </label>

                        <input
                            type="text"
                            wire:model.defer="phone"
                            placeholder="{{ app()->getLocale() === 'ar' ? 'رقم الهاتف' : 'Phone number' }}"
                        >

                        @error('phone')
                            <span>{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label>
                            {{ app()->getLocale() === 'ar' ? 'البريد الإلكتروني' : 'Email Address' }}
                        </label>

                        <input
                            type="email"
                            wire:model.defer="email"
                            placeholder="email@example.com"
                        >

                        @error('email')
                            <span>{{ $message }}</span>
                        @enderror
                    </div>

                    <button type="button" wire:click="verifyOrders" wire:loading.attr="disabled">
                        <span wire:loading.remove>
                            {{ app()->getLocale() === 'ar' ? 'عرض طلباتي' : 'View My Orders' }}
                        </span>

                        <span wire:loading>
                            {{ app()->getLocale() === 'ar' ? 'جاري البحث...' : 'Searching...' }}
                        </span>
                    </button>
                </div>
            </div>
        @else
            @if ($orders->count())
                <div class="customer-orders-list">
                    @foreach ($orders as $order)
                        <div class="customer-order-card">
                            <div class="customer-order-main">
                                <div>
                                    <span class="customer-order-label">
                                        {{ app()->getLocale() === 'ar' ? 'رقم الطلب' : 'Order Number' }}
                                    </span>

                                    <h2>{{ $order->order_number }}</h2>

                                    <p>
                                        {{ app()->getLocale() === 'ar' ? 'تاريخ الطلب:' : 'Order date:' }}
                                        {{ $order->created_at?->format('Y-m-d H:i') }}
                                    </p>
                                </div>

                                <div class="customer-order-badges">
                                    <span class="customer-order-badge {{ $this->statusColor($order->status) }}">
                                        {{ $this->statusLabel($order->status) }}
                                    </span>

                                    <span class="customer-order-badge {{ $this->paymentStatusColor($order->payment_status) }}">
                                        {{ $this->paymentStatusLabel($order->payment_status) }}
                                    </span>
                                </div>
                            </div>

                            <div class="customer-order-meta">
                                <div>
                                    <span>{{ app()->getLocale() === 'ar' ? 'عدد المنتجات' : 'Items' }}</span>
                                    <strong>{{ $order->items_count }}</strong>
                                </div>

                                <div>
                                    <span>{{ app()->getLocale() === 'ar' ? 'الشحن' : 'Shipping' }}</span>
                                    <strong>{{ number_format((float) $order->shipping_total, 2) }} {{ $currency }}</strong>
                                </div>

                                <div>
                                    <span>{{ app()->getLocale() === 'ar' ? 'الإجمالي' : 'Total' }}</span>
                                    <strong>{{ number_format((float) $order->grand_total, 2) }} {{ $currency }}</strong>
                                </div>
                            </div>

                            <div class="customer-order-actions">
                                <button
                                    type="button"
                                    wire:click="openOrder(@js($order->order_number))"
                                    wire:loading.attr="disabled"
                                >
                                    {{ app()->getLocale() === 'ar' ? 'عرض التفاصيل' : 'View Details' }}
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="customer-orders-pagination">
                    {{ $orders->links() }}
                </div>
            @else
                <div class="customer-orders-empty">
                    <h2>
                        {{ app()->getLocale() === 'ar' ? 'لا توجد طلبات' : 'No Orders Yet' }}
                    </h2>

                    <p>
                        {{ app()->getLocale() === 'ar'
                            ? 'لم يتم العثور على أي طلبات مرتبطة بهذه البيانات.'
                            : 'No orders were found for these details.' }}
                    </p>

                    <a href="{{ route('site.shop') }}">
                        {{ app()->getLocale() === 'ar' ? 'ابدأ التسوق' : 'Start Shopping' }}
                    </a>
                </div>
            @endif
        @endif
    </div>
</section>