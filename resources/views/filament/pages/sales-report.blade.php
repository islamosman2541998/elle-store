<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            {{ $this->form }}
        </x-filament::section>

        @php
            $summary = $this->getSummary();
            $orders = $this->getLatestOrders();
        @endphp

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-6">
            <x-filament::section>
                <div class="text-sm text-gray-500">{{ __('admin.orders_count') }}</div>
                <div class="mt-2 text-2xl font-bold">{{ number_format($summary['orders_count']) }}</div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-sm text-gray-500">{{ __('admin.subtotal') }}</div>
                <div class="mt-2 text-2xl font-bold">{{ number_format($summary['subtotal'], 2) }} EGP</div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-sm text-gray-500">{{ __('admin.discount_total') }}</div>
                <div class="mt-2 text-2xl font-bold">{{ number_format($summary['discount_total'], 2) }} EGP</div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-sm text-gray-500">{{ __('admin.shipping_total') }}</div>
                <div class="mt-2 text-2xl font-bold">{{ number_format($summary['shipping_total'], 2) }} EGP</div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-sm text-gray-500">{{ __('admin.tax_total') }}</div>
                <div class="mt-2 text-2xl font-bold">{{ number_format($summary['tax_total'], 2) }} EGP</div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-sm text-gray-500">{{ __('admin.grand_total') }}</div>
                <div class="mt-2 text-2xl font-bold text-primary-600">{{ number_format($summary['grand_total'], 2) }}
                    EGP</div>
            </x-filament::section>
        </div>

        <x-filament::section>
            <x-slot name="heading">
                {{ __('admin.latest_orders') }}
            </x-slot>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b">
                            <th class="px-3 py-3 text-start">{{ __('admin.order_number') }}</th>
                            <th class="px-3 py-3 text-start">{{ __('admin.customer_name') }}</th>
                            <th class="px-3 py-3 text-start">{{ __('admin.status') }}</th>
                            <th class="px-3 py-3 text-start">{{ __('admin.payment_status') }}</th>
                            <th class="px-3 py-3 text-start">{{ __('admin.payment_method') }}</th>
                            <th class="px-3 py-3 text-start">{{ __('admin.grand_total') }}</th>
                            <th class="px-3 py-3 text-start">{{ __('admin.created_at') }}</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($orders as $order)
                            <tr class="border-b">
                                <td class="px-3 py-3">{{ $order->order_number }}</td>
                                <td class="px-3 py-3">{{ $order->customer_name }}</td>
                                <td class="px-3 py-3">{{ __('admin.order_status_' . $order->status) }}</td>
                                <td class="px-3 py-3">{{ __('admin.payment_status_' . $order->payment_status) }}</td>
                                <td class="px-3 py-3">
                                    {{ __('admin.payment_method_' . $order->payment_method) }}
                                </td>
                                <td class="px-3 py-3">{{ number_format((float) $order->grand_total, 2) }} EGP</td>
                                <td class="px-3 py-3">{{ $order->created_at?->format('Y-m-d H:i') }}</td>
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
