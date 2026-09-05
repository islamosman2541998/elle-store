<?php

namespace App\Filament\Resources\NavigationLinkResource\Pages;

use App\Filament\Resources\NavigationLinkResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditNavigationLink extends EditRecord
{
    protected static string $resource = NavigationLinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
