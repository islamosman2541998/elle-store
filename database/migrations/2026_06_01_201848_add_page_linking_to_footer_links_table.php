<?php

use App\Models\Page;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('footer_links', function (Blueprint $table) {
            $table->string('link_type')->default('custom')->after('group');

            $table->foreignIdFor(Page::class)
                ->nullable()
                ->after('link_type')
                ->constrained()
                ->nullOnDelete();

            $table->string('url')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('footer_links', function (Blueprint $table) {
            $table->dropConstrainedForeignIdFor(Page::class);
            $table->dropColumn('link_type');

            $table->string('url')->nullable(false)->change();
        });
    }
};