<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BlogPost extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'blog_category_id',
        'title_ar',
        'title_en',
        'slug_ar',
        'slug_en',
        'excerpt_ar',
        'excerpt_en',
        'content_ar',
        'content_en',
        'featured_image',
        'author_name',
        'is_featured',
        'status',
        'published_at',
        'meta_title_ar',
        'meta_title_en',
        'meta_description_ar',
        'meta_description_en',
        'sort_order',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'published_at' => 'datetime',
        'sort_order' => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(BlogCategory::class, 'blog_category_id');
    }

    public function getTitleAttribute(): string
    {
        return app()->getLocale() === 'ar'
            ? $this->title_ar
            : $this->title_en;
    }

    public function getSlugAttribute(): string
    {
        return app()->getLocale() === 'ar'
            ? $this->slug_ar
            : $this->slug_en;
    }

    public function getExcerptAttribute(): ?string
    {
        return app()->getLocale() === 'ar'
            ? $this->excerpt_ar
            : $this->excerpt_en;
    }

    public function getContentAttribute(): ?string
    {
        return app()->getLocale() === 'ar'
            ? $this->content_ar
            : $this->content_en;
    }

    public function getMetaTitleAttribute(): ?string
    {
        return app()->getLocale() === 'ar'
            ? $this->meta_title_ar
            : $this->meta_title_en;
    }

    public function getMetaDescriptionAttribute(): ?string
    {
        return app()->getLocale() === 'ar'
            ? $this->meta_description_ar
            : $this->meta_description_en;
    }

    public static function statuses(): array
    {
        return [
            'draft' => __('admin.blog_status_draft'),
            'published' => __('admin.blog_status_published'),
            'archived' => __('admin.blog_status_archived'),
        ];
    }

    public function scopePublished($query)
    {
        return $query
            ->where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }

    public function publish(): void
    {
        $this->update([
            'status' => 'published',
            'published_at' => $this->published_at ?: now(),
        ]);
    }

    public function archive(): void
    {
        $this->update([
            'status' => 'archived',
        ]);
    }
}