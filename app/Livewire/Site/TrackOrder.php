<?php

namespace App\Livewire\Site;

use App\Models\Order;
use Livewire\Component;

class TrackOrder extends Component
{
    public string $order_number = '';
    public string $phone = '';

    public function track(): void
    {
        $this->validate([
            'order_number' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
        ], [
            'order_number.required' => app()->getLocale() === 'ar'
                ? 'رقم الطلب مطلوب'
                : 'Order number is required',

            'phone.required' => app()->getLocale() === 'ar'
                ? 'رقم الهاتف مطلوب'
                : 'Phone number is required',
        ]);

        $orderNumber = trim($this->order_number);
        $phone = trim($this->phone);

        $order = Order::query()
            ->where('order_number', $orderNumber)
            ->where('customer_phone', $phone)
            ->first();

        if (! $order) {
            $this->dispatch('site-toast',
                type: 'error',
                icon: '!',
                title: app()->getLocale() === 'ar' ? 'لم يتم العثور على الطلب' : 'Order not found',
                message: app()->getLocale() === 'ar'
                    ? 'تأكد من رقم الطلب ورقم الهاتف'
                    : 'Please check the order number and phone number'
            );

            return;
        }

        session()->put('last_order_number', $order->order_number);

        $this->redirectRoute('site.orders.show', [
            'orderNumber' => $order->order_number,
        ]);
    }

    public function render()
    {
        return view('livewire.site.track-order');
    }
}