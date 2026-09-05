<?php

namespace App\Filament\Resources\PaymentMethodDisplayResource\Pages;

use App\Filament\Resources\PaymentMethodDisplayResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPaymentMethodDisplay extends ViewRecord
{
    protected static string $resource = PaymentMethodDisplayResource::class;

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