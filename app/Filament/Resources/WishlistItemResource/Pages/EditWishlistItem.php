<?php

namespace App\Filament\Resources\WishlistItemResource\Pages;

use App\Filament\Resources\WishlistItemResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWishlistItem extends EditRecord
{
    protected static string $resource = WishlistItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
