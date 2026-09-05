<?php

namespace App\Filament\Resources\NavigationLinkResource\Pages;

use App\Filament\Resources\NavigationLinkResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListNavigationLinks extends ListRecords
{
    protected static string $resource = NavigationLinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
