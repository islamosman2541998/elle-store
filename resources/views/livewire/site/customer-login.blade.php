<section class="customer-auth-page">
    <div class="site-container">
        <div class="customer-auth-card">
            <div class="customer-auth-icon">
                <svg class="h-10 w-10" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25a3.75 3.75 0 1 0-7.5 0V9m-.75 11.25h9a2.25 2.25 0 0 0 2.25-2.25v-6.75A2.25 2.25 0 0 0 16.5 9h-9a2.25 2.25 0 0 0-2.25 2.25V18a2.25 2.25 0 0 0 2.25 2.25Z" />
                </svg>
            </div>

            <h1>{{ app()->getLocale() === 'ar' ? 'تسجيل الدخول' : 'Login' }}</h1>

            <p>
                {{ app()->getLocale() === 'ar'
                    ? 'ادخل إلى حسابك لمتابعة طلباتك وإدارة بياناتك.'
                    : 'Login to track your orders and manage your account.' }}
            </p>

            @error('login')
                <div class="customer-auth-alert">
                    {{ $message }}
                </div>
            @enderror

            <form wire:submit.prevent="login" class="customer-auth-form">
                <div>
                    <label>{{ app()->getLocale() === 'ar' ? 'البريد الإلكتروني' : 'Email Address' }}</label>

                    <input
                        type="email"
                        wire:model="email"
                        placeholder="email@example.com"
                        autocomplete="email"
                    >

                    @error('email')
                        <span class="customer-auth-error">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label>{{ app()->getLocale() === 'ar' ? 'كلمة المرور' : 'Password' }}</label>

                    <input
                        type="password"
                        wire:model="password"
                        placeholder="********"
                        autocomplete="current-password"
                    >

                    @error('password')
                        <span class="customer-auth-error">{{ $message }}</span>
                    @enderror
                </div>

                <label class="customer-auth-check">
                    <input type="checkbox" wire:model="remember">
                    <span>{{ app()->getLocale() === 'ar' ? 'تذكرني' : 'Remember me' }}</span>
                </label>

                <button type="submit" wire:loading.attr="disabled">
                    <span wire:loading.remove>
                        {{ app()->getLocale() === 'ar' ? 'دخول' : 'Login' }}
                    </span>

                    <span wire:loading>
                        {{ app()->getLocale() === 'ar' ? 'جاري الدخول...' : 'Logging in...' }}
                    </span>
                </button>
            </form>

            <div class="customer-auth-switch">
                <span>{{ app()->getLocale() === 'ar' ? 'ليس لديك حساب؟' : 'Do not have an account?' }}</span>

                <a href="{{ route('site.customer.register') }}">
                    {{ app()->getLocale() === 'ar' ? 'إنشاء حساب جديد' : 'Create account' }}
                </a>
            </div>
        </div>
    </div>
</section>