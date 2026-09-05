<?php

namespace App\Filament\Resources\BlogPostResource\Pages;

use App\Filament\Resources\BlogPostResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewBlogPost extends ViewRecord
{
    protected static string $resource = BlogPostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('publish')
                ->label(__('admin.publish'))
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $this->record->status !== 'published')
                ->requiresConfirmation()
                ->action(fn () => $this->record->publish()),

            Actions\Action::make('archive')
                ->label(__('admin.archive'))
                ->icon('heroicon-o-archive-box')
                ->color('warning')
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