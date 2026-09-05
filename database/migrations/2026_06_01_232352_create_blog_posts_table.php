<?php

use App\Models\BlogCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(BlogCategory::class)
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('title_ar');
            $table->string('title_en');

            $table->string('slug_ar')->unique();
            $table->string('slug_en')->unique();

            $table->text('excerpt_ar')->nullable();
            $table->text('excerpt_en')->nullable();

            $table->longText('content_ar')->nullable();
            $table->longText('content_en')->nullable();

            $table->string('featured_image')->nullable();

            $table->string('author_name')->nullable();

            $table->boolean('is_featured')->default(false);
            $table->string('status')->default('draft');

            $table->timestamp('published_at')->nullable();

            $table->string('meta_title_ar')->nullable();
            $table->string('meta_title_en')->nullable();
            $table->text('meta_description_ar')->nullable();
            $table->text('meta_description_en')->nullable();

            $table->integer('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'published_at']);
            $table->index(['is_featured', 'sort_order']);
            $table->index('slug_ar');
            $table->index('slug_en');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_posts');
    }
};