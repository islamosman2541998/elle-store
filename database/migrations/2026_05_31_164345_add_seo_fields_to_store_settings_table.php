<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->string('meta_title_ar')->nullable()->after('maintenance_message_en');
            $table->string('meta_title_en')->nullable()->after('meta_title_ar');

            $table->text('meta_description_ar')->nullable()->after('meta_title_en');
            $table->text('meta_description_en')->nullable()->after('meta_description_ar');

            $table->text('meta_keywords_ar')->nullable()->after('meta_description_en');
            $table->text('meta_keywords_en')->nullable()->after('meta_keywords_ar');

            $table->string('og_image')->nullable()->after('meta_keywords_en');

            $table->string('robots')->default('index, follow')->after('og_image');
            $table->string('canonical_url')->nullable()->after('robots');

            $table->string('google_site_verification')->nullable()->after('canonical_url');
            $table->string('bing_site_verification')->nullable()->after('google_site_verification');
        });
    }

    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->dropColumn([
                'meta_title_ar',
                'meta_title_en',
                'meta_description_ar',
                'meta_description_en',
                'meta_keywords_ar',
                'meta_keywords_en',
                'og_image',
                'robots',
                'canonical_url',
                'google_site_verification',
                'bing_site_verification',
            ]);
        });
    }
};