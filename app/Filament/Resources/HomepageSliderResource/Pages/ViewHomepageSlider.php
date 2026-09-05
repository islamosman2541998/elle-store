<?php

namespace App\Filament\Resources\HomepageSliderResource\Pages;

use App\Filament\Resources\HomepageSliderResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewHomepageSlider extends ViewRecord
{
    protected static string $resource = HomepageSliderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label(__('admin.edit')),

            Actions\DeleteAction::make()
                ->label(__('admin.delete')),
        ];
    }
}