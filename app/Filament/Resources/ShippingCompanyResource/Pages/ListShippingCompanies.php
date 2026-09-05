<?php

namespace App\Filament\Resources\ShippingCompanyResource\Pages;

use App\Filament\Resources\ShippingCompanyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListShippingCompanies extends ListRecords
{
    protected static string $resource = ShippingCompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
