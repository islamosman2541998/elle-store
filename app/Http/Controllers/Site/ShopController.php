<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::query()
            ->where('is_active', true)
            ->with([
                'transNow',
                'arabicTranslation',
                'englishTranslation',
            ])
            ->orderBy('sort_order')
            ->get();

        $brands = Brand::query()
            ->where('is_active', true)
            ->with([
                'transNow',
                'arabicTranslation',
                'englishTranslation',
            ])
            ->orderBy('sort_order')
            ->get();

        $productsQuery = Product::query()
            ->where('is_active', true)
            ->with([
                'transNow',
                'arabicTranslation',
                'englishTranslation',
                'brand.transNow',
                'brand.arabicTranslation',
                'brand.englishTranslation',
                'category.transNow',
                'category.arabicTranslation',
                'category.englishTranslation',
            ]);

        if ($request->filled('q')) {
            $search = trim($request->q);

            $productsQuery->where(function ($query) use ($search) {
                $query->where('sku', 'like', '%' . $search . '%')
                    ->orWhereHas('translations', function ($translationQuery) use ($search) {
                        $translationQuery->where('name', 'like', '%' . $search . '%')
                            ->orWhere('description', 'like', '%' . $search . '%')
                            ->orWhere('short_description', 'like', '%' . $search . '%');
                    });
            });
        }

        if ($request->filled('category')) {
            $categoryValue = $request->category;

            $productsQuery->where(function ($query) use ($categoryValue) {
                $query->where('category_id', $categoryValue)
                    ->orWhereHas('category.translations', function ($translationQuery) use ($categoryValue) {
                        $translationQuery->where('slug', $categoryValue);
                    });
            });
        }

        if ($request->filled('brand')) {
            $brandValue = $request->brand;

            $productsQuery->where(function ($query) use ($brandValue) {
                $query->where('brand_id', $brandValue)
                    ->orWhereHas('brand.translations', function ($translationQuery) use ($brandValue) {
                        $translationQuery->where('slug', $brandValue);
                    });
            });
        }

        if ($request->filled('min_price')) {
            $productsQuery->whereRaw(
                'COALESCE(NULLIF(sale_price, 0), price) >= ?',
                [(float) $request->min_price]
            );
        }

        if ($request->filled('max_price')) {
            $productsQuery->whereRaw(
                'COALESCE(NULLIF(sale_price, 0), price) <= ?',
                [(float) $request->max_price]
            );
        }

        if ($request->boolean('sale')) {
            $productsQuery->whereNotNull('sale_price')
                ->whereColumn('sale_price', '<', 'price');
        }

        match ($request->get('sort')) {
            'price_asc' => $productsQuery->orderByRaw('COALESCE(NULLIF(sale_price, 0), price) ASC'),
            'price_desc' => $productsQuery->orderByRaw('COALESCE(NULLIF(sale_price, 0), price) DESC'),
            'featured' => $productsQuery->orderByDesc('is_featured')->latest(),
            default => $productsQuery->latest(),
        };

        $products = $productsQuery
            ->paginate(12)
            ->withQueryString();

        return view('site.pages.shop.index', [
            'products' => $products,
            'categories' => $categories,
            'brands' => $brands,
        ]);
    }
}