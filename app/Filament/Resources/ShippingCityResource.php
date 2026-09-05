<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShippingCityResource\Pages;
use App\Models\ShippingCity;
use App\Models\ShippingZone;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ShippingCityResource extends Resource
{
    protected static ?string $model = ShippingCity::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.shipping_delivery');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.shipping_cities');
    }

    public static function getModelLabel(): string
    {
        return __('admin.shipping_city');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.shipping_cities');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('admin.shipping_city_information'))
                    ->schema([
                        Forms\Components\Select::make('shipping_zone_id')
                            ->label(__('admin.shipping_zone'))
                            ->options(function () {
                                return ShippingZone::query()
                                    ->where('is_active', true)
                                    ->orderBy('sort_order')
                                    ->get()
                                    ->mapWithKeys(fn ($zone) => [
                                        $zone->id => app()->getLocale() === 'ar'
                                            ? $zone->name_ar
                                            : $zone->name_en,
                                    ])
                                    ->toArray();
                            })
                            ->searchable()
                            ->preload()
                            ->nullable(),

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

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('zone'))
            ->columns([
                Tables\Columns\TextColumn::make('name_ar')
                    ->label(__('admin.name_ar'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name_en')
                    ->label(__('admin.name_en'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('zone.name_ar')
                    ->label(__('admin.shipping_zone'))
                    ->placeholder('-')
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

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('admin.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('shipping_zone_id')
                    ->label(__('admin.shipping_zone'))
                    ->options(function () {
                        return ShippingZone::query()
                            ->orderBy('sort_order')
                            ->get()
                            ->mapWithKeys(fn ($zone) => [
                                $zone->id => app()->getLocale() === 'ar'
                                    ? $zone->name_ar
                                    : $zone->name_en,
                            ])
                            ->toArray();
                    }),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('admin.active')),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort_order');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make(__('admin.shipping_city_information'))
                    ->schema([
                        Infolists\Components\TextEntry::make('name_ar')
                            ->label(__('admin.name_ar')),

                        Infolists\Components\TextEntry::make('name_en')
                            ->label(__('admin.name_en')),

                        Infolists\Components\TextEntry::make('zone.name_ar')
                            ->label(__('admin.shipping_zone'))
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('delivery_fee')
                            ->label(__('admin.delivery_fee'))
                            ->formatStateUsing(fn ($state) => number_format((float) $state, 2, '.', ',') . ' EGP'),

                        Infolists\Components\TextEntry::make('free_shipping_min_order')
                            ->label(__('admin.free_shipping_min_order'))
                            ->formatStateUsing(fn ($state) => $state ? number_format((float) $state, 2, '.', ',') . ' EGP' : '-'),

                        Infolists\Components\TextEntry::make('estimated_delivery_days')
                            ->label(__('admin.estimated_delivery_days'))
                            ->formatStateUsing(fn ($state) => $state ? number_format((int) $state) . ' ' . __('admin.days') : '-'),

                        Infolists\Components\IconEntry::make('is_active')
                            ->label(__('admin.active'))
                            ->boolean(),

                        Infolists\Components\TextEntry::make('sort_order')
                            ->label(__('admin.sort_order')),
                    ])
                    ->columns(2),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShippingCities::route('/'),
            'create' => Pages\CreateShippingCity::route('/create'),
            'view' => Pages\ViewShippingCity::route('/{record}'),
            'edit' => Pages\EditShippingCity::route('/{record}/edit'),
        ];
    }
}