<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class NavigationLink extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'location',
        'link_type',
        'route_name',
        'page_id',
        'category_id',
        'brand_id',
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

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function getTitleAttribute(): string
    {
        return app()->getLocale() === 'ar'
            ? $this->title_ar
            : $this->title_en;
    }

    public function getResolvedUrlAttribute(): string
    {
        if ($this->link_type === 'custom') {
            return $this->url ?: '#';
        }

        if ($this->link_type === 'route' && $this->route_name) {
            return Route::has($this->route_name)
                ? route($this->route_name)
                : '#';
        }

        if ($this->link_type === 'page' && $this->page) {
            $slug = app()->getLocale() === 'ar'
                ? $this->page->slug_ar
                : $this->page->slug_en;

            return Route::has('site.pages.show')
                ? route('site.pages.show', $slug)
                : '#';
        }

        if ($this->link_type === 'category' && $this->category) {
            return Route::has('site.categories.show')
                ? route('site.categories.show', $this->category->id)
                : '#';
        }

        if ($this->link_type === 'brand' && $this->brand) {
            return Route::has('site.brands.show')
                ? route('site.brands.show', $this->brand->id)
                : '#';
        }

        return '#';
    }

    public static function locations(): array
    {
        return [
            'header' => __('admin.navigation_location_header'),
            'mobile' => __('admin.navigation_location_mobile'),
            'footer' => __('admin.navigation_location_footer'),
        ];
    }

    public static function linkTypes(): array
    {
        return [
            'custom' => __('admin.custom_url'),
            'route' => __('admin.named_route'),
            'page' => __('admin.page'),
            'category' => __('admin.category'),
            'brand' => __('admin.brand'),
        ];
    }

  public static function routeOptions(): array
{
    return collect(Route::getRoutes())
        ->filter(function ($route) {
            $name = $route->getName();

            if (! $name) {
                return false;
            }

            if (! in_array('GET', $route->methods())) {
                return false;
            }

            if (count($route->parameterNames()) > 0) {
                return false;
            }

            $excludedPrefixes = [
                'admin.',
                'filament.',
                'livewire.',
                'ignition.',
                'sanctum.',
                'password.',
                'verification.',
                'login',
                'logout',
                'register',
            ];

            foreach ($excludedPrefixes as $prefix) {
                if (Str::startsWith($name, $prefix)) {
                    return false;
                }
            }

            $uri = $route->uri();

            $excludedUris = [
                'admin',
                'admin/',
                'livewire',
                'livewire/',
                '_ignition',
                '_debugbar',
            ];

            foreach ($excludedUris as $excludedUri) {
                if (Str::startsWith($uri, $excludedUri)) {
                    return false;
                }
            }

            return true;
        })
        ->mapWithKeys(function ($route) {
            $name = $route->getName();
            $uri = $route->uri();

            return [
                $name => $name . '  —  /' . ltrim($uri, '/'),
            ];
        })
        ->sort()
        ->toArray();
}
}