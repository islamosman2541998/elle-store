{{-- Clickable list of the placeholders valid for the template being edited. --}}
<div
    x-data="{
        copied: null,
        copy(token) {
            navigator.clipboard?.writeText('{' + token + '}');
            this.copied = token;
            setTimeout(() => { if (this.copied === token) this.copied = null }, 1200);
        }
    }"
    class="flex flex-wrap gap-2"
>
    @foreach ($tokens as $token)
        <button
            type="button"
            x-on:click="copy(@js($token))"
            class="group inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-2.5 py-1.5 text-xs transition hover:border-primary-500 hover:bg-primary-50 dark:border-gray-600 dark:bg-gray-800 dark:hover:border-primary-400 dark:hover:bg-gray-700"
            title="{{ $samples[$token] ?? '' }}"
        >
            <code class="font-mono text-primary-600 dark:text-primary-400" dir="ltr">{{ '{' . $token . '}' }}</code>

            <span class="text-gray-400 dark:text-gray-500" x-show="copied !== @js($token)">
                {{ \Illuminate\Support\Str::limit((string) ($samples[$token] ?? ''), 18) }}
            </span>

            <span class="font-semibold text-success-600 dark:text-success-400" x-show="copied === @js($token)" x-cloak>
                {{ __('admin.copied') }}
            </span>
        </button>
    @endforeach
</div>
