<?php

namespace App\Filament\Resources\BrandResource\Pages;

use App\Filament\Resources\BrandResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBrand extends CreateRecord
{
    protected static string $resource = BrandResource::class;

    protected array $translationsData = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->translationsData = [
            'ar' => [
                'name' => $data['ar_name'],
                'slug' => $data['ar_slug'],
                'description' => $data['ar_description'] ?? null,
                'meta_title' => $data['ar_meta_title'] ?? null,
                'meta_description' => $data['ar_meta_description'] ?? null,
                'meta_keywords' => $data['ar_meta_keywords'] ?? null,
            ],
            'en' => [
                'name' => $data['en_name'],
                'slug' => $data['en_slug'],
                'description' => $data['en_description'] ?? null,
                'meta_title' => $data['en_meta_title'] ?? null,
                'meta_description' => $data['en_meta_description'] ?? null,
                'meta_keywords' => $data['en_meta_keywords'] ?? null,
            ],
        ];

        unset(
            $data['ar_name'],
            $data['ar_slug'],
            $data['ar_description'],
            $data['ar_meta_title'],
            $data['ar_meta_description'],
            $data['ar_meta_keywords'],

            $data['en_name'],
            $data['en_slug'],
            $data['en_description'],
            $data['en_meta_title'],
            $data['en_meta_description'],
            $data['en_meta_keywords'],
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
    }
}