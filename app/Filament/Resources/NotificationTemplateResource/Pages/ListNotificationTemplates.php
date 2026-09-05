<?php

namespace App\Filament\Resources\NotificationTemplateResource\Pages;

use App\Filament\Resources\NotificationTemplateResource;
use App\Models\NotificationTemplate;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListNotificationTemplates extends ListRecords
{
    protected static string $resource = NotificationTemplateResource::class;

    public function mount(): void
    {
        parent::mount();

        // A new event added in a release has no row yet; create it silently so
        // the list is never missing a message the store can send.
        NotificationTemplate::syncMissing();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('reset_all')
                ->label(__('admin.reset_all_templates'))
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription(__('admin.reset_all_templates_confirm'))
                ->action(function (): void {
                    NotificationTemplate::query()->get()->each->resetToDefault();

                    Notification::make()
                        ->title(__('admin.templates_reset'))
                        ->success()
                        ->send();
                }),
        ];
    }
}
