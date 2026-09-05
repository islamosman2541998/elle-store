<?php

namespace App\Filament\Resources\ProductDiscountResource\Pages;

use App\Filament\Resources\ProductDiscountResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProductDiscounts extends ListRecords
{
    protected static string $resource = ProductDiscountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
