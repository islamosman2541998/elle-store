<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            {{ $this->form }}
        </x-filament::section>

        @php
            $summary = $this->getSummary();
            $products = $this->getProducts();
            $movements = $this->getLatestMovements();
        @endphp

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-7">
            <x-filament::section>
                <div class="text-sm text-gray-500">{{ __('admin.products_count') }}</div>
                <div class="mt-2 text-2xl font-bold">{{ number_format($summary['products_count']) }}</div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-sm text-gray-500">{{ __('admin.stock_total_quantity') }}</div>
                <div class="mt-2 text-2xl font-bold text-primary-600">{{ number_format($summary['stock_total_quantity']) }}</div>
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
                <div class="text-sm text-gray-500">{{ __('admin.stock_movements_count') }}</div>
                <div class="mt-2 text-2xl font-bold">{{ number_format($summary['stock_movements_count']) }}</div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-sm text-gray-500">{{ __('admin.stock_in_total') }}</div>
                <div class="mt-2 text-2xl font-bold text-success-600">{{ number_format($summary['stock_in_total']) }}</div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-sm text-gray-500">{{ __('admin.stock_out_total') }}</div>
                <div class="mt-2 text-2xl font-bold text-danger-600">{{ number_format($summary['stock_out_total']) }}</div>
            </x-filament::section>
        </div>

        <x-filament::section>
            <x-slot name="heading">
                {{ __('admin.inventory_report') }}
            </x-slot>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b">
                            <th class="px-3 py-3 text-start">{{ __('admin.product') }}</th>
                            <th class="px-3 py-3 text-start">{{ __('admin.sku') }}</th>
                            <th class="px-3 py-3 text-start">{{ __('admin.category') }}</th>
                            <th class="px-3 py-3 text-start">{{ __('admin.brand') }}</th>
                            <th class="px-3 py-3 text-start">{{ __('admin.stock_quantity') }}</th>
                            <th class="px-3 py-3 text-start">{{ __('admin.low_stock_alert') }}</th>
                            <th class="px-3 py-3 text-start">{{ __('admin.stock_status') }}</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($products as $product)
                            <tr class="border-b">
                                <td class="px-3 py-3">{{ $product->transNow?->name ?? '-' }}</td>
                                <td class="px-3 py-3">{{ $product->sku ?? '-' }}</td>
                                <td class="px-3 py-3">{{ $product->category?->transNow?->name ?? '-' }}</td>
                                <td class="px-3 py-3">{{ $product->brand?->transNow?->name ?? '-' }}</td>
                                <td class="px-3 py-3">{{ number_format((int) $product->stock_quantity) }}</td>
                                <td class="px-3 py-3">{{ number_format((int) $product->low_stock_alert) }}</td>
                                <td class="px-3 py-3">{{ $this->getProductStockStatusLabel($product) }}</td>
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

        <x-filament::section>
            <x-slot name="heading">
                {{ __('admin.latest_stock_movements') }}
            </x-slot>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b">
                            <th class="px-3 py-3 text-start">{{ __('admin.product') }}</th>
                            <th class="px-3 py-3 text-start">{{ __('admin.movement_type') }}</th>
                            <th class="px-3 py-3 text-start">{{ __('admin.quantity') }}</th>
                            <th class="px-3 py-3 text-start">{{ __('admin.notes') }}</th>
                            <th class="px-3 py-3 text-start">{{ __('admin.created_at') }}</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($movements as $movement)
                            <tr class="border-b">
                                <td class="px-3 py-3">{{ $movement->product?->transNow?->name ?? '-' }}</td>
                                <td class="px-3 py-3">
                                    {{ \App\Models\StockMovement::types()[$movement->type] ?? $movement->type }}
                                </td>
                                <td class="px-3 py-3">{{ number_format((int) $movement->quantity) }}</td>
                                <td class="px-3 py-3">{{ $movement->notes ?? '-' }}</td>
                                <td class="px-3 py-3">{{ $movement->created_at?->format('Y-m-d H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-3 py-6 text-center text-gray-500">
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