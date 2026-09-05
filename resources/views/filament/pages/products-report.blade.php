<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            {{ $this->form }}
        </x-filament::section>

        @php
            $summary = $this->getSummary();
            $products = $this->getProducts();
        @endphp

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-6">
            <x-filament::section>
                <div class="text-sm text-gray-500">{{ __('admin.products_count') }}</div>
                <div class="mt-2 text-2xl font-bold">{{ number_format($summary['products_count']) }}</div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-sm text-gray-500">{{ __('admin.active_products') }}</div>
                <div class="mt-2 text-2xl font-bold">{{ number_format($summary['active_products_count']) }}</div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-sm text-gray-500">{{ __('admin.featured_products') }}</div>
                <div class="mt-2 text-2xl font-bold">{{ number_format($summary['featured_products_count']) }}</div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-sm text-gray-500">{{ __('admin.low_stock_products') }}</div>
                <div class="mt-2 text-2xl font-bold text-warning-600">{{ number_format($summary['low_stock_products_count']) }}</div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-sm text-gray-500">{{ __('admin.out_of_stock_products') }}</div>
                <div class="mt-2 text-2xl font-bold text-danger-600">{{ number_format($summary['out_of_stock_products_count']) }}</div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-sm text-gray-500">{{ __('admin.stock_total_quantity') }}</div>
                <div class="mt-2 text-2xl font-bold text-primary-600">{{ number_format($summary['stock_total_quantity']) }}</div>
            </x-filament::section>
        </div>

        <x-filament::section>
            <x-slot name="heading">
                {{ __('admin.products_report') }}
            </x-slot>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b">
                            <th class="px-3 py-3 text-start">{{ __('admin.product') }}</th>
                            <th class="px-3 py-3 text-start">{{ __('admin.sku') }}</th>
                            <th class="px-3 py-3 text-start">{{ __('admin.category') }}</th>
                            <th class="px-3 py-3 text-start">{{ __('admin.brand') }}</th>
                            <th class="px-3 py-3 text-start">{{ __('admin.price') }}</th>
                            <th class="px-3 py-3 text-start">{{ __('admin.sale_price') }}</th>
                            <th class="px-3 py-3 text-start">{{ __('admin.stock_quantity') }}</th>
                            <th class="px-3 py-3 text-start">{{ __('admin.sold_quantity') }}</th>
                            <th class="px-3 py-3 text-start">{{ __('admin.status') }}</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($products as $product)
                            <tr class="border-b">
                                <td class="px-3 py-3">{{ $product->transNow?->name ?? '-' }}</td>
                                <td class="px-3 py-3">{{ $product->sku ?? '-' }}</td>
                                <td class="px-3 py-3">{{ $product->category?->transNow?->name ?? '-' }}</td>
                                <td class="px-3 py-3">{{ $product->brand?->transNow?->name ?? '-' }}</td>
                                <td class="px-3 py-3">{{ number_format((float) $product->price, 2) }} EGP</td>
                                <td class="px-3 py-3">
                                    @if ($product->sale_price)
                                        {{ number_format((float) $product->sale_price, 2) }} EGP
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-3 py-3">
                                    {{ number_format((int) $product->stock_quantity) }}

                                    @if ($product->manage_stock && $product->stock_quantity <= $product->low_stock_alert)
                                        <span class="text-warning-600">
                                            ({{ __('admin.low_stock') }})
                                        </span>
                                    @endif
                                </td>
                                <td class="px-3 py-3">{{ number_format($this->getSoldQuantityForProduct($product->id)) }}</td>
                                <td class="px-3 py-3">
                                    {{ $product->is_active ? __('admin.active') : __('admin.inactive') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-3 py-6 text-center text-gray-500">
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