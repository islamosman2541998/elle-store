<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockMovementResource\Pages;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StockMovementResource extends Resource
{
    protected static ?string $model = StockMovement::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path-rounded-square';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.inventory_management');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.stock_movements');
    }

    public static function getModelLabel(): string
    {
        return __('admin.stock_movement');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.stock_movements');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('admin.stock_movement_information'))
                    ->schema([
                        Forms\Components\Select::make('product_id')
                            ->label(__('admin.product'))
                            ->options(function () {
                                return Product::query()
                                    ->with('arabicTranslation')
                                    ->orderByDesc('created_at')
                                    ->get()
                                    ->mapWithKeys(fn ($product) => [
                                        $product->id => $product->arabicTranslation?->name ?? 'Product #' . $product->id,
                                    ])
                                    ->toArray();
                            })
                            ->searchable()
                            ->preload()
                            ->live()
                            ->required()
                            ->afterStateUpdated(function (Forms\Set $set) {
                                $set('product_variant_id', null);
                            }),

                        Forms\Components\Select::make('product_variant_id')
                            ->label(__('admin.variant'))
                            ->options(function (Forms\Get $get) {
                                $productId = $get('product_id');

                                if (! $productId) {
                                    return [];
                                }

                                return ProductVariant::query()
                                    ->where('product_id', $productId)
                                    ->with(['arabicTranslation', 'englishTranslation'])
                                    ->get()
                                    ->mapWithKeys(function ($variant) {
                                        $name = $variant->arabicTranslation?->name
                                            ?? $variant->englishTranslation?->name
                                            ?? 'Variant #' . $variant->id;

                                        if ($variant->sku) {
                                            $name .= ' - ' . $variant->sku;
                                        }

                                        return [
                                            $variant->id => $name,
                                        ];
                                    })
                                    ->toArray();
                            })
                            ->searchable()
                            ->preload()
                            ->nullable(),

                        Forms\Components\Select::make('type')
                            ->label(__('admin.movement_type'))
                            ->options([
                                'order_deduction' => __('admin.stock_order_deduction'),
                                'order_cancelled_restore' => __('admin.stock_order_cancelled_restore'),
                                'return' => __('admin.stock_return'),
                                'manual_adjustment' => __('admin.stock_manual_adjustment'),
                                'stock_in' => __('admin.stock_in'),
                                'stock_out' => __('admin.stock_out'),
                            ])
                            ->required(),

                        Forms\Components\TextInput::make('quantity')
                            ->label(__('admin.quantity'))
                            ->numeric()
                            ->required(),

                        Forms\Components\TextInput::make('before_quantity')
                            ->label(__('admin.before_quantity'))
                            ->numeric(),

                        Forms\Components\TextInput::make('after_quantity')
                            ->label(__('admin.after_quantity'))
                            ->numeric(),

                        Forms\Components\TextInput::make('reference')
                            ->label(__('admin.reference'))
                            ->maxLength(255),

                        Forms\Components\Textarea::make('notes')
                            ->label(__('admin.notes'))
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with([
                'product.arabicTranslation',
                'variant.arabicTranslation',
                'order',
                'user',
            ]))
            ->columns([
                Tables\Columns\TextColumn::make('product.arabicTranslation.name')
                    ->label(__('admin.product'))
                    ->searchable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('variant.arabicTranslation.name')
                    ->label(__('admin.variant'))
                    ->placeholder('-')
                    ->wrap(),

                Tables\Columns\TextColumn::make('type')
                    ->label(__('admin.movement_type'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => __('admin.stock_' . $state))
                    ->color(fn ($state) => match ($state) {
                        'order_deduction', 'stock_out' => 'danger',
                        'order_cancelled_restore', 'return', 'stock_in' => 'success',
                        'manual_adjustment' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('quantity')
                    ->label(__('admin.quantity'))
                    ->badge()
                    ->color(fn ($state) => (int) $state < 0 ? 'danger' : 'success')
                    ->formatStateUsing(fn ($state) => number_format((int) $state)),

                Tables\Columns\TextColumn::make('before_quantity')
                    ->label(__('admin.before_quantity'))
                    ->formatStateUsing(fn ($state) => $state === null ? '-' : number_format((int) $state)),

                Tables\Columns\TextColumn::make('after_quantity')
                    ->label(__('admin.after_quantity'))
                    ->formatStateUsing(fn ($state) => $state === null ? '-' : number_format((int) $state)),

                Tables\Columns\TextColumn::make('order.order_number')
                    ->label(__('admin.order_number'))
                    ->copyable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('admin.user'))
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('admin.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label(__('admin.movement_type'))
                    ->options([
                        'order_deduction' => __('admin.stock_order_deduction'),
                        'order_cancelled_restore' => __('admin.stock_order_cancelled_restore'),
                        'return' => __('admin.stock_return'),
                        'manual_adjustment' => __('admin.stock_manual_adjustment'),
                        'stock_in' => __('admin.stock_in'),
                        'stock_out' => __('admin.stock_out'),
                    ]),

                Tables\Filters\SelectFilter::make('product_id')
                    ->label(__('admin.product'))
                    ->options(function () {
                        return Product::query()
                            ->with('arabicTranslation')
                            ->get()
                            ->mapWithKeys(fn ($product) => [
                                $product->id => $product->arabicTranslation?->name ?? 'Product #' . $product->id,
                            ])
                            ->toArray();
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make(__('admin.stock_movement_information'))
                    ->schema([
                        Infolists\Components\TextEntry::make('product.arabicTranslation.name')
                            ->label(__('admin.product')),

                        Infolists\Components\TextEntry::make('variant.arabicTranslation.name')
                            ->label(__('admin.variant'))
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('type')
                            ->label(__('admin.movement_type'))
                            ->badge()
                            ->formatStateUsing(fn ($state) => __('admin.stock_' . $state)),

                        Infolists\Components\TextEntry::make('quantity')
                            ->label(__('admin.quantity'))
                            ->formatStateUsing(fn ($state) => number_format((int) $state)),

                        Infolists\Components\TextEntry::make('before_quantity')
                            ->label(__('admin.before_quantity'))
                            ->formatStateUsing(fn ($state) => $state === null ? '-' : number_format((int) $state)),

                        Infolists\Components\TextEntry::make('after_quantity')
                            ->label(__('admin.after_quantity'))
                            ->formatStateUsing(fn ($state) => $state === null ? '-' : number_format((int) $state)),

                        Infolists\Components\TextEntry::make('order.order_number')
                            ->label(__('admin.order_number'))
                            ->copyable()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('user.name')
                            ->label(__('admin.user'))
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('reference')
                            ->label(__('admin.reference'))
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('notes')
                            ->label(__('admin.notes'))
                            ->placeholder('-')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('created_at')
                            ->label(__('admin.created_at'))
                            ->dateTime(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockMovements::route('/'),
            'view' => Pages\ViewStockMovement::route('/{record}'),
        ];
    }
}