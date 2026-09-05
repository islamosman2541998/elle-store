<?php

namespace App\Filament\Resources\NavigationLinkResource\Pages;

use App\Filament\Resources\NavigationLinkResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewNavigationLink extends ViewRecord
{
    protected static string $resource = NavigationLinkResource::class;

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