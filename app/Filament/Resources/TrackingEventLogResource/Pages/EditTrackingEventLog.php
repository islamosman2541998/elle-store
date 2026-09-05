<?php

namespace App\Filament\Resources\TrackingEventLogResource\Pages;

use App\Filament\Resources\TrackingEventLogResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTrackingEventLog extends EditRecord
{
    protected static string $resource = TrackingEventLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
