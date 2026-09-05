<?php

namespace App\Filament\Resources\ContactMessageResource\Pages;

use App\Filament\Resources\ContactMessageResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewContactMessage extends ViewRecord
{
    protected static string $resource = ContactMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('mark_as_read')
                ->label(__('admin.mark_as_read'))
                ->icon('heroicon-o-eye')
                ->color('info')
                ->visible(fn () => $this->record->status !== 'read')
                ->action(fn () => $this->record->markAsRead()),

            Actions\Action::make('mark_as_replied')
                ->label(__('admin.mark_as_replied'))
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $this->record->status !== 'replied')
                ->requiresConfirmation()
                ->action(fn () => $this->record->markAsReplied()),

            Actions\Action::make('archive')
                ->label(__('admin.archive'))
                ->icon('heroicon-o-archive-box')
                ->color('gray')
                ->visible(fn () => $this->record->status !== 'archived')
                ->requiresConfirmation()
                ->action(fn () => $this->record->archive()),

            Actions\EditAction::make()
                ->label(__('admin.edit')),

            Actions\DeleteAction::make()
                ->label(__('admin.delete')),
        ];
    }
}