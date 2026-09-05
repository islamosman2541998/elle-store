<?php

namespace App\Filament\Resources\AttributeResource\Pages;

use App\Filament\Resources\AttributeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAttribute extends CreateRecord
{
    protected static string $resource = AttributeResource::class;

    protected array $translationsData = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->translationsData = [
            'ar' => [
                'name' => $data['ar_name'],
            ],
            'en' => [
                'name' => $data['en_name'],
            ],
        ];

        unset(
            $data['ar_name'],
            $data['en_name'],
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