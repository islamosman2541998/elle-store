<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShippingZoneResource\Pages;
use App\Models\ShippingZone;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use App\Filament\Resources\ShippingZoneResource\RelationManagers\CitiesRelationManager;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ShippingZoneResource extends Resource
{
    protected static ?string $model = ShippingZone::class;

    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.shipping_delivery');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.shipping_zones');
    }

    public static function getModelLabel(): string
    {
        return __('admin.shipping_zone');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.shipping_zones');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('admin.shipping_zone_information'))
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
            ->modifyQueryUsing(fn(Builder $query) => $query->withCount('cities'))
            ->columns([
                Tables\Columns\TextColumn::make('name_ar')
                    ->label(__('admin.name_ar'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name_en')
                    ->label(__('admin.name_en'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('cities_count')
                    ->label(__('admin.cities_count'))
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
                Infolists\Components\Section::make(__('admin.shipping_zone_information'))
                    ->schema([
                        Infolists\Components\TextEntry::make('name_ar')
                            ->label(__('admin.name_ar')),

                        Infolists\Components\TextEntry::make('name_en')
                            ->label(__('admin.name_en')),

                        Infolists\Components\IconEntry::make('is_active')
                            ->label(__('admin.active'))
                            ->boolean(),

                        Infolists\Components\TextEntry::make('sort_order')
                            ->label(__('admin.sort_order')),

                        Infolists\Components\TextEntry::make('created_at')
                            ->label(__('admin.created_at'))
                            ->dateTime(),

                        Infolists\Components\TextEntry::make('updated_at')
                            ->label(__('admin.updated_at'))
                            ->dateTime(),
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

    public static function getRelations(): array
    {
        return [
            CitiesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShippingZones::route('/'),
            'create' => Pages\CreateShippingZone::route('/create'),
            'view' => Pages\ViewShippingZone::route('/{record}'),
            'edit' => Pages\EditShippingZone::route('/{record}/edit'),
        ];
    }
}