<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FreeShippingOfferResource\Pages;
use App\Models\FreeShippingOffer;
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

class FreeShippingOfferResource extends Resource
{
    protected static ?string $model = FreeShippingOffer::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.promotions');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.free_shipping_offers');
    }

    public static function getModelLabel(): string
    {
        return __('admin.free_shipping_offer');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.free_shipping_offers');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Free Shipping Offer Tabs')
                    ->columnSpanFull()
                    ->tabs([
                        Forms\Components\Tabs\Tab::make(__('admin.free_shipping_offer_information'))
                            ->schema([
                                Forms\Components\Section::make(__('admin.basic_information'))
                                    ->schema([
                                        Forms\Components\TextInput::make('name_ar')
                                            ->label(__('admin.name_ar'))
                                            ->required()
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('name_en')
                                            ->label(__('admin.name_en'))
                                            ->required()
                                            ->maxLength(255),

                                        Forms\Components\Toggle::make('is_active')
                                            ->label(__('admin.active'))
                                            ->default(true),

                                        Forms\Components\TextInput::make('priority')
                                            ->label(__('admin.priority'))
                                            ->numeric()
                                            ->default(0)
                                            ->helperText(__('admin.free_shipping_priority_helper')),

                                        Forms\Components\Textarea::make('notes')
                                            ->label(__('admin.notes'))
                                            ->rows(3)
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make(__('admin.free_shipping_conditions'))
                            ->schema([
                                Forms\Components\Section::make(__('admin.free_shipping_conditions'))
                                    ->schema([
                                        Forms\Components\TextInput::make('minimum_order_amount')
                                            ->label(__('admin.minimum_order_amount'))
                                            ->numeric()
                                            ->nullable()
                                            ->prefix('EGP')
                                            ->helperText(__('admin.free_shipping_minimum_helper')),

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
                                            ->live()
                                            ->nullable()
                                            ->helperText(__('admin.free_shipping_zone_helper'))
                                            ->afterStateUpdated(function (Forms\Set $set) {
                                                $set('shipping_city_id', null);
                                            }),

                                        Forms\Components\Select::make('shipping_city_id')
                                            ->label(__('admin.shipping_city'))
                                            ->options(function (Forms\Get $get) {
                                                $zoneId = $get('shipping_zone_id');

                                                $query = ShippingCity::query()
                                                    ->where('is_active', true)
                                                    ->orderBy('sort_order');

                                                if ($zoneId) {
                                                    $query->where('shipping_zone_id', $zoneId);
                                                }

                                                return $query
                                                    ->get()
                                                    ->mapWithKeys(fn ($city) => [
                                                        $city->id => app()->getLocale() === 'ar'
                                                            ? $city->name_ar
                                                            : $city->name_en,
                                                    ])
                                                    ->toArray();
                                            })
                                            ->searchable()
                                            ->preload()
                                            ->nullable()
                                            ->helperText(__('admin.free_shipping_city_helper')),
                                    ])
                                    ->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make(__('admin.validity_period'))
                            ->schema([
                                Forms\Components\Section::make(__('admin.validity_period'))
                                    ->schema([
                                        Forms\Components\DateTimePicker::make('starts_at')
                                            ->label(__('admin.starts_at'))
                                            ->seconds(false),

                                        Forms\Components\DateTimePicker::make('ends_at')
                                            ->label(__('admin.ends_at'))
                                            ->seconds(false),
                                    ])
                                    ->columns(2),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['city', 'zone']))
            ->columns([
                Tables\Columns\TextColumn::make('name_ar')
                    ->label(__('admin.name_ar'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name_en')
                    ->label(__('admin.name_en'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('minimum_order_amount')
                    ->label(__('admin.minimum_order_amount'))
                    ->formatStateUsing(fn ($state) => $state ? number_format((float) $state, 2, '.', ',') . ' EGP' : __('admin.no_minimum'))
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('zone.name_ar')
                    ->label(__('admin.shipping_zone'))
                    ->placeholder(__('admin.all_zones')),

                Tables\Columns\TextColumn::make('city.name_ar')
                    ->label(__('admin.shipping_city'))
                    ->placeholder(__('admin.all_cities')),

                Tables\Columns\TextColumn::make('priority')
                    ->label(__('admin.priority'))
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('starts_at')
                    ->label(__('admin.starts_at'))
                    ->dateTime()
                    ->placeholder('-')
                    ->sortable(),

                Tables\Columns\TextColumn::make('ends_at')
                    ->label(__('admin.ends_at'))
                    ->dateTime()
                    ->placeholder('-')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status_display')
                    ->label(__('admin.status'))
                    ->state(function ($record) {
                        if (! $record->is_active) {
                            return __('admin.inactive');
                        }

                        if ($record->starts_at && now()->lt($record->starts_at)) {
                            return __('admin.scheduled');
                        }

                        if ($record->ends_at && now()->gt($record->ends_at)) {
                            return __('admin.ended');
                        }

                        return __('admin.running');
                    })
                    ->badge()
                    ->color(function ($record) {
                        if (! $record->is_active) {
                            return 'gray';
                        }

                        if ($record->starts_at && now()->lt($record->starts_at)) {
                            return 'info';
                        }

                        if ($record->ends_at && now()->gt($record->ends_at)) {
                            return 'danger';
                        }

                        return 'success';
                    }),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('admin.active'))
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('admin.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('admin.active')),

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

                Tables\Filters\SelectFilter::make('shipping_city_id')
                    ->label(__('admin.shipping_city'))
                    ->options(function () {
                        return ShippingCity::query()
                            ->orderBy('sort_order')
                            ->get()
                            ->mapWithKeys(fn ($city) => [
                                $city->id => app()->getLocale() === 'ar'
                                    ? $city->name_ar
                                    : $city->name_en,
                            ])
                            ->toArray();
                    }),

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
            ->defaultSort('priority', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make(__('admin.free_shipping_offer_overview'))
                    ->schema([
                        Infolists\Components\TextEntry::make('name_ar')
                            ->label(__('admin.name_ar')),

                        Infolists\Components\TextEntry::make('name_en')
                            ->label(__('admin.name_en')),

                        Infolists\Components\IconEntry::make('is_active')
                            ->label(__('admin.active'))
                            ->boolean(),

                        Infolists\Components\TextEntry::make('priority')
                            ->label(__('admin.priority')),

                        Infolists\Components\TextEntry::make('minimum_order_amount')
                            ->label(__('admin.minimum_order_amount'))
                            ->formatStateUsing(fn ($state) => $state ? number_format((float) $state, 2, '.', ',') . ' EGP' : __('admin.no_minimum')),

                        Infolists\Components\TextEntry::make('zone.name_ar')
                            ->label(__('admin.shipping_zone'))
                            ->placeholder(__('admin.all_zones')),

                        Infolists\Components\TextEntry::make('city.name_ar')
                            ->label(__('admin.shipping_city'))
                            ->placeholder(__('admin.all_cities')),

                        Infolists\Components\TextEntry::make('notes')
                            ->label(__('admin.notes'))
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make(__('admin.validity_period'))
                    ->schema([
                        Infolists\Components\TextEntry::make('starts_at')
                            ->label(__('admin.starts_at'))
                            ->dateTime()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('ends_at')
                            ->label(__('admin.ends_at'))
                            ->dateTime()
                            ->placeholder('-'),
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
            'index' => Pages\ListFreeShippingOffers::route('/'),
            'create' => Pages\CreateFreeShippingOffer::route('/create'),
            'view' => Pages\ViewFreeShippingOffer::route('/{record}'),
            'edit' => Pages\EditFreeShippingOffer::route('/{record}/edit'),
        ];
    }
}