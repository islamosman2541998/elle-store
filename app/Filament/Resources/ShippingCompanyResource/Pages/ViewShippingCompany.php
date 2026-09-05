<?php

namespace App\Filament\Resources\ShippingCompanyResource\Pages;

use App\Filament\Resources\ShippingCompanyResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewShippingCompany extends ViewRecord
{
    protected static string $resource = ShippingCompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label(__('admin.edit')),
        ];
    }
}