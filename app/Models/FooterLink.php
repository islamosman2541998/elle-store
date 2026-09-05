<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FooterLink extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'group',
        'link_type',
        'page_id',
        'title_ar',
        'title_en',
        'url',
        'open_in_new_tab',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'open_in_new_tab' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function page()
    {
        return $this->belongsTo(Page::class);
    }

    public function getTitleAttribute(): string
    {
        return app()->getLocale() === 'ar'
            ? $this->title_ar
            : $this->title_en;
    }

    public function getResolvedUrlAttribute(): string
    {
        if ($this->link_type === 'page' && $this->page) {
            $slug = app()->getLocale() === 'ar'
                ? $this->page->slug_ar
                : $this->page->slug_en;

            return route('site.pages.show', $slug);
        }

        return $this->url ?: '#';
    }

    public static function groups(): array
    {
        return [
            'main' => __('admin.footer_group_main'),
            'customer_service' => __('admin.footer_group_customer_service'),
            'policies' => __('admin.footer_group_policies'),
            'quick_links' => __('admin.footer_group_quick_links'),
        ];
    }

    public static function linkTypes(): array
    {
        return [
            'custom' => __('admin.custom_url'),
            'page' => __('admin.page'),
        ];
    }
}