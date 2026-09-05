<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Three storefront additions, all driven from the dashboard:
 * InstaPay as a payment method, a welcome coupon popup, and the floating
 * WhatsApp button.
 *
 * The text columns are TEXT rather than VARCHAR on purpose. store_settings
 * has grown past MySQL's 65535-byte row limit, and a VARCHAR(255) in utf8mb4
 * reserves up to 1020 bytes of that budget while a TEXT reserves only a
 * pointer. Anything added here from now on has to follow the same rule.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->add([
            // ------------------------------------------------------ InstaPay
            'instapay_enabled' => fn (Blueprint $t) => $t->boolean('instapay_enabled')->default(false),
            'instapay_handle' => fn (Blueprint $t) => $t->text('instapay_handle')->nullable(),
            'instapay_link' => fn (Blueprint $t) => $t->text('instapay_link')->nullable(),
            'instapay_details_ar' => fn (Blueprint $t) => $t->text('instapay_details_ar')->nullable(),
            'instapay_details_en' => fn (Blueprint $t) => $t->text('instapay_details_en')->nullable(),

            // -------------------------------------------------- coupon popup
            'coupon_popup_enabled' => fn (Blueprint $t) => $t->boolean('coupon_popup_enabled')->default(false),
            'coupon_popup_title_ar' => fn (Blueprint $t) => $t->text('coupon_popup_title_ar')->nullable(),
            'coupon_popup_title_en' => fn (Blueprint $t) => $t->text('coupon_popup_title_en')->nullable(),
            'coupon_popup_text_ar' => fn (Blueprint $t) => $t->text('coupon_popup_text_ar')->nullable(),
            'coupon_popup_text_en' => fn (Blueprint $t) => $t->text('coupon_popup_text_en')->nullable(),
            'coupon_popup_code' => fn (Blueprint $t) => $t->text('coupon_popup_code')->nullable(),
            'coupon_popup_button_label_ar' => fn (Blueprint $t) => $t->text('coupon_popup_button_label_ar')->nullable(),
            'coupon_popup_button_label_en' => fn (Blueprint $t) => $t->text('coupon_popup_button_label_en')->nullable(),
            'coupon_popup_image' => fn (Blueprint $t) => $t->text('coupon_popup_image')->nullable(),
            // Seconds before it appears, and how long a dismissal lasts.
            'coupon_popup_delay' => fn (Blueprint $t) => $t->unsignedSmallInteger('coupon_popup_delay')->default(4),
            'coupon_popup_remember_days' => fn (Blueprint $t) => $t->unsignedSmallInteger('coupon_popup_remember_days')->default(7),

            // ---------------------------------------------- WhatsApp button
            'whatsapp_button_enabled' => fn (Blueprint $t) => $t->boolean('whatsapp_button_enabled')->default(true),
            'whatsapp_button_message_ar' => fn (Blueprint $t) => $t->text('whatsapp_button_message_ar')->nullable(),
            'whatsapp_button_message_en' => fn (Blueprint $t) => $t->text('whatsapp_button_message_en')->nullable(),
        ]);
    }

    /** Add one column at a time, skipping any that already landed. */
    private function add(array $columns): void
    {
        foreach ($columns as $name => $definition) {
            if (Schema::hasColumn('store_settings', $name)) {
                continue;
            }

            Schema::table('store_settings', $definition);
        }
    }

    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->dropColumn([
                'instapay_enabled', 'instapay_handle', 'instapay_link',
                'instapay_details_ar', 'instapay_details_en',
                'coupon_popup_enabled', 'coupon_popup_title_ar', 'coupon_popup_title_en',
                'coupon_popup_text_ar', 'coupon_popup_text_en', 'coupon_popup_code',
                'coupon_popup_button_label_ar', 'coupon_popup_button_label_en',
                'coupon_popup_image', 'coupon_popup_delay', 'coupon_popup_remember_days',
                'whatsapp_button_enabled', 'whatsapp_button_message_ar', 'whatsapp_button_message_en',
            ]);
        });
    }
};
