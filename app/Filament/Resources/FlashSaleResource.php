<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FlashSaleResource\Pages;
use App\Models\FlashSale;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use App\Filament\Resources\FlashSaleResource\RelationManagers\ItemsRelationManager;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class FlashSaleResource extends Resource
{
    protected static ?string $model = FlashSale::class;

    protected static ?string $navigationIcon = 'heroicon-o-bolt';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.promotions');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.flash_sales');
    }

    public static function getModelLabel(): string
    {
        return __('admin.flash_sale');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.flash_sales');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Flash Sale Tabs')
                    ->columnSpanFull()
                    ->tabs([
                        Forms\Components\Tabs\Tab::make(__('admin.flash_sale_information'))
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

                                        Forms\Components\TextInput::make('sort_order')
                                            ->label(__('admin.sort_order'))
                                            ->numeric()
                                            ->default(0),

                                        Forms\Components\Textarea::make('notes')
                                            ->label(__('admin.notes'))
                                            ->rows(3)
                                            ->columnSpanFull(),
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
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('items'))
            ->columns([
                Tables\Columns\TextColumn::make('name_ar')
                    ->label(__('admin.name_ar'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name_en')
                    ->label(__('admin.name_en'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('items_count')
                    ->label(__('admin.items_count'))
                    ->badge(),

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

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('admin.active'))
                    ->boolean(),

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

                Tables\Columns\TextColumn::make('sort_order')
                    ->label(__('admin.sort_order'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

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
                Infolists\Components\Section::make(__('admin.flash_sale_overview'))
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

                        Infolists\Components\TextEntry::make('starts_at')
                            ->label(__('admin.starts_at'))
                            ->dateTime()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('ends_at')
                            ->label(__('admin.ends_at'))
                            ->dateTime()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('notes')
                            ->label(__('admin.notes'))
                            ->placeholder('-')
                            ->columnSpanFull(),
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
        ItemsRelationManager::class,
    ];
}

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFlashSales::route('/'),
            'create' => Pages\CreateFlashSale::route('/create'),
            'view' => Pages\ViewFlashSale::route('/{record}'),
            'edit' => Pages\EditFlashSale::route('/{record}/edit'),
        ];
    }
}