<?php

namespace App\Filament\Resources\WishlistItemResource\Pages;

use App\Filament\Resources\WishlistItemResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateWishlistItem extends CreateRecord
{
    protected static string $resource = WishlistItemResource::class;
}
