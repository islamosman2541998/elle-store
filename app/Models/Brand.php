<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Brand extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'logo',
        'is_active',
        'is_featured',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
    ];

    public function translations()
    {
        return $this->hasMany(BrandTranslation::class);
    }

    public function translation($locale = null)
    {
        $locale = $locale ?? app()->getLocale();

        return $this->hasOne(BrandTranslation::class)
            ->where('locale', $locale);
    }

    public function transNow()
    {
        return $this->hasOne(BrandTranslation::class)
            ->where('locale', app()->getLocale());
    }

    public function arabicTranslation()
    {
        return $this->hasOne(BrandTranslation::class)
            ->where('locale', 'ar');
    }

    public function englishTranslation()
    {
        return $this->hasOne(BrandTranslation::class)
            ->where('locale', 'en');
    }
    public function products()
{
    return $this->hasMany(Product::class);
}
}