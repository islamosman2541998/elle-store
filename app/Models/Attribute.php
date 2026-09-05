<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Attribute extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'type',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function translations()
    {
        return $this->hasMany(AttributeTranslation::class);
    }

    public function translation($locale = null)
    {
        $locale = $locale ?? app()->getLocale();

        return $this->hasOne(AttributeTranslation::class)
            ->where('locale', $locale);
    }

    public function transNow()
    {
        return $this->hasOne(AttributeTranslation::class)
            ->where('locale', app()->getLocale());
    }

    public function arabicTranslation()
    {
        return $this->hasOne(AttributeTranslation::class)
            ->where('locale', 'ar');
    }

    public function englishTranslation()
    {
        return $this->hasOne(AttributeTranslation::class)
            ->where('locale', 'en');
    }

    public function values()
    {
        return $this->hasMany(AttributeValue::class)
            ->orderBy('sort_order');
    }
    public function productAttributes()
{
    return $this->hasMany(ProductAttribute::class);
}

public function products()
{
    return $this->belongsToMany(Product::class, 'product_attributes')
        ->withPivot('sort_order')
        ->withTimestamps();
}
}