<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BlogCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name_ar',
        'name_en',
        'slug_ar',
        'slug_en',
        'description_ar',
        'description_en',
        'image',
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

    public function posts()
    {
        return $this->hasMany(BlogPost::class, 'blog_category_id');
    }

    public function activePosts()
    {
        return $this->hasMany(BlogPost::class, 'blog_category_id')
            ->published();
    }

    public function getNameAttribute(): string
    {
        return app()->getLocale() === 'ar'
            ? $this->name_ar
            : $this->name_en;
    }

    public function getSlugAttribute(): string
    {
        return app()->getLocale() === 'ar'
            ? $this->slug_ar
            : $this->slug_en;
    }

    public function getDescriptionAttribute(): ?string
    {
        return app()->getLocale() === 'ar'
            ? $this->description_ar
            : $this->description_en;
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