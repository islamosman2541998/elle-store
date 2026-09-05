<?php

namespace App\Filament\Resources\StoreSettingResource\Pages;

use App\Filament\Resources\StoreSettingResource;
use App\Services\MetaCapiService;
use App\Services\TrackingEventService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewStoreSetting extends ViewRecord
{
    protected static string $resource = StoreSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('send_test_meta_capi_event')
                ->label(__('admin.send_test_meta_capi_event'))
                ->icon('heroicon-o-paper-airplane')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading(__('admin.send_test_meta_capi_event'))
                ->modalDescription(__('admin.send_test_meta_capi_event_confirmation'))
                ->action(function (): void {
                    $eventId = app(TrackingEventService::class)->eventId('test_lead');

                    $payload = [
                        'currency' => $this->record->currency_code ?? 'EGP',
                        'value' => 1,
                        'content_name' => 'Test Meta CAPI Event',
                    ];

                    app(MetaCapiService::class)->sendEvent(
                        eventName: 'Lead',
                        payload: $payload,
                        eventId: $eventId
                    );

                    Notification::make()
                        ->title(__('admin.test_meta_capi_event_sent'))
                        ->body(__('admin.check_tracking_event_logs'))
                        ->success()
                        ->send();
                }),

            Actions\EditAction::make()
                ->label(__('admin.edit')),
        ];
    }
}