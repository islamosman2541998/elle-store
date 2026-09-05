<?php

namespace App\Filament\Resources\ProductDiscountResource\Pages;

use App\Filament\Resources\ProductDiscountResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewProductDiscount extends ViewRecord
{
    protected static string $resource = ProductDiscountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label(__('admin.edit')),
        ];
    }
}