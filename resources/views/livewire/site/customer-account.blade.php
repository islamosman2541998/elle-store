<section class="customer-account-page">
    <div class="site-container">
        <div class="customer-account-head">
            <div>
                <h1>{{ app()->getLocale() === 'ar' ? 'حسابي' : 'My Account' }}</h1>

                <p>
                    {{ app()->getLocale() === 'ar'
                        ? 'مرحبًا ' . $customer->name . '، يمكنك إدارة حسابك وطلباتك من هنا.'
                        : 'Hello ' . $customer->name . ', manage your account and orders here.' }}
                </p>
            </div>

            <button type="button" wire:click="logout">
                {{ app()->getLocale() === 'ar' ? 'تسجيل الخروج' : 'Logout' }}
            </button>
        </div>

        <div class="customer-account-forms">
            <div class="customer-account-form-card">
                <h2>{{ app()->getLocale() === 'ar' ? 'بيانات الحساب' : 'Account Details' }}</h2>

                <p>
                    {{ app()->getLocale() === 'ar'
                        ? 'يمكنك تعديل بيانات حسابك الأساسية.'
                        : 'You can update your basic account information.' }}
                </p>

                <form wire:submit.prevent="updateProfile" class="customer-account-form">
                    <div>
                        <label>{{ app()->getLocale() === 'ar' ? 'الاسم' : 'Name' }}</label>

                        <input type="text" wire:model="name"
                            placeholder="{{ app()->getLocale() === 'ar' ? 'الاسم بالكامل' : 'Full name' }}"
                            autocomplete="name">

                        @error('name')
                            <span class="customer-auth-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label>{{ app()->getLocale() === 'ar' ? 'البريد الإلكتروني' : 'Email Address' }}</label>

                        <input type="email" wire:model="email" placeholder="email@example.com" autocomplete="email">

                        @error('email')
                            <span class="customer-auth-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label>{{ app()->getLocale() === 'ar' ? 'رقم الهاتف' : 'Phone Number' }}</label>

                        <input type="text" wire:model="phone"
                            placeholder="{{ app()->getLocale() === 'ar' ? 'رقم الهاتف' : 'Phone number' }}"
                            autocomplete="tel">

                        @error('phone')
                            <span class="customer-auth-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <button type="submit" wire:loading.attr="disabled" wire:target="updateProfile">
                        <span wire:loading.remove wire:target="updateProfile">
                            {{ app()->getLocale() === 'ar' ? 'حفظ البيانات' : 'Save Details' }}
                        </span>

                        <span wire:loading wire:target="updateProfile">
                            {{ app()->getLocale() === 'ar' ? 'جاري الحفظ...' : 'Saving...' }}
                        </span>
                    </button>
                </form>
            </div>

            <div class="customer-account-form-card">
                <h2>{{ app()->getLocale() === 'ar' ? 'تغيير كلمة المرور' : 'Change Password' }}</h2>

                <p>
                    {{ app()->getLocale() === 'ar'
                        ? 'استخدم كلمة مرور قوية لحماية حسابك.'
                        : 'Use a strong password to keep your account secure.' }}
                </p>

                <form wire:submit.prevent="updatePassword" class="customer-account-form">
                    <div>
                        <label>{{ app()->getLocale() === 'ar' ? 'كلمة المرور الحالية' : 'Current Password' }}</label>

                        <input type="password" wire:model="current_password" placeholder="********"
                            autocomplete="current-password">

                        @error('current_password')
                            <span class="customer-auth-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label>{{ app()->getLocale() === 'ar' ? 'كلمة المرور الجديدة' : 'New Password' }}</label>

                        <input type="password" wire:model="password" placeholder="********" autocomplete="new-password">

                        @error('password')
                            <span class="customer-auth-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label>{{ app()->getLocale() === 'ar' ? 'تأكيد كلمة المرور الجديدة' : 'Confirm New Password' }}</label>

                        <input type="password" wire:model="password_confirmation" placeholder="********"
                            autocomplete="new-password">

                        @error('password_confirmation')
                            <span class="customer-auth-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <button type="submit" wire:loading.attr="disabled" wire:target="updatePassword">
                        <span wire:loading.remove wire:target="updatePassword">
                            {{ app()->getLocale() === 'ar' ? 'تغيير كلمة المرور' : 'Change Password' }}
                        </span>

                        <span wire:loading wire:target="updatePassword">
                            {{ app()->getLocale() === 'ar' ? 'جاري التغيير...' : 'Updating...' }}
                        </span>
                    </button>
                </form>
            </div>
        </div>

        <div class="customer-account-grid">
            <a href="{{ route('site.customer.orders') }}" class="customer-account-card">
                <span>
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 12h6m-6 4h6M7 3h10a2 2 0 0 1 2 2v14l-4-2-3 2-3-2-4 2V5a2 2 0 0 1 2-2Z" />
                    </svg>
                </span>

                <h2>{{ app()->getLocale() === 'ar' ? 'طلباتي' : 'My Orders' }}</h2>

                <p>
                    {{ app()->getLocale() === 'ar'
                        ? 'تابع كل طلباتك وحالة الشحن والدفع.'
                        : 'Track your orders, shipping, and payment status.' }}
                </p>
            </a>

            <a href="{{ route('site.orders.track') }}" class="customer-account-card">
                <span>
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 17v-6h13v6M9 17H5a2 2 0 0 1-2-2V7h6v10Zm13 0a2 2 0 0 1-2 2h-1a2 2 0 0 1-4 0H9a2 2 0 0 1-4 0H4" />
                    </svg>
                </span>

                <h2>{{ app()->getLocale() === 'ar' ? 'تتبع طلب' : 'Track Order' }}</h2>

                <p>
                    {{ app()->getLocale() === 'ar'
                        ? 'تابع طلب معين باستخدام رقم الطلب.'
                        : 'Track a specific order using its order number.' }}
                </p>
            </a>

            <a href="{{ route('site.shop') }}" class="customer-account-card">
                <span>
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5M4.5 9.75h15l-.75 10.5h-13.5L4.5 9.75Z" />
                    </svg>
                </span>

                <h2>{{ app()->getLocale() === 'ar' ? 'استكمال التسوق' : 'Continue Shopping' }}</h2>

                <p>
                    {{ app()->getLocale() === 'ar' ? 'اكتشف المنتجات والعروض المتاحة.' : 'Explore products and current offers.' }}
                </p>
            </a>
            <a href="{{ route('site.customer.addresses') }}" class="customer-account-card">
                <span>
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 21s7-4.5 7-11a7 7 0 1 0-14 0c0 6.5 7 11 7 11Z" />
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 10.5a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" />
                    </svg>
                </span>

                <h2>{{ app()->getLocale() === 'ar' ? 'عناويني' : 'My Addresses' }}</h2>

                <p>
                    {{ app()->getLocale() === 'ar'
                        ? 'أضف وعدّل عناوين الشحن الخاصة بك.'
                        : 'Add and manage your shipping addresses.' }}
                </p>
            </a>
        </div>
    </div>
</section>
