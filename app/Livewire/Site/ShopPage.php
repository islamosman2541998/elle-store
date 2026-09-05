<?php

namespace App\Livewire\Site;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ShopPage extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $q = '';

    #[Url(as: 'category', except: '')]
    public string $category = '';

    #[Url(as: 'brand', except: '')]
    public string $brand = '';

    #[Url(as: 'min_price', except: '')]
    public string $min_price = '';

    #[Url(as: 'max_price', except: '')]
    public string $max_price = '';

    #[Url(as: 'sale', except: false)]
    public bool $sale = false;
    #[Url(as: 'featured', except: false)]
    public bool $featured = false;

    #[Url(as: 'sort', except: 'newest')]
    public string $sort = 'newest';

    public bool $showMobileFilters = false;

    public int $perPage = 12;
    public function updated($property): void
    {
        if (in_array($property, [
            'q',
            'category',
            'brand',
            'min_price',
            'max_price',
            'sale',
            'featured',
            'sort',
        ])) {
            $this->resetPage();
        }
    }

    public function openMobileFilters(): void
    {
        $this->showMobileFilters = true;
    }

    public function closeMobileFilters(): void
    {
        $this->showMobileFilters = false;
    }

    public function clearFilters(): void
    {
        $this->reset([
            'q',
            'category',
            'brand',
            'min_price',
            'max_price',
            'sale',
            'featured',
        ]);

        $this->sort = 'newest';

        $this->resetPage();
    }

    public function removeFilter(string $filter): void
    {
        if (! property_exists($this, $filter)) {
            return;
        }

        if (in_array($filter, ['sale', 'featured'], true)) {
            $this->{$filter} = false;
        } else {
            $this->{$filter} = '';
        }

        $this->resetPage();
    }

    private function priceBounds(): array
    {
        $bounds = Product::query()
            ->where('is_active', true)
            ->selectRaw('
            FLOOR(MIN(COALESCE(NULLIF(sale_price, 0), price))) as min_price,
            CEIL(MAX(COALESCE(NULLIF(sale_price, 0), price))) as max_price
        ')
            ->first();

        $priceFloor = (int) ($bounds?->min_price ?? 0);
        $priceCeiling = (int) ($bounds?->max_price ?? 1000);

        if ($priceCeiling <= $priceFloor) {
            $priceCeiling = $priceFloor + 1000;
        }

        return [$priceFloor, $priceCeiling];
    }

    public function setMinPrice($value): void
    {
        [$priceFloor, $priceCeiling] = $this->priceBounds();

        $value = (int) $value;

        if ($value < $priceFloor) {
            $value = $priceFloor;
        }

        if ($value > $priceCeiling) {
            $value = $priceCeiling;
        }

        $currentMax = filled($this->max_price)
            ? (int) $this->max_price
            : $priceCeiling;

        if ($value > $currentMax) {
            $value = $currentMax;
        }

        $this->min_price = (string) $value;

        $this->resetPage();
    }

    public function setMaxPrice($value): void
    {
        [$priceFloor, $priceCeiling] = $this->priceBounds();

        $value = (int) $value;

        if ($value < $priceFloor) {
            $value = $priceFloor;
        }

        if ($value > $priceCeiling) {
            $value = $priceCeiling;
        }

        $currentMin = filled($this->min_price)
            ? (int) $this->min_price
            : $priceFloor;

        if ($value < $currentMin) {
            $value = $currentMin;
        }

        $this->max_price = (string) $value;

        $this->resetPage();
    }

    public function clearPriceFilter(): void
    {
        $this->min_price = '';
        $this->max_price = '';

        $this->resetPage();
    }
    public function goToShopPage(int $page): void
    {
        $this->gotoPage($page);

        $this->dispatch('shop-scroll-to-products');
    }

    public function nextShopPage(): void
    {
        $this->nextPage();

        $this->dispatch('shop-scroll-to-products');
    }

    public function previousShopPage(): void
    {
        $this->previousPage();

        $this->dispatch('shop-scroll-to-products');
    }

  private function selectedCategoryModel(): ?Category
{
    if (! filled($this->category)) {
        return null;
    }

    $categoryValue = trim((string) $this->category);

    return Category::query()
        ->where(function ($query) use ($categoryValue) {
            if (is_numeric($categoryValue)) {
                $query->whereKey((int) $categoryValue);
            }

            $query->orWhereHas('translations', function ($translationQuery) use ($categoryValue) {
                $translationQuery->where('slug', $categoryValue);
            });
        })
        ->with([
            'transNow',
            'arabicTranslation',
            'englishTranslation',
        ])
        ->first();
}

private function childTabsForCategory(?Category $selectedCategory)
{
    if (! $selectedCategory) {
        return collect();
    }

    $parentId = $selectedCategory->parent_id ?: $selectedCategory->id;

    return Category::query()
        ->where('is_active', true)
        ->where('parent_id', $parentId)
        ->with([
            'transNow',
            'arabicTranslation',
            'englishTranslation',
        ])
        ->orderBy('sort_order')
        ->get();
}

private function categoryIdsForProducts(?Category $selectedCategory, $subCategories): array
{
    if (! $selectedCategory) {
        return [];
    }

    $subCategories = collect($subCategories);

    if (! $selectedCategory->parent_id && $subCategories->count()) {
        return $subCategories->pluck('id')->values()->toArray();
    }

    return [$selectedCategory->id];
}

    public function selectChildCategory(int $categoryId): void
    {
        $this->category = (string) $categoryId;

        $this->resetPage();

        $this->dispatch('shop-scroll-to-products');
    }
    public function render()
    {
        [$priceFloor, $priceCeiling] = $this->priceBounds();

        $selectedMinPrice = filled($this->min_price)
            ? max($priceFloor, min((int) $this->min_price, $priceCeiling))
            : $priceFloor;

        $selectedMaxPrice = filled($this->max_price)
            ? max($priceFloor, min((int) $this->max_price, $priceCeiling))
            : $priceCeiling;

        if ($selectedMinPrice > $selectedMaxPrice) {
            $selectedMinPrice = $selectedMaxPrice;
        }

        $priceRangeSpan = max(1, $priceCeiling - $priceFloor);

        $priceRangeLeft = (($selectedMinPrice - $priceFloor) / $priceRangeSpan) * 100;
        $priceRangeRight = 100 - ((($selectedMaxPrice - $priceFloor) / $priceRangeSpan) * 100);
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

        if (filled($this->q)) {
            $search = trim($this->q);

            $productsQuery->where(function ($query) use ($search) {
                $query->where('sku', 'like', '%' . $search . '%')
                    ->orWhereHas('translations', function ($translationQuery) use ($search) {
                        $translationQuery->where('name', 'like', '%' . $search . '%')
                            ->orWhere('short_description', 'like', '%' . $search . '%')
                            ->orWhere('description', 'like', '%' . $search . '%');
                    });
            });
        }

$selectedCategory = $this->selectedCategoryModel();

$subCategories = $this->childTabsForCategory($selectedCategory);

$categoryIdsForProducts = $this->categoryIdsForProducts($selectedCategory, $subCategories);

if (! empty($categoryIdsForProducts)) {
    $productsQuery->whereIn('category_id', $categoryIdsForProducts);
}

        if (filled($this->brand)) {
            $brandValue = $this->brand;

            $productsQuery->where(function ($query) use ($brandValue) {
                $query->where('brand_id', $brandValue)
                    ->orWhereHas('brand.translations', function ($translationQuery) use ($brandValue) {
                        $translationQuery->where('slug', $brandValue);
                    });
            });
        }

        if (filled($this->min_price)) {
            $productsQuery->whereRaw(
                'COALESCE(NULLIF(sale_price, 0), price) >= ?',
                [(float) $this->min_price]
            );
        }

        if (filled($this->max_price)) {
            $productsQuery->whereRaw(
                'COALESCE(NULLIF(sale_price, 0), price) <= ?',
                [(float) $this->max_price]
            );
        }

        if ($this->sale) {
            $productsQuery->whereNotNull('sale_price')
                ->whereColumn('sale_price', '<', 'price');
        }
        if ($this->featured) {
            $productsQuery->where('is_featured', true);
        }
        match ($this->sort) {
            'price_asc' => $productsQuery->orderByRaw('COALESCE(NULLIF(sale_price, 0), price) ASC'),
            'price_desc' => $productsQuery->orderByRaw('COALESCE(NULLIF(sale_price, 0), price) DESC'),
            'featured' => $productsQuery->orderByDesc('is_featured')->latest(),
            default => $productsQuery->latest(),
        };

        $products = $productsQuery->paginate($this->perPage);

        return view('livewire.site.shop-page', [
            'products' => $products,
            'categories' => $categories,
            'brands' => $brands,
            'priceFloor' => $priceFloor,
            'priceCeiling' => $priceCeiling,
            'selectedMinPrice' => $selectedMinPrice,
            'selectedMaxPrice' => $selectedMaxPrice,
            'priceRangeLeft' => $priceRangeLeft,
            'priceRangeRight' => $priceRangeRight,
           'subCategories' => $subCategories,
'selectedCategory' => $selectedCategory,
        ]);
    }
}