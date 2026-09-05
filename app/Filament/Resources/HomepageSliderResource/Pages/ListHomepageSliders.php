<?php

namespace App\Filament\Resources\HomepageSliderResource\Pages;

use App\Filament\Resources\HomepageSliderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHomepageSliders extends ListRecords
{
    protected static string $resource = HomepageSliderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
