<?php

namespace App\Livewire\Site;

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;

class CustomerAccount extends Component
{
    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(): void
    {
        $customer = auth('customer')->user();

        $this->name = $customer->name ?? '';
        $this->email = $customer->email ?? '';
        $this->phone = $customer->phone ?? '';
    }

    public function updateProfile(): void
    {
        $customer = auth('customer')->user();

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('customers', 'email')->ignore($customer->id),
            ],
            'phone' => [
                'required',
                'string',
                'max:50',
                Rule::unique('customers', 'phone')->ignore($customer->id),
            ],
        ], [
            'name.required' => app()->getLocale() === 'ar' ? 'الاسم مطلوب' : 'Name is required',

            'email.required' => app()->getLocale() === 'ar' ? 'البريد الإلكتروني مطلوب' : 'Email is required',
            'email.email' => app()->getLocale() === 'ar' ? 'صيغة البريد الإلكتروني غير صحيحة' : 'Invalid email address',
            'email.unique' => app()->getLocale() === 'ar' ? 'هذا البريد مستخدم بالفعل' : 'This email is already used',

            'phone.required' => app()->getLocale() === 'ar' ? 'رقم الهاتف مطلوب' : 'Phone number is required',
            'phone.unique' => app()->getLocale() === 'ar' ? 'رقم الهاتف مستخدم بالفعل' : 'This phone number is already used',
        ]);

        $customer->update([
            'name' => trim($this->name),
            'email' => mb_strtolower(trim($this->email)),
            'phone' => trim($this->phone),
        ]);

        $this->dispatch('site-toast',
            type: 'success',
            icon: '✓',
            title: app()->getLocale() === 'ar' ? 'تم تحديث الحساب' : 'Account Updated',
            message: app()->getLocale() === 'ar'
                ? 'تم حفظ بيانات حسابك بنجاح'
                : 'Your account details have been saved successfully'
        );
    }

    public function updatePassword(): void
    {
        $customer = auth('customer')->user();

        $this->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'current_password.required' => app()->getLocale() === 'ar'
                ? 'كلمة المرور الحالية مطلوبة'
                : 'Current password is required',

            'password.required' => app()->getLocale() === 'ar'
                ? 'كلمة المرور الجديدة مطلوبة'
                : 'New password is required',

            'password.min' => app()->getLocale() === 'ar'
                ? 'كلمة المرور الجديدة يجب ألا تقل عن 8 أحرف'
                : 'New password must be at least 8 characters',

            'password.confirmed' => app()->getLocale() === 'ar'
                ? 'تأكيد كلمة المرور الجديدة غير مطابق'
                : 'New password confirmation does not match',
        ]);

        if (! Hash::check($this->current_password, $customer->password)) {
            $this->addError(
                'current_password',
                app()->getLocale() === 'ar'
                    ? 'كلمة المرور الحالية غير صحيحة'
                    : 'Current password is incorrect'
            );

            return;
        }

        $customer->forceFill([
            'password' => Hash::make($this->password),
        ])->save();

        $this->reset([
            'current_password',
            'password',
            'password_confirmation',
        ]);

        $this->dispatch('site-toast',
            type: 'success',
            icon: '✓',
            title: app()->getLocale() === 'ar' ? 'تم تغيير كلمة المرور' : 'Password Updated',
            message: app()->getLocale() === 'ar'
                ? 'تم تغيير كلمة المرور بنجاح'
                : 'Your password has been changed successfully'
        );
    }

    public function logout(): void
    {
        auth('customer')->logout();

        session()->regenerateToken();

        $this->redirectRoute('site.home');
    }

    public function render()
    {
        return view('livewire.site.customer-account', [
            'customer' => auth('customer')->user(),
        ]);
    }
}