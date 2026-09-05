<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HomepageSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'slider_enabled',

        'featured_categories_enabled',
        'featured_categories_title_ar',
        'featured_categories_title_en',
        'featured_categories_limit',

        'featured_products_enabled',
        'featured_products_title_ar',
        'featured_products_title_en',
        'featured_products_limit',

        'new_products_enabled',
        'new_products_title_ar',
        'new_products_title_en',
        'new_products_limit',

        'flash_sales_enabled',
        'flash_sales_title_ar',
        'flash_sales_title_en',
        'flash_sales_limit',

        'brands_enabled',
        'brands_title_ar',
        'brands_title_en',
        'brands_limit',

        'best_sellers_enabled',
        'best_sellers_title_ar',
        'best_sellers_title_en',
        'best_sellers_limit',

        'features_strip_enabled',
        'category_banner_enabled',
        'social_strip_enabled',

        'is_active',
        
    ];

    protected $casts = [
        'slider_enabled' => 'boolean',

        'featured_categories_enabled' => 'boolean',
        'featured_categories_limit' => 'integer',

        'featured_products_enabled' => 'boolean',
        'featured_products_limit' => 'integer',

        'new_products_enabled' => 'boolean',
        'new_products_limit' => 'integer',

        'flash_sales_enabled' => 'boolean',
        'flash_sales_limit' => 'integer',

        'brands_enabled' => 'boolean',
        'brands_limit' => 'integer',

        'best_sellers_enabled' => 'boolean',
        'best_sellers_limit' => 'integer',
        'features_strip_enabled' => 'boolean',
        'category_banner_enabled' => 'boolean',
        'social_strip_enabled' => 'boolean',

        'is_active' => 'boolean',
        
    ];

    /** Memoised per request - there is only one row and it is read repeatedly. */
    private static ?self $currentInstance = null;

    public static function flushCurrent(): void
    {
        static::$currentInstance = null;
    }

    protected static function booted(): void
    {
        static::saved(static fn () => static::flushCurrent());
        static::deleted(static fn () => static::flushCurrent());
    }

    public static function current(): self
    {
        if (static::$currentInstance !== null) {
            return static::$currentInstance;
        }

        return static::$currentInstance = static::query()->firstOrCreate(
            ['id' => 1],
            [
                'slider_enabled' => true,

                'featured_categories_enabled' => true,
                'featured_categories_title_ar' => 'الأقسام المميزة',
                'featured_categories_title_en' => 'Featured Categories',
                'featured_categories_limit' => 8,

                'featured_products_enabled' => true,
                'featured_products_title_ar' => 'منتجات مميزة',
                'featured_products_title_en' => 'Featured Products',
                'featured_products_limit' => 8,

                'new_products_enabled' => true,
                'new_products_title_ar' => 'وصل حديثًا',
                'new_products_title_en' => 'New Arrivals',
                'new_products_limit' => 8,

                'flash_sales_enabled' => true,
                'flash_sales_title_ar' => 'العروض السريعة',
                'flash_sales_title_en' => 'Flash Sales',
                'flash_sales_limit' => 8,

                'brands_enabled' => true,
                'brands_title_ar' => 'العلامات التجارية',
                'brands_title_en' => 'Brands',
                'brands_limit' => 10,

                'is_active' => true,
            ]
        );
    }

    public function getFeaturedCategoriesTitleAttribute(): string
    {
        return app()->getLocale() === 'ar'
            ? ($this->featured_categories_title_ar ?: 'الأقسام المميزة')
            : ($this->featured_categories_title_en ?: 'Featured Categories');
    }

    public function getFeaturedProductsTitleAttribute(): string
    {
        return app()->getLocale() === 'ar'
            ? ($this->featured_products_title_ar ?: 'منتجات مميزة')
            : ($this->featured_products_title_en ?: 'Featured Products');
    }

    public function getNewProductsTitleAttribute(): string
    {
        return app()->getLocale() === 'ar'
            ? ($this->new_products_title_ar ?: 'وصل حديثًا')
            : ($this->new_products_title_en ?: 'New Arrivals');
    }

    public function getFlashSalesTitleAttribute(): string
    {
        return app()->getLocale() === 'ar'
            ? ($this->flash_sales_title_ar ?: 'العروض السريعة')
            : ($this->flash_sales_title_en ?: 'Flash Sales');
    }

    public function getBrandsTitleAttribute(): string
    {
        return app()->getLocale() === 'ar'
            ? ($this->brands_title_ar ?: 'العلامات التجارية')
            : ($this->brands_title_en ?: 'Brands');
    }

    public function getBestSellersTitleAttribute(): string
    {
        return app()->getLocale() === 'ar'
            ? ($this->best_sellers_title_ar ?: 'الأكثر مبيعًا')
            : ($this->best_sellers_title_en ?: 'Best Sellers');
    }
}