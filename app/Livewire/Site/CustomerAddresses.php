<?php

namespace App\Livewire\Site;

use App\Models\CustomerAddress;
use Livewire\Component;

class CustomerAddresses extends Component
{
    public ?int $editingAddressId = null;

    public string $label = '';

    public string $name = '';

    public string $phone = '';

    public string $country = '';

    public string $city = '';

    public string $area = '';

    public string $street = '';

    public string $building = '';

    public string $floor = '';

    public string $apartment = '';

    public string $landmark = '';

    public string $notes = '';

    public bool $is_default = false;

    public function saveAddress(): void
    {
        $customer = auth('customer')->user();

        $this->validate([
            'label' => ['nullable', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'country' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:150'],
            'area' => ['nullable', 'string', 'max:150'],
            'street' => ['required', 'string', 'max:255'],
            'building' => ['nullable', 'string', 'max:100'],
            'floor' => ['nullable', 'string', 'max:100'],
            'apartment' => ['nullable', 'string', 'max:100'],
            'landmark' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'name.required' => app()->getLocale() === 'ar' ? 'اسم المستلم مطلوب' : 'Recipient name is required',
            'phone.required' => app()->getLocale() === 'ar' ? 'رقم الهاتف مطلوب' : 'Phone number is required',
            'country.required' => app()->getLocale() === 'ar' ? 'الدولة مطلوبة' : 'Country is required',
            'city.required' => app()->getLocale() === 'ar' ? 'المدينة مطلوبة' : 'City is required',
            'street.required' => app()->getLocale() === 'ar' ? 'العنوان / الشارع مطلوب' : 'Street address is required',
        ]);

        $hasAddresses = CustomerAddress::query()
            ->where('customer_id', $customer->id)
            ->exists();

        $makeDefault = $this->is_default || ! $hasAddresses;

        if ($makeDefault) {
            CustomerAddress::query()
                ->where('customer_id', $customer->id)
                ->update(['is_default' => false]);
        }

        CustomerAddress::query()->updateOrCreate(
            [
                'id' => $this->editingAddressId,
                'customer_id' => $customer->id,
            ],
            [
                'label' => trim($this->label) ?: null,
                'name' => trim($this->name),
                'phone' => trim($this->phone),
                'country' => trim($this->country),
                'city' => trim($this->city),
                'area' => trim($this->area) ?: null,
                'street' => trim($this->street),
                'building' => trim($this->building) ?: null,
                'floor' => trim($this->floor) ?: null,
                'apartment' => trim($this->apartment) ?: null,
                'landmark' => trim($this->landmark) ?: null,
                'notes' => trim($this->notes) ?: null,
                'is_default' => $makeDefault,
            ]
        );

        $this->resetForm();

        $this->dispatch(
            'site-toast',
            type: 'success',
            icon: '✓',
            title: app()->getLocale() === 'ar' ? 'تم حفظ العنوان' : 'Address Saved',
            message: app()->getLocale() === 'ar'
                ? 'تم حفظ العنوان بنجاح'
                : 'Address has been saved successfully'
        );
    }

    public function editAddress(int $addressId): void
    {
        $address = CustomerAddress::query()
            ->where('customer_id', auth('customer')->id())
            ->findOrFail($addressId);

       $this->editingAddressId = $address->id;
$this->label = (string) ($address->label ?? '');
$this->name = (string) ($address->name ?? '');
$this->phone = (string) ($address->phone ?? '');
$this->country = (string) ($address->country ?? '');
$this->city = (string) ($address->city ?? '');
$this->area = (string) ($address->area ?? '');
$this->street = (string) ($address->street ?? '');
$this->building = (string) ($address->building ?? '');
$this->floor = (string) ($address->floor ?? '');
$this->apartment = (string) ($address->apartment ?? '');
$this->landmark = (string) ($address->landmark ?? '');
$this->notes = (string) ($address->notes ?? '');
$this->is_default = (bool) $address->is_default;
    }

    public function setDefault(int $addressId): void
    {
        $customerId = auth('customer')->id();

        $address = CustomerAddress::query()
            ->where('customer_id', $customerId)
            ->findOrFail($addressId);

        CustomerAddress::query()
            ->where('customer_id', $customerId)
            ->update(['is_default' => false]);

        $address->update(['is_default' => true]);

        $this->dispatch(
            'site-toast',
            type: 'success',
            icon: '✓',
            title: app()->getLocale() === 'ar' ? 'تم تعيين العنوان' : 'Default Address Updated',
            message: app()->getLocale() === 'ar'
                ? 'تم تعيين هذا العنوان كعنوان افتراضي'
                : 'This address is now your default address'
        );
    }

    public function deleteAddress(int $addressId): void
    {
        $customerId = auth('customer')->id();

        $address = CustomerAddress::query()
            ->where('customer_id', $customerId)
            ->findOrFail($addressId);

        $wasDefault = (bool) $address->is_default;

        $address->delete();

        if ($wasDefault) {
            $nextAddress = CustomerAddress::query()
                ->where('customer_id', $customerId)
                ->oldest()
                ->first();

            if ($nextAddress) {
                $nextAddress->update(['is_default' => true]);
            }
        }

        if ($this->editingAddressId === $addressId) {
            $this->resetForm();
        }

        $this->dispatch(
            'site-toast',
            type: 'success',
            icon: '✓',
            title: app()->getLocale() === 'ar' ? 'تم حذف العنوان' : 'Address Deleted',
            message: app()->getLocale() === 'ar'
                ? 'تم حذف العنوان بنجاح'
                : 'Address has been deleted successfully'
        );
    }

    public function resetForm(): void
    {
        $this->reset([
    'editingAddressId',
    'label',
    'name',
    'phone',
    'country',
    'city',
    'area',
    'street',
    'building',
    'floor',
    'apartment',
    'landmark',
    'notes',
    'is_default',
]);

        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.site.customer-addresses', [
            'addresses' => CustomerAddress::query()
                ->where('customer_id', auth('customer')->id())
                ->latest('is_default')
                ->latest()
                ->get(),
        ]);
    }
}