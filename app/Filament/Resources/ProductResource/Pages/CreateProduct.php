<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected array $translationsData = [];

    protected array $galleryImages = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->translationsData = [
            'ar' => [
                'name' => $data['ar_name'],
                'slug' => $data['ar_slug'],
                'short_description' => $data['ar_short_description'] ?? null,
                'description' => $data['ar_description'] ?? null,
                'meta_title' => $data['ar_meta_title'] ?? null,
                'meta_description' => $data['ar_meta_description'] ?? null,
                'meta_keywords' => $data['ar_meta_keywords'] ?? null,
            ],
            'en' => [
                'name' => $data['en_name'],
                'slug' => $data['en_slug'],
                'short_description' => $data['en_short_description'] ?? null,
                'description' => $data['en_description'] ?? null,
                'meta_title' => $data['en_meta_title'] ?? null,
                'meta_description' => $data['en_meta_description'] ?? null,
                'meta_keywords' => $data['en_meta_keywords'] ?? null,
            ],
        ];

        $this->galleryImages = $data['gallery_images'] ?? [];

        unset(
            $data['ar_name'],
            $data['ar_slug'],
            $data['ar_short_description'],
            $data['ar_description'],
            $data['ar_meta_title'],
            $data['ar_meta_description'],
            $data['ar_meta_keywords'],

            $data['en_name'],
            $data['en_slug'],
            $data['en_short_description'],
            $data['en_description'],
            $data['en_meta_title'],
            $data['en_meta_description'],
            $data['en_meta_keywords'],

            $data['gallery_images'],
        );

        return $data;
    }

    protected function afterCreate(): void
    {
        foreach ($this->translationsData as $locale => $translationData) {
            $this->record->translations()->create([
                'locale' => $locale,
                ...$translationData,
            ]);
        }

        foreach (array_values($this->galleryImages) as $index => $image) {
            $this->record->images()->create([
                'image' => $image,
                'is_main' => false,
                'sort_order' => $index + 1,
            ]);
        }
    }
}