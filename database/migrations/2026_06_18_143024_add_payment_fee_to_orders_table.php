<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('orders', 'payment_fee')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->decimal('payment_fee', 10, 2)
                    ->default(0)
                    ->after('payment_method');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('orders', 'payment_fee')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('payment_fee');
            });
        }
    }
};