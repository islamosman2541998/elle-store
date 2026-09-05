<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\CategoryTranslation;
use Illuminate\Database\Seeder;

class ElleCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'ar' => 'اسدالات',
                'en' => 'Prayer Dresses',
                'slug' => 'prayer-dresses',
                'slug_ar' => 'prayer-dresses',
                'image' => 'categories/cat-isdal.jpeg',
                'desc_ar' => 'اسدالات صلاة من الساتان الناعم بتصميمات أنيقة وطبعات مميزة، مقاس واحد يناسب الجميع.',
                'desc_en' => 'Soft satin prayer dresses with elegant cuts and distinctive prints, free size to fit all.',
            ],
            [
                'ar' => 'بيجامات',
                'en' => 'Pajamas',
                'slug' => 'pajamas',
                'slug_ar' => 'pajamas',
                'image' => 'categories/cat-pajamas.jpeg',
                'desc_ar' => 'أطقم بيجامات ساتان حريري فاخر، راحة تامة وأناقة ما تتفوتش.',
                'desc_en' => 'Luxurious satin silk pajama sets — total comfort with elegance you cannot miss.',
            ],
            [
                'ar' => 'طرح',
                'en' => 'Scarves',
                'slug' => 'scarves',
                'slug_ar' => 'scarves',
                'image' => 'categories/cat-scarves.jpeg',
                'desc_ar' => 'تشكيلة طرح ELLE بخامات وألوان متنوعة. قريبًا.',
                'desc_en' => 'The ELLE scarves collection in a variety of fabrics and colours. Coming soon.',
            ],
            [
                'ar' => 'لانجري',
                'en' => 'Lingerie',
                'slug' => 'lingerie',
                'slug_ar' => 'lingerie',
                'image' => 'categories/cat-lingerie.jpeg',
                'desc_ar' => 'تشكيلة لانجري ELLE الناعمة. قريبًا.',
                'desc_en' => 'The delicate ELLE lingerie collection. Coming soon.',
            ],
        ];

        foreach ($categories as $index => $data) {
            $category = Category::query()->create([
                'parent_id' => null,
                'image' => $data['image'],
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => $index + 1,
            ]);

            CategoryTranslation::query()->create([
                'category_id' => $category->id,
                'locale' => 'ar',
                'name' => $data['ar'],
                'slug' => $data['slug_ar'],
                'description' => $data['desc_ar'],
                'meta_title' => $data['ar'] . ' | ELLE',
                'meta_description' => $data['desc_ar'],
            ]);

            CategoryTranslation::query()->create([
                'category_id' => $category->id,
                'locale' => 'en',
                'name' => $data['en'],
                'slug' => $data['slug'],
                'description' => $data['desc_en'],
                'meta_title' => $data['en'] . ' | ELLE',
                'meta_description' => $data['desc_en'],
            ]);
        }
    }
}
