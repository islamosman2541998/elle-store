<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('wishlist_items', 'customer_id')) {
            DB::statement('ALTER TABLE wishlist_items MODIFY customer_id BIGINT UNSIGNED NULL');
        }

        Schema::table('wishlist_items', function (Blueprint $table) {
            if (! Schema::hasColumn('wishlist_items', 'session_id')) {
                $table->string('session_id')->nullable()->after('customer_id')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('wishlist_items', function (Blueprint $table) {
            if (Schema::hasColumn('wishlist_items', 'session_id')) {
                $table->dropColumn('session_id');
            }
        });
    }
};