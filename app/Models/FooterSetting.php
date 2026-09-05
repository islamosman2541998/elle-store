<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FooterSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'logo',
        'title_ar',
        'title_en',
        'description_ar',
        'description_en',
        'copyright_ar',
        'copyright_en',
        'show_social_links',
        'show_payment_methods',
        'show_newsletter',
        'newsletter_title_ar',
        'newsletter_title_en',
        'newsletter_description_ar',
        'newsletter_description_en',
        'is_active',
    ];

    protected $casts = [
        'show_social_links' => 'boolean',
        'show_payment_methods' => 'boolean',
        'show_newsletter' => 'boolean',
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
                'title_ar' => 'متجرنا',
                'title_en' => 'Our Store',
                'description_ar' => 'تسوق منتجاتك المفضلة بسهولة وأمان.',
                'description_en' => 'Shop your favorite products easily and securely.',
                'copyright_ar' => 'جميع الحقوق محفوظة.',
                'copyright_en' => 'All rights reserved.',
                'show_social_links' => true,
                'show_payment_methods' => true,
                'show_newsletter' => false,
                'is_active' => true,
            ]
        );
    }

    public function getTitleAttribute(): string
    {
        return app()->getLocale() === 'ar'
            ? ($this->title_ar ?: 'متجرنا')
            : ($this->title_en ?: 'Our Store');
    }

    public function getDescriptionAttribute(): ?string
    {
        return app()->getLocale() === 'ar'
            ? $this->description_ar
            : $this->description_en;
    }

    public function getCopyrightAttribute(): ?string
    {
        return app()->getLocale() === 'ar'
            ? $this->copyright_ar
            : $this->copyright_en;
    }

    public function getNewsletterTitleAttribute(): ?string
    {
        return app()->getLocale() === 'ar'
            ? $this->newsletter_title_ar
            : $this->newsletter_title_en;
    }

    public function getNewsletterDescriptionAttribute(): ?string
    {
        return app()->getLocale() === 'ar'
            ? $this->newsletter_description_ar
            : $this->newsletter_description_en;
    }
}