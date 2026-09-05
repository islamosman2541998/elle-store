<?php

namespace App\Filament\Resources\TrackingEventLogResource\Pages;

use App\Filament\Resources\TrackingEventLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTrackingEventLogs extends ListRecords
{
    protected static string $resource = TrackingEventLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
