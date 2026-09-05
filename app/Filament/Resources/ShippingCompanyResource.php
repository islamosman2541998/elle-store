<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShippingCompanyResource\Pages;
use App\Models\ShippingCompany;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ShippingCompanyResource extends Resource
{
    protected static ?string $model = ShippingCompany::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.shipping_delivery');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.shipping_companies');
    }

    public static function getModelLabel(): string
    {
        return __('admin.shipping_company');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.shipping_companies');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('admin.shipping_company_information'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('admin.name'))
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('code')
                            ->label(__('admin.code'))
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText(__('admin.shipping_company_code_helper')),

                        Forms\Components\TextInput::make('contact_name')
                            ->label(__('admin.contact_name'))
                            ->maxLength(255),

                        Forms\Components\TextInput::make('phone')
                            ->label(__('admin.phone'))
                            ->tel()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label(__('admin.email'))
                            ->email()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('tracking_url_template')
                            ->label(__('admin.tracking_url_template'))
                            ->maxLength(255)
                            // A template without the placeholder silently produces a dead link.
                            ->rule(new \App\Rules\TrackingUrlTemplate())
                            ->placeholder('https://carrier.com/track?number={tracking_number}')
                            ->helperText(__('admin.tracking_url_template_helper'))
                            ->columnSpanFull(),

                        Forms\Components\Section::make(__('admin.carrier_integration'))
                            ->columnSpanFull()
                            ->columns(2)
                            ->schema([
                                Forms\Components\Select::make('integration_driver')
                                    ->label(__('admin.integration_driver'))
                                    ->options(fn () => \App\Services\Carriers\CarrierManager::options())
                                    ->default('manual')
                                    ->required()
                                    ->live(),

                                Forms\Components\Toggle::make('sandbox_mode')
                                    ->label(__('admin.sandbox_mode'))
                                    ->default(true)
                                    ->visible(fn (Forms\Get $get) => \App\Services\Carriers\CarrierManager::isApiDriver($get('integration_driver'))),

                                Forms\Components\TextInput::make('api_key')
                                    ->label(__('admin.api_key'))
                                    ->password()
                                    ->revealable()
                                    ->maxLength(500)
                                    ->visible(fn (Forms\Get $get) => \App\Services\Carriers\CarrierManager::isApiDriver($get('integration_driver'))),

                                Forms\Components\TextInput::make('api_base_url')
                                    ->label(__('admin.api_base_url'))
                                    ->url()
                                    ->maxLength(255)
                                    ->placeholder(fn () => config('carriers.bosta.sandbox_url'))
                                    ->visible(fn (Forms\Get $get) => \App\Services\Carriers\CarrierManager::isApiDriver($get('integration_driver'))),

                                Forms\Components\TextInput::make('pickup_location_id')
                                    ->label(__('admin.pickup_location_id'))
                                    ->maxLength(255)
                                    ->visible(fn (Forms\Get $get) => \App\Services\Carriers\CarrierManager::isApiDriver($get('integration_driver'))),

                                Forms\Components\Toggle::make('auto_create_shipment')
                                    ->label(__('admin.auto_create_shipment'))
                                    ->default(false)
                                    ->visible(fn (Forms\Get $get) => \App\Services\Carriers\CarrierManager::isApiDriver($get('integration_driver'))),

                                Forms\Components\TextInput::make('webhook_secret')
                                    ->label(__('admin.webhook_secret'))
                                    ->password()
                                    ->revealable()
                                    ->maxLength(500)
                                    ->helperText(__('admin.webhook_url_hint'))
                                    ->visible(fn (Forms\Get $get) => \App\Services\Carriers\CarrierManager::isApiDriver($get('integration_driver'))),

                                Forms\Components\Placeholder::make('webhook_url')
                                    ->label(__('admin.webhook_url'))
                                    ->content(fn (?ShippingCompany $record) => $record
                                        ? url('/api/carriers/' . $record->id . '/webhook')
                                        : '-')
                                    ->visible(fn (Forms\Get $get, ?ShippingCompany $record) => $record
                                        && \App\Services\Carriers\CarrierManager::isApiDriver($get('integration_driver'))),
                            ]),

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
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('shipments'))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('admin.name'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('code')
                    ->label(__('admin.code'))
                    ->searchable()
                    ->copyable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('phone')
                    ->label(__('admin.phone'))
                    ->searchable()
                    ->copyable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('email')
                    ->label(__('admin.email'))
                    ->searchable()
                    ->copyable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('shipments_count')
                    ->label(__('admin.shipments_count'))
                    ->badge(),

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
                Infolists\Components\Section::make(__('admin.shipping_company_information'))
                    ->schema([
                        Infolists\Components\TextEntry::make('name')
                            ->label(__('admin.name')),

                        Infolists\Components\TextEntry::make('code')
                            ->label(__('admin.code'))
                            ->copyable()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('contact_name')
                            ->label(__('admin.contact_name'))
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('phone')
                            ->label(__('admin.phone'))
                            ->copyable()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('email')
                            ->label(__('admin.email'))
                            ->copyable()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('tracking_url_template')
                            ->label(__('admin.tracking_url_template'))
                            ->copyable()
                            ->placeholder('-')
                            ->columnSpanFull(),

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
            'index' => Pages\ListShippingCompanies::route('/'),
            'create' => Pages\CreateShippingCompany::route('/create'),
            'view' => Pages\ViewShippingCompany::route('/{record}'),
            'edit' => Pages\EditShippingCompany::route('/{record}/edit'),
        ];
    }
}