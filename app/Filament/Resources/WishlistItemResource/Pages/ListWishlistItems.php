<?php

namespace App\Filament\Resources\WishlistItemResource\Pages;

use App\Filament\Resources\WishlistItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWishlistItems extends ListRecords
{
    protected static string $resource = WishlistItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
