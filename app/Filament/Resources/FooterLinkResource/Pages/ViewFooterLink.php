<?php

namespace App\Filament\Resources\FooterLinkResource\Pages;

use App\Filament\Resources\FooterLinkResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewFooterLink extends ViewRecord
{
    protected static string $resource = FooterLinkResource::class;

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