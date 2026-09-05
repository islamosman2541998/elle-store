<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->boolean('tracking_enabled')->default(false)->after('bing_site_verification');

            $table->string('meta_pixel_id')->nullable()->after('tracking_enabled');
            $table->text('meta_capi_access_token')->nullable()->after('meta_pixel_id');
            $table->string('meta_test_event_code')->nullable()->after('meta_capi_access_token');

            $table->string('google_tag_manager_id')->nullable()->after('meta_test_event_code');
            $table->string('ga4_measurement_id')->nullable()->after('google_tag_manager_id');

            $table->string('google_ads_conversion_id')->nullable()->after('ga4_measurement_id');
            $table->string('google_ads_conversion_label')->nullable()->after('google_ads_conversion_id');

            $table->string('tiktok_pixel_id')->nullable()->after('google_ads_conversion_label');
            $table->string('snapchat_pixel_id')->nullable()->after('tiktok_pixel_id');
            $table->string('linkedin_partner_id')->nullable()->after('snapchat_pixel_id');
            $table->string('pinterest_tag_id')->nullable()->after('linkedin_partner_id');
            $table->string('twitter_pixel_id')->nullable()->after('pinterest_tag_id');

            $table->boolean('cookie_consent_enabled')->default(true)->after('twitter_pixel_id');
            $table->text('cookie_consent_message_ar')->nullable()->after('cookie_consent_enabled');
            $table->text('cookie_consent_message_en')->nullable()->after('cookie_consent_message_ar');
        });
    }

    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->dropColumn([
                'tracking_enabled',
                'meta_pixel_id',
                'meta_capi_access_token',
                'meta_test_event_code',
                'google_tag_manager_id',
                'ga4_measurement_id',
                'google_ads_conversion_id',
                'google_ads_conversion_label',
                'tiktok_pixel_id',
                'snapchat_pixel_id',
                'linkedin_partner_id',
                'pinterest_tag_id',
                'twitter_pixel_id',
                'cookie_consent_enabled',
                'cookie_consent_message_ar',
                'cookie_consent_message_en',
            ]);
        });
    }
};