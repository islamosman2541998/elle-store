<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Page extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title_ar',
        'title_en',
        'slug_ar',
        'slug_en',
        'short_description_ar',
        'short_description_en',
        'content_ar',
        'content_en',
        'main_image',
        'meta_title_ar',
        'meta_title_en',
        'meta_description_ar',
        'meta_description_en',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function images()
    {
        return $this->hasMany(PageImage::class)
            ->orderBy('sort_order');
    }

    public function activeImages()
    {
        return $this->hasMany(PageImage::class)
            ->where('is_active', true)
            ->orderBy('sort_order');
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

    public function getShortDescriptionAttribute(): ?string
    {
        return app()->getLocale() === 'ar'
            ? $this->short_description_ar
            : $this->short_description_en;
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

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}