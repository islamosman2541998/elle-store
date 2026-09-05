<?php

namespace App\Filament\Resources\ReturnRequestResource\Pages;

use App\Filament\Resources\ReturnRequestResource;
use App\Services\StockService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewReturnRequest extends ViewRecord
{
    protected static string $resource = ReturnRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('change_return_status')
                ->label(__('admin.change_return_status'))
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->form([
                    Forms\Components\Select::make('status')
                        ->label(__('admin.return_status'))
                        ->options([
                            'requested' => __('admin.return_requested'),
                            'approved' => __('admin.return_approved'),
                            'rejected' => __('admin.return_rejected'),
                            'received' => __('admin.return_received'),
                            'refunded' => __('admin.return_refunded'),
                            'cancelled' => __('admin.return_cancelled'),
                        ])
                        ->required()
                        ->default(fn () => $this->record->status),

                    Forms\Components\Textarea::make('admin_notes')
                        ->label(__('admin.admin_notes'))
                        ->rows(3),
                ])
                ->action(function (array $data): void {
                    $oldStatus = $this->record->status;
                    $newStatus = $data['status'];

                    if ($oldStatus === $newStatus) {
                        Notification::make()
                            ->title(__('admin.status_not_changed'))
                            ->warning()
                            ->send();

                        return;
                    }

                    $timestamps = [];

                    if ($newStatus === 'approved') {
                        $timestamps['approved_at'] = now();
                    }

                    if ($newStatus === 'rejected') {
                        $timestamps['rejected_at'] = now();
                    }

                    if ($newStatus === 'received') {
                        $timestamps['received_at'] = now();
                    }

                    if ($newStatus === 'refunded') {
                        $timestamps['refunded_at'] = now();
                    }

                    $this->record->update([
                        'status' => $newStatus,
                        'admin_notes' => $data['admin_notes'] ?? $this->record->admin_notes,
                        ...$timestamps,
                    ]);

                    if (in_array($newStatus, ['received', 'refunded']) && ! $this->record->stock_restored_at) {
                        app(StockService::class)->restoreReturnRequestStock($this->record->fresh());
                    }

                    Notification::make()
                        ->title(__('admin.return_status_updated_successfully'))
                        ->success()
                        ->send();
                }),

            Actions\EditAction::make()
                ->label(__('admin.edit')),
        ];
    }
}