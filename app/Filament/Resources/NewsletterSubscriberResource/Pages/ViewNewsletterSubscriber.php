<?php

namespace App\Filament\Resources\NewsletterSubscriberResource\Pages;

use App\Filament\Resources\NewsletterSubscriberResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewNewsletterSubscriber extends ViewRecord
{
    protected static string $resource = NewsletterSubscriberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('subscribe')
                ->label(__('admin.subscribe'))
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $this->record->status !== 'subscribed')
                ->requiresConfirmation()
                ->action(fn () => $this->record->subscribe()),

            Actions\Action::make('unsubscribe')
                ->label(__('admin.unsubscribe'))
                ->icon('heroicon-o-x-circle')
                ->color('warning')
                ->visible(fn () => $this->record->status !== 'unsubscribed')
                ->requiresConfirmation()
                ->action(fn () => $this->record->unsubscribe()),

            Actions\EditAction::make()
                ->label(__('admin.edit')),

            Actions\DeleteAction::make()
                ->label(__('admin.delete')),
        ];
    }
}