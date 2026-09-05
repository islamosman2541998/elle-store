<?php

namespace App\Filament\Resources\ShippingZoneResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class CitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'cities';

    public static function getTitle($ownerRecord, string $pageClass): string
    {
        return __('admin.shipping_cities');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('admin.shipping_city_information'))
                    ->schema([
                        Forms\Components\TextInput::make('name_ar')
                            ->label(__('admin.name_ar'))
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('name_en')
                            ->label(__('admin.name_en'))
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('delivery_fee')
                            ->label(__('admin.delivery_fee'))
                            ->numeric()
                            ->default(0)
                            ->prefix('EGP')
                            ->required(),

                        Forms\Components\TextInput::make('free_shipping_min_order')
                            ->label(__('admin.free_shipping_min_order'))
                            ->numeric()
                            ->prefix('EGP')
                            ->nullable(),

                        Forms\Components\TextInput::make('estimated_delivery_days')
                            ->label(__('admin.estimated_delivery_days'))
                            ->numeric()
                            ->nullable(),

                        Forms\Components\Toggle::make('is_active')
                            ->label(__('admin.active'))
                            ->default(true),

                        Forms\Components\TextInput::make('sort_order')
                            ->label(__('admin.sort_order'))
                            ->numeric()
                            ->default(0),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name_ar')
            ->columns([
                Tables\Columns\TextColumn::make('name_ar')
                    ->label(__('admin.name_ar'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name_en')
                    ->label(__('admin.name_en'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('delivery_fee')
                    ->label(__('admin.delivery_fee'))
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2, '.', ',') . ' EGP')
                    ->sortable(),

                Tables\Columns\TextColumn::make('free_shipping_min_order')
                    ->label(__('admin.free_shipping_min_order'))
                    ->formatStateUsing(fn ($state) => $state ? number_format((float) $state, 2, '.', ',') . ' EGP' : '-')
                    ->sortable(),

                Tables\Columns\TextColumn::make('estimated_delivery_days')
                    ->label(__('admin.estimated_delivery_days'))
                    ->formatStateUsing(fn ($state) => $state ? number_format((int) $state) . ' ' . __('admin.days') : '-')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('admin.active'))
                    ->boolean(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label(__('admin.sort_order'))
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('admin.active')),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label(__('admin.add_city')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('sort_order');
    }
}