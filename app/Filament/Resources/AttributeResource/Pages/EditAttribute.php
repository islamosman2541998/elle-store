<?php

namespace App\Filament\Resources\AttributeResource\Pages;

use App\Filament\Resources\AttributeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAttribute extends EditRecord
{
    protected static string $resource = AttributeResource::class;

    protected array $translationsData = [];

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->record->load([
            'arabicTranslation',
            'englishTranslation',
        ]);

        $data['ar_name'] = $this->record->arabicTranslation?->name;
        $data['en_name'] = $this->record->englishTranslation?->name;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
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