<?php

namespace App\Filament\Resources\HomepageSliderResource\Pages;

use App\Filament\Resources\HomepageSliderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHomepageSlider extends EditRecord
{
    protected static string $resource = HomepageSliderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
