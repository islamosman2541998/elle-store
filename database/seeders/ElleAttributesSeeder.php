<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\AttributeTranslation;
use App\Models\AttributeValue;
use App\Models\AttributeValueTranslation;
use Illuminate\Database\Seeder;

class ElleAttributesSeeder extends Seeder
{
    public function run(): void
    {
        $attributes = [
            [
                'type' => 'select',
                'ar' => 'المقاس',
                'en' => 'Size',
                'values' => [
                    ['ar' => 'مقاس واحد', 'en' => 'Free Size', 'color' => null],
                    ['ar' => 'سمول', 'en' => 'S', 'color' => null],
                    ['ar' => 'ميديم', 'en' => 'M', 'color' => null],
                    ['ar' => 'لارج', 'en' => 'L', 'color' => null],
                    ['ar' => 'إكس لارج', 'en' => 'XL', 'color' => null],
                    ['ar' => 'دبل إكس لارج', 'en' => 'XXL', 'color' => null],
                ],
            ],
            [
                'type' => 'color',
                'ar' => 'اللون',
                'en' => 'Color',
                'values' => [
                    ['ar' => 'أوف وايت', 'en' => 'Off White', 'color' => '#F5F1E8'],
                    ['ar' => 'أبيض', 'en' => 'White', 'color' => '#FFFFFF'],
                    ['ar' => 'بيج', 'en' => 'Beige', 'color' => '#E3D5C5'],
                    ['ar' => 'وردي', 'en' => 'Pink', 'color' => '#D9A6A6'],
                    ['ar' => 'روز', 'en' => 'Dusty Rose', 'color' => '#C48B8B'],
                    ['ar' => 'تراكوتا', 'en' => 'Terracotta', 'color' => '#C1705C'],
                    ['ar' => 'أحمر', 'en' => 'Red', 'color' => '#B22222'],
                    ['ar' => 'موف', 'en' => 'Plum', 'color' => '#6E2E52'],
                    ['ar' => 'كحلي', 'en' => 'Navy', 'color' => '#2C3350'],
                    ['ar' => 'أزرق', 'en' => 'Blue', 'color' => '#6B84A8'],
                    ['ar' => 'لبني', 'en' => 'Baby Blue', 'color' => '#A9C6DE'],
                    ['ar' => 'بترولي', 'en' => 'Teal', 'color' => '#2E5F66'],
                    ['ar' => 'منتي', 'en' => 'Mint', 'color' => '#C3DAD6'],
                    ['ar' => 'زيتي', 'en' => 'Olive', 'color' => '#6B7256'],
                    ['ar' => 'رمادي', 'en' => 'Grey', 'color' => '#7B8794'],
                    ['ar' => 'أسود', 'en' => 'Black', 'color' => '#161616'],
                    ['ar' => 'متعدد الألوان', 'en' => 'Multicolor', 'color' => '#B8A9C9'],
                ],
            ],
        ];

        foreach ($attributes as $index => $data) {
            $attribute = Attribute::query()->create([
                'type' => $data['type'],
                'is_active' => true,
                'sort_order' => $index + 1,
            ]);

            AttributeTranslation::query()->create([
                'attribute_id' => $attribute->id,
                'locale' => 'ar',
                'name' => $data['ar'],
            ]);

            AttributeTranslation::query()->create([
                'attribute_id' => $attribute->id,
                'locale' => 'en',
                'name' => $data['en'],
            ]);

            foreach ($data['values'] as $valueIndex => $value) {
                $attributeValue = AttributeValue::query()->create([
                    'attribute_id' => $attribute->id,
                    'color_code' => $value['color'],
                    'is_active' => true,
                    'sort_order' => $valueIndex + 1,
                ]);

                AttributeValueTranslation::query()->create([
                    'attribute_value_id' => $attributeValue->id,
                    'locale' => 'ar',
                    'value' => $value['ar'],
                ]);

                AttributeValueTranslation::query()->create([
                    'attribute_value_id' => $attributeValue->id,
                    'locale' => 'en',
                    'value' => $value['en'],
                ]);
            }
        }
    }
}
