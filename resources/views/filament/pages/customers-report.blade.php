<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            {{ $this->form }}
        </x-filament::section>

        @php
            $summary = $this->getSummary();
            $customers = $this->getCustomers();
        @endphp

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-6">
            <x-filament::section>
                <div class="text-sm text-gray-500">{{ __('admin.customers_count') }}</div>
                <div class="mt-2 text-2xl font-bold">{{ number_format($summary['customers_count']) }}</div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-sm text-gray-500">{{ __('admin.active_customers') }}</div>
                <div class="mt-2 text-2xl font-bold">{{ number_format($summary['active_customers_count']) }}</div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-sm text-gray-500">{{ __('admin.inactive_customers') }}</div>
                <div class="mt-2 text-2xl font-bold">{{ number_format($summary['inactive_customers_count']) }}</div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-sm text-gray-500">{{ __('admin.customers_with_orders') }}</div>
                <div class="mt-2 text-2xl font-bold text-success-600">{{ number_format($summary['customers_with_orders_count']) }}</div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-sm text-gray-500">{{ __('admin.customers_without_orders') }}</div>
                <div class="mt-2 text-2xl font-bold text-warning-600">{{ number_format($summary['customers_without_orders_count']) }}</div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-sm text-gray-500">{{ __('admin.total_customer_orders') }}</div>
                <div class="mt-2 text-2xl font-bold text-primary-600">{{ number_format($summary['total_customer_orders']) }}</div>
            </x-filament::section>
        </div>

        <x-filament::section>
            <x-slot name="heading">
                {{ __('admin.customers_report') }}
            </x-slot>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b">
                            <th class="px-3 py-3 text-start">{{ __('admin.customer_name') }}</th>
                            <th class="px-3 py-3 text-start">{{ __('admin.email') }}</th>
                            <th class="px-3 py-3 text-start">{{ __('admin.phone') }}</th>
                            <th class="px-3 py-3 text-start">{{ __('admin.orders_count') }}</th>
                            <th class="px-3 py-3 text-start">{{ __('admin.orders_total') }}</th>
                            <th class="px-3 py-3 text-start">{{ __('admin.status') }}</th>
                            <th class="px-3 py-3 text-start">{{ __('admin.created_at') }}</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($customers as $customer)
                            <tr class="border-b">
                                <td class="px-3 py-3">{{ $customer->name ?? '-' }}</td>
                                <td class="px-3 py-3">{{ $customer->email ?? '-' }}</td>
                                <td class="px-3 py-3">{{ $customer->phone ?? '-' }}</td>
                                <td class="px-3 py-3">{{ number_format((int) $customer->orders_count) }}</td>
                                <td class="px-3 py-3">{{ number_format($this->getCustomerOrdersTotal($customer->id), 2) }} EGP</td>
                                <td class="px-3 py-3">
                                    {{ $customer->is_active ? __('admin.active') : __('admin.inactive') }}
                                </td>
                                <td class="px-3 py-3">{{ $customer->created_at?->format('Y-m-d H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-3 py-6 text-center text-gray-500">
                                    {{ __('admin.no_records_found') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>