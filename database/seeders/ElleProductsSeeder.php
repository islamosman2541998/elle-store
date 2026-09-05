<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\BrandTranslation;
use App\Models\Category;
use App\Models\CategoryTranslation;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductImage;
use App\Models\ProductTranslation;
use App\Models\ProductVariant;
use App\Models\ProductVariantAttributeValue;
use App\Models\ProductVariantTranslation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ElleProductsSeeder extends Seeder
{
    /** Sizes offered per category. */
    private const SIZES = [
        'prayer-dresses' => ['Free Size'],
        'pajamas' => ['S', 'M', 'L', 'XL'],
    ];

    /** Stock created for every variant. */
    private const STOCK_PER_VARIANT = [
        'prayer-dresses' => 20,
        'pajamas' => 10,
    ];

    public function run(): void
    {
        $brand = $this->brand();
        $categories = $this->categoryIdsBySlug();
        [$sizeAttribute, $colorAttribute] = $this->attributes();
        $sizeValues = $this->valuesByEnglishName($sizeAttribute);
        $colorValues = $this->valuesByEnglishName($colorAttribute);

        $products = require database_path('seeders/data/elle-products.php');

        foreach ($products as $data) {
            $sizes = self::SIZES[$data['cat']];
            $stockPerVariant = self::STOCK_PER_VARIANT[$data['cat']];
            $mainImage = "products/gallery/elle-{$data['code']}-1.jpeg";

            $product = Product::query()->create([
                'category_id' => $categories[$data['cat']],
                'brand_id' => $brand->id,
                'sku' => "ELLE-{$data['code']}",
                'barcode' => null,
                'main_image' => $mainImage,
                'price' => $data['price'],
                'sale_price' => $data['sale'],
                'cost_price' => null,
                'stock_quantity' => $stockPerVariant * count($sizes) * count($data['colors']),
                'low_stock_alert' => 3,
                'manage_stock' => true,
                'allow_backorder' => false,
                'weight' => $data['cat'] === 'pajamas' ? 0.45 : 0.60,
                'is_active' => true,
                'is_featured' => $data['featured'],
                'published_at' => now(),
            ]);

            $this->translate($product, $data);
            $this->gallery($product, $data);

            ProductAttribute::query()->create([
                'product_id' => $product->id,
                'attribute_id' => $sizeAttribute->id,
                'sort_order' => 1,
            ]);

            ProductAttribute::query()->create([
                'product_id' => $product->id,
                'attribute_id' => $colorAttribute->id,
                'sort_order' => 2,
            ]);

            $sortOrder = 0;

            foreach ($data['colors'] as $color) {
                foreach ($sizes as $size) {
                    $sortOrder++;

                    $variant = ProductVariant::query()->create([
                        'product_id' => $product->id,
                        'sku' => $this->variantSku($data['code'], $color, $size),
                        'price' => null,
                        'sale_price' => null,
                        'stock_quantity' => $stockPerVariant,
                        'low_stock_alert' => 2,
                        'image' => null,
                        'is_active' => true,
                        'sort_order' => $sortOrder,
                    ]);

                    ProductVariantTranslation::query()->create([
                        'product_variant_id' => $variant->id,
                        'locale' => 'ar',
                        'name' => $colorValues[$color]['ar'] . ' - ' . $sizeValues[$size]['ar'],
                    ]);

                    ProductVariantTranslation::query()->create([
                        'product_variant_id' => $variant->id,
                        'locale' => 'en',
                        'name' => $color . ' - ' . $size,
                    ]);

                    ProductVariantAttributeValue::query()->create([
                        'product_variant_id' => $variant->id,
                        'attribute_id' => $sizeAttribute->id,
                        'attribute_value_id' => $sizeValues[$size]['id'],
                    ]);

                    ProductVariantAttributeValue::query()->create([
                        'product_variant_id' => $variant->id,
                        'attribute_id' => $colorAttribute->id,
                        'attribute_value_id' => $colorValues[$color]['id'],
                    ]);
                }
            }

            $this->command?->getOutput()->write('.');
        }

        $this->command?->getOutput()->writeln('');
    }

    private function brand(): Brand
    {
        $brand = Brand::query()->create([
            'logo' => 'brands/elle-logo.png',
            'is_active' => true,
            'is_featured' => true,
            'sort_order' => 1,
        ]);

        BrandTranslation::query()->create([
            'brand_id' => $brand->id,
            'locale' => 'ar',
            'name' => 'ELLE',
            'slug' => 'elle',
            'description' => 'ELLE للأزياء النسائية — اسدالات وبيجامات وطرح ولانجري بخامات ناعمة وتصميمات أنيقة.',
            'meta_title' => 'ELLE | أزياء نسائية',
            'meta_description' => 'تسوقي تشكيلة ELLE من الاسدالات والبيجامات والطرح واللانجري.',
        ]);

        BrandTranslation::query()->create([
            'brand_id' => $brand->id,
            'locale' => 'en',
            'name' => 'ELLE',
            'slug' => 'elle',
            'description' => "ELLE Women's Fashion — prayer dresses, pajamas, scarves and lingerie in soft fabrics and elegant designs.",
            'meta_title' => "ELLE | Women's Fashion",
            'meta_description' => 'Shop the ELLE collection of prayer dresses, pajamas, scarves and lingerie.',
        ]);

        return $brand;
    }

    /** @return array<string, int> */
    private function categoryIdsBySlug(): array
    {
        return CategoryTranslation::query()
            ->where('locale', 'en')
            ->pluck('category_id', 'slug')
            ->all();
    }

    /** @return array{0: Attribute, 1: Attribute} */
    private function attributes(): array
    {
        $attributes = Attribute::query()
            ->with('translations')
            ->get()
            ->keyBy(fn (Attribute $attribute) => $attribute->translations
                ->firstWhere('locale', 'en')?->name);

        return [$attributes['Size'], $attributes['Color']];
    }

    /**
     * Index an attribute's values by their English label, keeping the Arabic one.
     *
     * @return array<string, array{id: int, ar: string}>
     */
    private function valuesByEnglishName(Attribute $attribute): array
    {
        $values = [];

        foreach (AttributeValue::query()->with('translations')->where('attribute_id', $attribute->id)->get() as $value) {
            $en = $value->translations->firstWhere('locale', 'en')?->value;
            $ar = $value->translations->firstWhere('locale', 'ar')?->value;

            if ($en !== null) {
                $values[$en] = ['id' => $value->id, 'ar' => $ar ?? $en];
            }
        }

        return $values;
    }

    private function translate(Product $product, array $data): void
    {
        $slug = Str::slug($data['en']) . '-' . $data['code'];

        ProductTranslation::query()->create([
            'product_id' => $product->id,
            'locale' => 'ar',
            'name' => $data['ar'] . ' - كود ' . $data['code'],
            'slug' => $slug,
            'short_description' => $data['ar_short'],
            'description' => $this->arabicDescription($data),
            'meta_title' => $data['ar'] . ' | ELLE',
            'meta_description' => $data['ar_short'],
            'meta_keywords' => 'ELLE, ' . ($data['cat'] === 'pajamas' ? 'بيجامات, بيجامة ساتان' : 'اسدالات, اسدال صلاة') . ', كود ' . $data['code'],
        ]);

        ProductTranslation::query()->create([
            'product_id' => $product->id,
            'locale' => 'en',
            'name' => $data['en'] . ' - Code ' . $data['code'],
            'slug' => $slug,
            'short_description' => $data['en_short'],
            'description' => $this->englishDescription($data),
            'meta_title' => $data['en'] . ' | ELLE',
            'meta_description' => $data['en_short'],
            'meta_keywords' => 'ELLE, ' . ($data['cat'] === 'pajamas' ? 'pajamas, satin pajama set' : 'prayer dress, isdal') . ', code ' . $data['code'],
        ]);
    }

    private function arabicDescription(array $data): string
    {
        $lines = ['<p>' . $data['ar_short'] . '</p>', '<ul>'];

        if ($data['cat'] === 'pajamas') {
            $lines[] = '<li>طقم من قطعتين: قميص بأزرار وبنطلون طويل.</li>';
            $lines[] = '<li>خامة ساتان حريري ناعمة وخفيفة على البشرة.</li>';
            $lines[] = '<li>ياقة كلاسيكية وحواف ملونة، وبنطلون بأستك مريح.</li>';
            $lines[] = '<li>المقاسات المتاحة: S — M — L — XL.</li>';
        } else {
            $lines[] = '<li>اسدال صلاة قطعة واحدة بغطاء رأس متصل.</li>';
            $lines[] = '<li>خامة ساتان ناعمة لا تنفذ منها الرؤية.</li>';
            $lines[] = '<li>أكمام واسعة بأستك عند المعصم وكشكشة أمامية.</li>';
            $lines[] = '<li>مقاس واحد يناسب من المقاس M حتى XXL.</li>';
        }

        $lines[] = '<li>كود المنتج: ' . $data['code'] . '</li>';
        $lines[] = '</ul>';

        return implode('', $lines);
    }

    private function englishDescription(array $data): string
    {
        $lines = ['<p>' . $data['en_short'] . '</p>', '<ul>'];

        if ($data['cat'] === 'pajamas') {
            $lines[] = '<li>Two-piece set: button-through shirt and full-length trousers.</li>';
            $lines[] = '<li>Soft, lightweight satin silk that is gentle on the skin.</li>';
            $lines[] = '<li>Classic notch collar with contrast piping and an elasticated waist.</li>';
            $lines[] = '<li>Available sizes: S — M — L — XL.</li>';
        } else {
            $lines[] = '<li>One-piece prayer dress with an attached head covering.</li>';
            $lines[] = '<li>Soft, fully opaque satin fabric.</li>';
            $lines[] = '<li>Wide sleeves with elasticated cuffs and a gathered front yoke.</li>';
            $lines[] = '<li>Free size, fitting M through XXL.</li>';
        }

        $lines[] = '<li>Product code: ' . $data['code'] . '</li>';
        $lines[] = '</ul>';

        return implode('', $lines);
    }

    private function gallery(Product $product, array $data): void
    {
        for ($i = 1; $i <= $data['images']; $i++) {
            ProductImage::query()->create([
                'product_id' => $product->id,
                'image' => "products/gallery/elle-{$data['code']}-{$i}.jpeg",
                'is_main' => $i === 1,
                'sort_order' => $i,
            ]);
        }
    }

    private function variantSku(string $code, string $color, string $size): string
    {
        return strtoupper('ELLE-' . $code . '-' . Str::slug($color) . '-' . Str::slug($size));
    }
}
