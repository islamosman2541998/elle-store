<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class StatusHistoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'statusHistories';

    public static function getTitle($ownerRecord, string $pageClass): string
    {
        return __('admin.status_timeline');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('from_status')
                    ->label(__('admin.from_status'))
                    ->options([
                        'pending' => __('admin.order_pending'),
                        'confirmed' => __('admin.order_confirmed'),
                        'processing' => __('admin.order_processing'),
                        'shipped' => __('admin.order_shipped'),
                        'delivered' => __('admin.order_delivered'),
                        'cancelled' => __('admin.order_cancelled'),
                        'returned' => __('admin.order_returned'),
                    ])
                    ->nullable(),

                Forms\Components\Select::make('to_status')
                    ->label(__('admin.to_status'))
                    ->options([
                        'pending' => __('admin.order_pending'),
                        'confirmed' => __('admin.order_confirmed'),
                        'processing' => __('admin.order_processing'),
                        'shipped' => __('admin.order_shipped'),
                        'delivered' => __('admin.order_delivered'),
                        'cancelled' => __('admin.order_cancelled'),
                        'returned' => __('admin.order_returned'),
                    ])
                    ->required(),

                Forms\Components\Textarea::make('note')
                    ->label(__('admin.note'))
                    ->rows(3)
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('user'))
            ->recordTitleAttribute('to_status')
            ->columns([
                Tables\Columns\TextColumn::make('from_status')
                    ->label(__('admin.from_status'))
                    ->formatStateUsing(fn ($state) => $state ? __('admin.order_' . $state) : '-')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('to_status')
                    ->label(__('admin.to_status'))
                    ->formatStateUsing(fn ($state) => __('admin.order_' . $state))
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'pending' => 'gray',
                        'confirmed' => 'info',
                        'processing' => 'warning',
                        'shipped' => 'primary',
                        'delivered' => 'success',
                        'cancelled' => 'danger',
                        'returned' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('admin.changed_by'))
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('note')
                    ->label(__('admin.note'))
                    ->limit(40)
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('admin.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->headerActions([])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    protected function canCreate(): bool
    {
        return false;
    }

    protected function canEdit($record): bool
    {
        return false;
    }

    protected function canDelete($record): bool
    {
        return false;
    }
}