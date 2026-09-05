<?php

namespace App\Livewire\Site;

use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class CustomerOrders extends Component
{
    use WithPagination;

    public string $phone = '';

    public string $email = '';

    public bool $verified = false;

    public function mount(): void
    {
        $this->verified = $this->hasAuthenticatedCustomer();
    }

    public function verifyOrders(): void
    {
        $this->validate([
            'phone' => ['nullable', 'string', 'max:50', 'required_without:email'],
            'email' => ['nullable', 'email', 'max:255', 'required_without:phone'],
        ], [
            'phone.required_without' => app()->getLocale() === 'ar'
                ? 'أدخل رقم الهاتف أو البريد الإلكتروني'
                : 'Enter phone number or email address',

            'email.required_without' => app()->getLocale() === 'ar'
                ? 'أدخل البريد الإلكتروني أو رقم الهاتف'
                : 'Enter email address or phone number',

            'email.email' => app()->getLocale() === 'ar'
                ? 'صيغة البريد الإلكتروني غير صحيحة'
                : 'Invalid email address',
        ]);

        $this->phone = filled($this->phone)
            ? trim($this->phone)
            : '';

        $this->email = filled($this->email)
            ? mb_strtolower(trim($this->email))
            : '';

        $exists = Order::query()
            ->where(function (Builder $query) {
                if (filled($this->phone)) {
                    $query->orWhere('customer_phone', $this->phone);
                }

                if (filled($this->email)) {
                    $query->orWhere('customer_email', $this->email);
                }
            })
            ->exists();

        if (! $exists) {
            $this->dispatch(
                'site-toast',
                type: 'error',
                icon: '!',
                title: app()->getLocale() === 'ar' ? 'لا توجد طلبات' : 'No orders found',
                message: app()->getLocale() === 'ar'
                    ? 'لم نجد طلبات مطابقة لهذه البيانات'
                    : 'No orders match these details'
            );

            return;
        }

        $this->verified = true;
        $this->resetPage();

        $this->dispatch(
            'site-toast',
            type: 'success',
            icon: '✓',
            title: app()->getLocale() === 'ar' ? 'تم العثور على الطلبات' : 'Orders found',
            message: app()->getLocale() === 'ar'
                ? 'تم عرض طلباتك بنجاح'
                : 'Your orders are now visible'
        );
    }

    public function openOrder(string $orderNumber): void
    {
        $order = $this->ordersQuery()
            ->where('order_number', $orderNumber)
            ->first();

        if (! $order) {
            $this->dispatch(
                'site-toast',
                type: 'error',
                icon: '!',
                title: app()->getLocale() === 'ar' ? 'غير مسموح' : 'Not allowed',
                message: app()->getLocale() === 'ar'
                    ? 'لا يمكنك عرض هذا الطلب'
                    : 'You cannot view this order'
            );

            return;
        }

        session()->put('last_order_number', $order->order_number);

        $this->redirectRoute('site.orders.show', [
            'orderNumber' => $order->order_number,
        ]);
    }

    public function statusLabel(?string $status): string
    {
        return match ($status) {
            'pending' => app()->getLocale() === 'ar' ? 'قيد المراجعة' : 'Pending',
            'confirmed' => app()->getLocale() === 'ar' ? 'تم التأكيد' : 'Confirmed',
            'processing' => app()->getLocale() === 'ar' ? 'قيد التجهيز' : 'Processing',
            'shipped' => app()->getLocale() === 'ar' ? 'تم الشحن' : 'Shipped',
            'delivered' => app()->getLocale() === 'ar' ? 'تم التسليم' : 'Delivered',
            'cancelled' => app()->getLocale() === 'ar' ? 'ملغي' : 'Cancelled',
            'returned' => app()->getLocale() === 'ar' ? 'مرتجع' : 'Returned',
            default => $status ?: '-',
        };
    }

    public function statusColor(?string $status): string
    {
        return match ($status) {
            'confirmed', 'delivered' => 'success',
            'processing', 'shipped' => 'info',
            'cancelled', 'returned' => 'danger',
            'pending' => 'warning',
            default => 'gray',
        };
    }

    public function paymentStatusLabel(?string $status): string
    {
        return match ($status) {
            'unpaid' => app()->getLocale() === 'ar' ? 'غير مدفوع' : 'Unpaid',
            'pending' => app()->getLocale() === 'ar' ? 'قيد المراجعة' : 'Pending',
            'paid' => app()->getLocale() === 'ar' ? 'مدفوع' : 'Paid',
            'failed' => app()->getLocale() === 'ar' ? 'فشل الدفع' : 'Failed',
            'refunded' => app()->getLocale() === 'ar' ? 'مسترد' : 'Refunded',
            default => $status ?: '-',
        };
    }

    public function paymentStatusColor(?string $status): string
    {
        return match ($status) {
            'paid' => 'success',
            'pending' => 'info',
            'failed' => 'danger',
            'refunded' => 'gray',
            'unpaid' => 'warning',
            default => 'gray',
        };
    }

   private function ordersQuery(): Builder
{
    $query = Order::query()
        ->withCount('items')
        ->latest();

    $customerId = $this->authenticatedCustomerId();

    if ($customerId) {
        return $query->where('customer_id', $customerId);
    }

    if (! $this->verified) {
        return $query->whereRaw('1 = 0');
    }

    return $query->where(function (Builder $query) {
        if (filled($this->phone)) {
            $query->orWhere('customer_phone', $this->phone);
        }

        if (filled($this->email)) {
            $query->orWhere('customer_email', $this->email);
        }
    });
}

    private function hasAuthenticatedCustomer(): bool
    {
        return filled($this->authenticatedCustomerId());
    }

    private function authenticatedCustomerId(): ?int
    {
        try {
            return auth('customer')->id();
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function render()
    {
        return view('livewire.site.customer-orders', [
            'orders' => $this->ordersQuery()->paginate(8),
            'isAuthenticatedCustomer' => $this->hasAuthenticatedCustomer(),
        ]);
    }
}