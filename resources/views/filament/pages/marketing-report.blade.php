<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            {{ $this->form }}
        </x-filament::section>

        @php
            $summary = $this->getSummary();
            $coupons = $this->getCoupons();
            $messages = $this->getContactMessages();
            $subscribers = $this->getNewsletterSubscribers();
            $trackingLogs = $this->getTrackingLogs();
        @endphp

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-filament::section>
                <div class="text-sm text-gray-500">{{ __('admin.active_coupons') }}</div>
                <div class="mt-2 text-2xl font-bold">{{ number_format($summary['active_coupons_count']) }}</div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-sm text-gray-500">{{ __('admin.coupons_used_count') }}</div>
                <div class="mt-2 text-2xl font-bold text-success-600">{{ number_format($summary['coupons_used_count']) }}
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-sm text-gray-500">{{ __('admin.active_flash_sales') }}</div>
                <div class="mt-2 text-2xl font-bold">{{ number_format($summary['active_flash_sales_count']) }}</div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-sm text-gray-500">{{ __('admin.active_product_discounts') }}</div>
                <div class="mt-2 text-2xl font-bold">{{ number_format($summary['active_product_discounts_count']) }}
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-sm text-gray-500">{{ __('admin.active_free_shipping_offers') }}</div>
                <div class="mt-2 text-2xl font-bold">{{ number_format($summary['active_free_shipping_offers_count']) }}
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-sm text-gray-500">{{ __('admin.contact_messages') }}</div>
                <div class="mt-2 text-2xl font-bold">{{ number_format($summary['contact_messages_count']) }}</div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-sm text-gray-500">{{ __('admin.newsletter_subscribers') }}</div>
                <div class="mt-2 text-2xl font-bold text-primary-600">
                    {{ number_format($summary['newsletter_subscribers_count']) }}</div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-sm text-gray-500">{{ __('admin.tracking_events') }}</div>
                <div class="mt-2 text-2xl font-bold">{{ number_format($summary['tracking_events_count']) }}</div>
            </x-filament::section>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <x-filament::section>
                <x-slot name="heading">
                    {{ __('admin.coupons') }}
                </x-slot>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b">
                                <th class="px-3 py-3 text-start">{{ __('admin.code') }}</th>
                                <th class="px-3 py-3 text-start">{{ __('admin.discount') }}</th>
                                <th class="px-3 py-3 text-start">{{ __('admin.used_count') }}</th>
                                <th class="px-3 py-3 text-start">{{ __('admin.status') }}</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($coupons as $coupon)
                                <tr class="border-b">
                                    <td class="px-3 py-3">{{ $coupon->code }}</td>
                                    <td class="px-3 py-3">{{ $coupon->value ?? ($coupon->discount_value ?? '-') }}</td>
                                    <td class="px-3 py-3">{{ number_format((int) $coupon->used_count) }}</td>
                                    <td class="px-3 py-3">
                                        {{ $coupon->is_active ? __('admin.active') : __('admin.inactive') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-3 py-6 text-center text-gray-500">
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
                    {{ __('admin.tracking_events') }}
                </x-slot>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b">
                                <th class="px-3 py-3 text-start">{{ __('admin.event_name') }}</th>
                                <th class="px-3 py-3 text-start">{{ __('admin.status') }}</th>
                                <th class="px-3 py-3 text-start">{{ __('admin.created_at') }}</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($trackingLogs as $log)
                                <tr class="border-b">
                                    <td class="px-3 py-3">{{ $log->event_name ?? '-' }}</td>
                                    <td class="px-3 py-3">
                                        {{ $log->status === 'success' ? __('admin.success') : ($log->status === 'failed' ? __('admin.failed') : $log->status) }}
                                    </td>
                                    <td class="px-3 py-3">{{ $log->created_at?->format('Y-m-d H:i') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-3 py-6 text-center text-gray-500">
                                        {{ __('admin.no_records_found') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <x-filament::section>
                <x-slot name="heading">
                    {{ __('admin.contact_messages') }}
                </x-slot>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b">
                                <th class="px-3 py-3 text-start">{{ __('admin.name') }}</th>
                                <th class="px-3 py-3 text-start">{{ __('admin.email') }}</th>
                                <th class="px-3 py-3 text-start">{{ __('admin.status') }}</th>
                                <th class="px-3 py-3 text-start">{{ __('admin.created_at') }}</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($messages as $message)
                                <tr class="border-b">
                                    <td class="px-3 py-3">{{ $message->name }}</td>
                                    <td class="px-3 py-3">{{ $message->email ?? '-' }}</td>
                                    <td class="px-3 py-3">
                                        {{ \App\Models\ContactMessage::statuses()[$message->status] ?? $message->status }}
                                    </td>
                                    <td class="px-3 py-3">{{ $message->created_at?->format('Y-m-d H:i') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-3 py-6 text-center text-gray-500">
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
                    {{ __('admin.newsletter_subscribers') }}
                </x-slot>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b">
                                <th class="px-3 py-3 text-start">{{ __('admin.email') }}</th>
                                <th class="px-3 py-3 text-start">{{ __('admin.status') }}</th>
                                <th class="px-3 py-3 text-start">{{ __('admin.source') }}</th>
                                <th class="px-3 py-3 text-start">{{ __('admin.created_at') }}</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($subscribers as $subscriber)
                                <tr class="border-b">
                                    <td class="px-3 py-3">{{ $subscriber->email }}</td>
                                    <td class="px-3 py-3">
                                        {{ \App\Models\NewsletterSubscriber::statuses()[$subscriber->status] ?? $subscriber->status }}
                                    </td>
                                    <td class="px-3 py-3">{{ $subscriber->source ?? '-' }}</td>
                                    <td class="px-3 py-3">{{ $subscriber->created_at?->format('Y-m-d H:i') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-3 py-6 text-center text-gray-500">
                                        {{ __('admin.no_records_found') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
