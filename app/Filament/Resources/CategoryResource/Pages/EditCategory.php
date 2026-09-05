<?php

namespace App\Filament\Resources\CategoryResource\Pages;

use App\Filament\Resources\CategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCategory extends EditRecord
{
    protected static string $resource = CategoryResource::class;

    protected array $translationsData = [];

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->record->load([
            'arabicTranslation',
            'englishTranslation',
        ]);

        $arabicTranslation = $this->record->arabicTranslation;
        $englishTranslation = $this->record->englishTranslation;

        $data['ar_name'] = $arabicTranslation?->name;
        $data['ar_slug'] = $arabicTranslation?->slug;
        $data['ar_description'] = $arabicTranslation?->description;
        $data['ar_meta_title'] = $arabicTranslation?->meta_title;
        $data['ar_meta_description'] = $arabicTranslation?->meta_description;
        $data['ar_meta_keywords'] = $arabicTranslation?->meta_keywords;

        $data['en_name'] = $englishTranslation?->name;
        $data['en_slug'] = $englishTranslation?->slug;
        $data['en_description'] = $englishTranslation?->description;
        $data['en_meta_title'] = $englishTranslation?->meta_title;
        $data['en_meta_description'] = $englishTranslation?->meta_description;
        $data['en_meta_keywords'] = $englishTranslation?->meta_keywords;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
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

    protected function afterSave(): void
    {
        foreach ($this->translationsData as $locale => $translationData) {
            $this->record->translations()->updateOrCreate(
                [
                    'locale' => $locale,
                ],
                $translationData
            );
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\RestoreAction::make(),
            Actions\ForceDeleteAction::make(),
        ];
    }
}