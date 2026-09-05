<section class="customer-addresses-page">
    <div class="site-container">
        <div class="customer-addresses-head">
            <div>
                <h1>{{ app()->getLocale() === 'ar' ? 'عناويني' : 'My Addresses' }}</h1>

                <p>
                    {{ app()->getLocale() === 'ar'
                        ? 'احفظ عناوينك لتسهيل عملية الشراء في الطلبات القادمة.'
                        : 'Save your addresses to make future checkout faster.' }}
                </p>
            </div>

            <a href="{{ route('site.customer.account') }}">
                {{ app()->getLocale() === 'ar' ? 'العودة لحسابي' : 'Back to Account' }}
            </a>
        </div>

        <div class="customer-addresses-layout">
            <div class="customer-address-form-card">
                <h2>
                    {{ $editingAddressId
                        ? (app()->getLocale() === 'ar' ? 'تعديل العنوان' : 'Edit Address')
                        : (app()->getLocale() === 'ar' ? 'إضافة عنوان جديد' : 'Add New Address') }}
                </h2>

                <form wire:submit.prevent="saveAddress" class="customer-address-form">
                    <div>
                        <label>{{ app()->getLocale() === 'ar' ? 'اسم العنوان' : 'Address Label' }}</label>

                        <input
                            type="text"
                            wire:model="label"
                            placeholder="{{ app()->getLocale() === 'ar' ? 'مثال: المنزل / العمل' : 'Example: Home / Work' }}"
                        >

                        @error('label')
                            <span class="customer-auth-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label>{{ app()->getLocale() === 'ar' ? 'اسم المستلم' : 'Recipient Name' }}</label>

                        <input
                            type="text"
                            wire:model="name"
                            placeholder="{{ app()->getLocale() === 'ar' ? 'اسم الشخص المستلم' : 'Recipient name' }}"
                            autocomplete="name"
                        >

                        @error('name')
                            <span class="customer-auth-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label>{{ app()->getLocale() === 'ar' ? 'رقم الهاتف' : 'Phone Number' }}</label>

                        <input
                            type="text"
                            wire:model="phone"
                            placeholder="{{ app()->getLocale() === 'ar' ? 'رقم هاتف المستلم' : 'Recipient phone' }}"
                            autocomplete="tel"
                        >

                        @error('phone')
                            <span class="customer-auth-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label>{{ app()->getLocale() === 'ar' ? 'الدولة' : 'Country' }}</label>

                        <input
                            type="text"
                            wire:model="country"
                            placeholder="{{ app()->getLocale() === 'ar' ? 'مثال: مصر' : 'Example: Egypt' }}"
                        >

                        @error('country')
                            <span class="customer-auth-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label>{{ app()->getLocale() === 'ar' ? 'المدينة' : 'City' }}</label>

                        <input
                            type="text"
                            wire:model="city"
                            placeholder="{{ app()->getLocale() === 'ar' ? 'اكتب المدينة' : 'Enter city' }}"
                        >

                        @error('city')
                            <span class="customer-auth-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label>{{ app()->getLocale() === 'ar' ? 'المنطقة' : 'Area' }}</label>

                        <input
                            type="text"
                            wire:model="area"
                            placeholder="{{ app()->getLocale() === 'ar' ? 'اختياري' : 'Optional' }}"
                        >

                        @error('area')
                            <span class="customer-auth-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="customer-address-field-full">
                        <label>{{ app()->getLocale() === 'ar' ? 'العنوان / الشارع' : 'Street Address' }}</label>

                        <input
                            type="text"
                            wire:model="street"
                            placeholder="{{ app()->getLocale() === 'ar' ? 'اسم الشارع أو العنوان بالتفصيل' : 'Street name or full address' }}"
                        >

                        @error('street')
                            <span class="customer-auth-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label>{{ app()->getLocale() === 'ar' ? 'المبنى' : 'Building' }}</label>

                        <input
                            type="text"
                            wire:model="building"
                            placeholder="{{ app()->getLocale() === 'ar' ? 'اختياري' : 'Optional' }}"
                        >

                        @error('building')
                            <span class="customer-auth-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label>{{ app()->getLocale() === 'ar' ? 'الدور' : 'Floor' }}</label>

                        <input
                            type="text"
                            wire:model="floor"
                            placeholder="{{ app()->getLocale() === 'ar' ? 'اختياري' : 'Optional' }}"
                        >

                        @error('floor')
                            <span class="customer-auth-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label>{{ app()->getLocale() === 'ar' ? 'الشقة' : 'Apartment' }}</label>

                        <input
                            type="text"
                            wire:model="apartment"
                            placeholder="{{ app()->getLocale() === 'ar' ? 'اختياري' : 'Optional' }}"
                        >

                        @error('apartment')
                            <span class="customer-auth-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="customer-address-field-full">
                        <label>{{ app()->getLocale() === 'ar' ? 'علامة مميزة' : 'Landmark' }}</label>

                        <input
                            type="text"
                            wire:model="landmark"
                            placeholder="{{ app()->getLocale() === 'ar' ? 'مثال: بجوار النادي' : 'Example: Near the club' }}"
                        >

                        @error('landmark')
                            <span class="customer-auth-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="customer-address-field-full">
                        <label>{{ app()->getLocale() === 'ar' ? 'ملاحظات' : 'Notes' }}</label>

                        <textarea
                            wire:model="notes"
                            rows="3"
                            placeholder="{{ app()->getLocale() === 'ar' ? 'أي ملاحظات خاصة بالتوصيل' : 'Any delivery notes' }}"
                        ></textarea>

                        @error('notes')
                            <span class="customer-auth-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <label class="customer-address-check">
                        <input type="checkbox" wire:model="is_default">

                        <span>
                            {{ app()->getLocale() === 'ar'
                                ? 'تعيين كعنوان افتراضي'
                                : 'Set as default address' }}
                        </span>
                    </label>

                    <div class="customer-address-form-actions">
                        <button type="submit" wire:loading.attr="disabled" wire:target="saveAddress">
                            <span wire:loading.remove wire:target="saveAddress">
                                {{ $editingAddressId
                                    ? (app()->getLocale() === 'ar' ? 'حفظ التعديل' : 'Save Changes')
                                    : (app()->getLocale() === 'ar' ? 'إضافة العنوان' : 'Add Address') }}
                            </span>

                            <span wire:loading wire:target="saveAddress">
                                {{ app()->getLocale() === 'ar' ? 'جاري الحفظ...' : 'Saving...' }}
                            </span>
                        </button>

                        @if ($editingAddressId)
                            <button type="button" class="is-secondary" wire:click="resetForm">
                                {{ app()->getLocale() === 'ar' ? 'إلغاء' : 'Cancel' }}
                            </button>
                        @endif
                    </div>
                </form>
            </div>

            <div class="customer-addresses-list">
                @forelse ($addresses as $address)
                    <div class="customer-address-card">
                        <div class="customer-address-card-head">
                            <div>
                                <h3>
                                    {{ $address->label ?: (app()->getLocale() === 'ar' ? 'عنوان' : 'Address') }}
                                </h3>

                                @if ($address->is_default)
                                    <span class="customer-address-badge">
                                        {{ app()->getLocale() === 'ar' ? 'افتراضي' : 'Default' }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="customer-address-details">
                            <p>
                                <strong>{{ app()->getLocale() === 'ar' ? 'المستلم:' : 'Recipient:' }}</strong>
                                {{ $address->name }}
                            </p>

                            <p>
                                <strong>{{ app()->getLocale() === 'ar' ? 'الهاتف:' : 'Phone:' }}</strong>
                                {{ $address->phone }}
                            </p>

                            <p>
                                <strong>{{ app()->getLocale() === 'ar' ? 'الدولة:' : 'Country:' }}</strong>
                                {{ $address->country }}
                            </p>

                            <p>
                                <strong>{{ app()->getLocale() === 'ar' ? 'المدينة:' : 'City:' }}</strong>
                                {{ $address->city }}
                            </p>

                            @if ($address->area)
                                <p>
                                    <strong>{{ app()->getLocale() === 'ar' ? 'المنطقة:' : 'Area:' }}</strong>
                                    {{ $address->area }}
                                </p>
                            @endif

                            <p>
                                <strong>{{ app()->getLocale() === 'ar' ? 'العنوان:' : 'Address:' }}</strong>
                                {{ $address->street }}
                            </p>

                            @if ($address->building)
                                <p>
                                    <strong>{{ app()->getLocale() === 'ar' ? 'المبنى:' : 'Building:' }}</strong>
                                    {{ $address->building }}
                                </p>
                            @endif

                            @if ($address->floor)
                                <p>
                                    <strong>{{ app()->getLocale() === 'ar' ? 'الدور:' : 'Floor:' }}</strong>
                                    {{ $address->floor }}
                                </p>
                            @endif

                            @if ($address->apartment)
                                <p>
                                    <strong>{{ app()->getLocale() === 'ar' ? 'الشقة:' : 'Apartment:' }}</strong>
                                    {{ $address->apartment }}
                                </p>
                            @endif

                            @if ($address->landmark)
                                <p>
                                    <strong>{{ app()->getLocale() === 'ar' ? 'علامة مميزة:' : 'Landmark:' }}</strong>
                                    {{ $address->landmark }}
                                </p>
                            @endif

                            @if ($address->notes)
                                <p>
                                    <strong>{{ app()->getLocale() === 'ar' ? 'ملاحظات:' : 'Notes:' }}</strong>
                                    {{ $address->notes }}
                                </p>
                            @endif
                        </div>

                        <div class="customer-address-actions">
                            <button type="button" wire:click="editAddress({{ $address->id }})">
                                {{ app()->getLocale() === 'ar' ? 'تعديل' : 'Edit' }}
                            </button>

                            @if (! $address->is_default)
                                <button type="button" wire:click="setDefault({{ $address->id }})">
                                    {{ app()->getLocale() === 'ar' ? 'افتراضي' : 'Set Default' }}
                                </button>
                            @endif

                            <button type="button" class="is-danger" wire:click="deleteAddress({{ $address->id }})">
                                {{ app()->getLocale() === 'ar' ? 'حذف' : 'Delete' }}
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="customer-address-empty">
                        <h3>{{ app()->getLocale() === 'ar' ? 'لا توجد عناوين بعد' : 'No addresses yet' }}</h3>

                        <p>
                            {{ app()->getLocale() === 'ar'
                                ? 'أضف أول عنوان لك لتسهيل عملية الطلب.'
                                : 'Add your first address to make checkout easier.' }}
                        </p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</section>