<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class CartsRelationManager extends RelationManager
{
    protected static string $relationship = 'carts';

    public static function getTitle($ownerRecord, string $pageClass): string
    {
        return __('admin.carts');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('status')
                    ->label(__('admin.status'))
                    ->options([
                        'active' => __('admin.active_cart'),
                        'converted' => __('admin.converted_cart'),
                        'abandoned' => __('admin.abandoned_cart'),
                        'expired' => __('admin.expired_cart'),
                    ])
                    ->required()
                    ->default('active'),

                Forms\Components\TextInput::make('session_id')
                    ->label(__('admin.session_id'))
                    ->maxLength(255),

                Forms\Components\TextInput::make('subtotal')
                    ->label(__('admin.subtotal'))
                    ->numeric()
                    ->default(0)
                    ->prefix('EGP'),

                Forms\Components\TextInput::make('discount_total')
                    ->label(__('admin.discount_total'))
                    ->numeric()
                    ->default(0)
                    ->prefix('EGP'),

                Forms\Components\TextInput::make('shipping_total')
                    ->label(__('admin.shipping_total'))
                    ->numeric()
                    ->default(0)
                    ->prefix('EGP'),

                Forms\Components\TextInput::make('tax_total')
                    ->label(__('admin.tax_total'))
                    ->numeric()
                    ->default(0)
                    ->prefix('EGP'),

                Forms\Components\TextInput::make('grand_total')
                    ->label(__('admin.grand_total'))
                    ->numeric()
                    ->default(0)
                    ->prefix('EGP'),

                Forms\Components\DateTimePicker::make('last_activity_at')
                    ->label(__('admin.last_activity_at'))
                    ->seconds(false),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount('items'))
            ->recordTitleAttribute('status')
            ->columns([
                Tables\Columns\TextColumn::make('status')
                    ->label(__('admin.status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => __('admin.' . $state . '_cart'))
                    ->color(fn ($state) => match ($state) {
                        'active' => 'success',
                        'converted' => 'info',
                        'abandoned' => 'warning',
                        'expired' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('items_count')
                    ->label(__('admin.items_count'))
                    ->badge(),

                Tables\Columns\TextColumn::make('subtotal')
                    ->label(__('admin.subtotal'))
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2, '.', ',') . ' EGP'),

                Tables\Columns\TextColumn::make('discount_total')
                    ->label(__('admin.discount_total'))
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2, '.', ',') . ' EGP'),

                Tables\Columns\TextColumn::make('shipping_total')
                    ->label(__('admin.shipping_total'))
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2, '.', ',') . ' EGP'),

                Tables\Columns\TextColumn::make('grand_total')
                    ->label(__('admin.grand_total'))
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2, '.', ',') . ' EGP')
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('last_activity_at')
                    ->label(__('admin.last_activity_at'))
                    ->dateTime()
                    ->placeholder('-')
                    ->sortable(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label(__('admin.add_cart')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('last_activity_at', 'desc');
    }
}