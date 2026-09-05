<?php

namespace App\Filament\Resources\ProductDiscountResource\Pages;

use App\Filament\Resources\ProductDiscountResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProductDiscount extends EditRecord
{
    protected static string $resource = ProductDiscountResource::class;

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
