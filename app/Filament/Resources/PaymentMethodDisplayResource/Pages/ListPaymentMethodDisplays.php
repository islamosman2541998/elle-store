<?php

namespace App\Filament\Resources\PaymentMethodDisplayResource\Pages;

use App\Filament\Resources\PaymentMethodDisplayResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPaymentMethodDisplays extends ListRecords
{
    protected static string $resource = PaymentMethodDisplayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
