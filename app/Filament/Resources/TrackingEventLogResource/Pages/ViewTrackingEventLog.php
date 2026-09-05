<?php

namespace App\Filament\Resources\TrackingEventLogResource\Pages;

use App\Filament\Resources\TrackingEventLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewTrackingEventLog extends ViewRecord
{
    protected static string $resource = TrackingEventLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label(__('admin.delete')),
        ];
    }
}