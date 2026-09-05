{{-- Both language versions of the message being edited, filled with sample data. --}}
<div class="space-y-6 p-1">
    @foreach ($versions as $locale => $version)
        @php
            $rtl = $locale === 'ar';
            $hasContent = filled($version['body']);
        @endphp

        <div>
            <div class="mb-2 flex items-center gap-2">
                <span class="inline-flex rounded-md bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                    {{ $locale === 'ar' ? __('admin.arabic') : __('admin.english') }}
                </span>
            </div>

            @if (! $hasContent)
                <p class="rounded-lg border border-dashed border-gray-300 p-4 text-sm text-gray-500 dark:border-gray-600 dark:text-gray-400">
                    {{ __('admin.preview_empty') }}
                </p>
            @elseif ($channel === 'email')
                @php
                    $html = view('emails.store-notification', [
                        'storeSettings' => \App\Models\StoreSetting::current(),
                        'subjectLine' => $version['subject'] ?: '—',
                        'bodyText' => $version['body'],
                        'actionUrl' => $version['button'] ? url('/') : null,
                        'actionLabel' => $version['button'] ?: null,
                    ])->render();
                @endphp

                <p class="mb-2 text-xs text-gray-500 dark:text-gray-400">
                    <span class="font-semibold">{{ __('admin.message_subject') }}:</span>
                    {{ $version['subject'] ?: '—' }}
                </p>

                <iframe
                    srcdoc="{{ $html }}"
                    class="h-[26rem] w-full rounded-xl border border-gray-200 bg-white dark:border-gray-700"
                    sandbox=""
                    title="{{ $version['subject'] }}"
                ></iframe>
            @else
                {{-- A WhatsApp bubble, so line breaks and length are obvious. --}}
                <div class="rounded-xl bg-[#e5ddd5] p-4 dark:bg-gray-900">
                    <div
                        dir="{{ $rtl ? 'rtl' : 'ltr' }}"
                        class="relative ms-auto max-w-md rounded-xl bg-[#dcf8c6] px-3 py-2 text-sm leading-relaxed text-gray-800 shadow-sm dark:bg-emerald-900 dark:text-gray-100"
                        style="white-space: pre-wrap; word-break: break-word;"
                    >{{ $version['body'] }}</div>

                    <p class="mt-2 text-end text-[11px] text-gray-500 dark:text-gray-400">
                        {{ __('admin.characters') }}: {{ mb_strlen($version['body']) }}
                    </p>
                </div>
            @endif
        </div>
    @endforeach
</div>
