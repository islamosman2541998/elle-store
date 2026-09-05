<?php

namespace App\Filament\Resources\FreeShippingOfferResource\Pages;

use App\Filament\Resources\FreeShippingOfferResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFreeShippingOffers extends ListRecords
{
    protected static string $resource = FreeShippingOfferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
