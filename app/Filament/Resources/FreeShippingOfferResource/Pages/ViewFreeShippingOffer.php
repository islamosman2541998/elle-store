<?php

namespace App\Filament\Resources\FreeShippingOfferResource\Pages;

use App\Filament\Resources\FreeShippingOfferResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewFreeShippingOffer extends ViewRecord
{
    protected static string $resource = FreeShippingOfferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label(__('admin.edit')),
        ];
    }
}