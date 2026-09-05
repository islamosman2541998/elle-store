<section class="track-order-page">
    <div class="site-container">
        <div class="track-order-card">
            <div class="track-order-icon">
                <svg class="h-12 w-12" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-6h13v6M9 17H5a2 2 0 0 1-2-2V7h6v10Zm13 0a2 2 0 0 1-2 2h-1a2 2 0 0 1-4 0H9a2 2 0 0 1-4 0H4" />
                </svg>
            </div>

            <h1>
                {{ app()->getLocale() === 'ar' ? 'تتبع طلبك' : 'Track Your Order' }}
            </h1>

            <p>
                {{ app()->getLocale() === 'ar'
                    ? 'أدخل رقم الطلب ورقم الهاتف المستخدم أثناء الشراء لعرض تفاصيل الطلب وحالته.'
                    : 'Enter your order number and phone number to view your order details and current status.' }}
            </p>

            <div class="track-order-form">
                <div class="track-order-field">
                    <label>
                        {{ app()->getLocale() === 'ar' ? 'رقم الطلب' : 'Order Number' }}
                    </label>

                    <input
                        type="text"
                        wire:model.defer="order_number"
                        placeholder="ORD-20260618-XXXXXX"
                    >

                    @error('order_number')
                        <span>{{ $message }}</span>
                    @enderror
                </div>

                <div class="track-order-field">
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

                <button type="button" wire:click="track" wire:loading.attr="disabled">
                    <span wire:loading.remove>
                        {{ app()->getLocale() === 'ar' ? 'تتبع الطلب' : 'Track Order' }}
                    </span>

                    <span wire:loading>
                        {{ app()->getLocale() === 'ar' ? 'جاري البحث...' : 'Searching...' }}
                    </span>
                </button>
            </div>

            <a href="{{ route('site.shop') }}" class="track-order-back">
                {{ app()->getLocale() === 'ar' ? 'العودة للمتجر' : 'Back to shop' }}
            </a>
        </div>
    </div>
</section>