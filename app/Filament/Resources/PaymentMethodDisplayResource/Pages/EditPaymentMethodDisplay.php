<?php

namespace App\Filament\Resources\PaymentMethodDisplayResource\Pages;

use App\Filament\Resources\PaymentMethodDisplayResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPaymentMethodDisplay extends EditRecord
{
    protected static string $resource = PaymentMethodDisplayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
