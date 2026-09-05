<?php

use App\Models\NotificationTemplate;
use App\Models\StoreSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Fill the template table with the built-in wording, then carry over the three
 * messages the shop could previously edit from the store settings screen so
 * nothing written there is lost.
 */
return new class extends Migration
{
    public function up(): void
    {
        NotificationTemplate::syncMissing();

        if (! Schema::hasColumn('store_settings', 'order_status_message_ar')) {
            return;
        }

        $settings = StoreSetting::query()->first();

        if (! $settings) {
            return;
        }

        $this->carry('admin_new_order', [
            'subject_ar' => $settings->new_order_email_subject_ar,
            'subject_en' => $settings->new_order_email_subject_en,
        ]);

        $this->carry('customer_order_status', [
            'body_ar' => $this->renameStatusToken($settings->order_status_message_ar),
            'body_en' => $this->renameStatusToken($settings->order_status_message_en),
        ]);

        $this->carry('customer_invoice', [
            'body_ar' => $settings->invoice_created_message_ar,
            'body_en' => $settings->invoice_created_message_en,
        ]);
    }

    /** Only overwrite with what the shop actually typed. */
    private function carry(string $event, array $values): void
    {
        $values = array_filter($values, fn ($value) => filled($value));

        if ($values === []) {
            return;
        }

        NotificationTemplate::query()
            ->where('event', $event)
            ->where('channel', 'email')
            ->update($values);
    }

    /** The old field used {status}; templates call it {order_status}. */
    private function renameStatusToken(?string $text): ?string
    {
        return $text === null ? null : str_replace('{status}', '{order_status}', $text);
    }

    public function down(): void
    {
        // The table drop in the previous migration covers this.
    }
};
