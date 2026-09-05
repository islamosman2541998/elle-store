<?php

namespace App\Filament\Resources\FreeShippingOfferResource\Pages;

use App\Filament\Resources\FreeShippingOfferResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFreeShippingOffer extends EditRecord
{
    protected static string $resource = FreeShippingOfferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
