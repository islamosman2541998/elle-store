<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductDiscountResource\Pages;
use App\Models\Product;
use App\Models\ProductDiscount;
use App\Models\ProductVariant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductDiscountResource extends Resource
{
    protected static ?string $model = ProductDiscount::class;

    protected static ?string $navigationIcon = 'heroicon-o-percent-badge';

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.promotions');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.product_discounts');
    }

    public static function getModelLabel(): string
    {
        return __('admin.product_discount');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.product_discounts');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Product Discount Tabs')
                    ->columnSpanFull()
                    ->tabs([
                        Forms\Components\Tabs\Tab::make(__('admin.discount_information'))
                            ->schema([
                                Forms\Components\Section::make(__('admin.basic_information'))
                                    ->schema([
                                        Forms\Components\TextInput::make('name_ar')
                                            ->label(__('admin.name_ar'))
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('name_en')
                                            ->label(__('admin.name_en'))
                                            ->maxLength(255),

                                        Forms\Components\Select::make('product_id')
                                            ->label(__('admin.product'))
                                            ->options(function () {
                                                return Product::query()
                                                    ->with('arabicTranslation')
                                                    ->where('is_active', true)
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
                                                    ->with([
                                                        'arabicTranslation',
                                                        'englishTranslation',
                                                        'attributeValues.attribute.arabicTranslation',
                                                        'attributeValues.attributeValue.arabicTranslation',
                                                    ])
                                                    ->where('is_active', true)
                                                    ->get()
                                                    ->mapWithKeys(function ($variant) {
                                                        $variantName = $variant->arabicTranslation?->name
                                                            ?? $variant->englishTranslation?->name
                                                            ?? 'Variant #' . $variant->id;

                                                        $attributes = $variant->attributeValues
                                                            ->map(function ($item) {
                                                                $attributeName = $item->attribute?->arabicTranslation?->name ?? '-';
                                                                $valueName = $item->attributeValue?->arabicTranslation?->value ?? '-';

                                                                return $attributeName . ': ' . $valueName;
                                                            })
                                                            ->implode(' | ');

                                                        $label = $variantName;

                                                        if ($attributes) {
                                                            $label .= ' — ' . $attributes;
                                                        }

                                                        if ($variant->sku) {
                                                            $label .= ' — SKU: ' . $variant->sku;
                                                        }

                                                        return [
                                                            $variant->id => $label,
                                                        ];
                                                    })
                                                    ->toArray();
                                            })
                                            ->searchable()
                                            ->preload()
                                            ->nullable()
                                            ->helperText(__('admin.product_discount_variant_helper')),

                                        Forms\Components\Toggle::make('is_active')
                                            ->label(__('admin.active'))
                                            ->default(true),

                                        Forms\Components\TextInput::make('priority')
                                            ->label(__('admin.priority'))
                                            ->numeric()
                                            ->default(0)
                                            ->helperText(__('admin.discount_priority_helper')),
                                    ])
                                    ->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make(__('admin.discount_settings'))
                            ->schema([
                                Forms\Components\Section::make(__('admin.discount_settings'))
                                    ->schema([
                                        Forms\Components\Select::make('discount_type')
                                            ->label(__('admin.discount_type'))
                                            ->options([
                                                'percentage' => __('admin.percentage'),
                                                'fixed' => __('admin.fixed_amount'),
                                            ])
                                            ->required()
                                            ->default('percentage')
                                            ->live(),

                                        Forms\Components\TextInput::make('discount_value')
                                            ->label(__('admin.discount_value'))
                                            ->numeric()
                                            ->required()
                                            ->default(0)
                                            ->suffix(fn (Forms\Get $get) => $get('discount_type') === 'percentage' ? '%' : null)
                                            ->prefix(fn (Forms\Get $get) => $get('discount_type') === 'fixed' ? 'EGP' : null),
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

                        Forms\Components\Tabs\Tab::make(__('admin.notes'))
                            ->schema([
                                Forms\Components\Textarea::make('notes')
                                    ->label(__('admin.notes'))
                                    ->rows(4)
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with([
                'product.arabicTranslation',
                'variant.arabicTranslation',
                'variant.attributeValues.attribute.arabicTranslation',
                'variant.attributeValues.attributeValue.arabicTranslation',
            ]))
            ->columns([
                Tables\Columns\TextColumn::make('name_ar')
                    ->label(__('admin.name_ar'))
                    ->searchable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('product.arabicTranslation.name')
                    ->label(__('admin.product'))
                    ->searchable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('variant_display')
                    ->label(__('admin.variant'))
                    ->state(function ($record) {
                        if (! $record->variant) {
                            return '-';
                        }

                        $variantName = $record->variant->arabicTranslation?->name
                            ?? 'Variant #' . $record->variant->id;

                        $attributes = $record->variant->attributeValues
                            ->map(function ($item) {
                                $attributeName = $item->attribute?->arabicTranslation?->name ?? '-';
                                $valueName = $item->attributeValue?->arabicTranslation?->value ?? '-';

                                return $attributeName . ': ' . $valueName;
                            })
                            ->implode(' | ');

                        return $attributes ? $variantName . ' — ' . $attributes : $variantName;
                    })
                    ->wrap()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('discount_type')
                    ->label(__('admin.discount_type'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => __('admin.discount_' . $state))
                    ->color(fn ($state) => match ($state) {
                        'percentage' => 'info',
                        'fixed' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('discount_value')
                    ->label(__('admin.discount_value'))
                    ->formatStateUsing(function ($state, $record) {
                        if ($record->discount_type === 'percentage') {
                            return number_format((float) $state, 2, '.', ',') . '%';
                        }

                        return number_format((float) $state, 2, '.', ',') . ' EGP';
                    }),

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
                Tables\Filters\SelectFilter::make('discount_type')
                    ->label(__('admin.discount_type'))
                    ->options([
                        'percentage' => __('admin.percentage'),
                        'fixed' => __('admin.fixed_amount'),
                    ]),

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
            ->defaultSort('priority', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make(__('admin.product_discount_overview'))
                    ->schema([
                        Infolists\Components\TextEntry::make('name_ar')
                            ->label(__('admin.name_ar'))
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('name_en')
                            ->label(__('admin.name_en'))
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('product.arabicTranslation.name')
                            ->label(__('admin.product')),

                        Infolists\Components\TextEntry::make('variant.arabicTranslation.name')
                            ->label(__('admin.variant'))
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('discount_type')
                            ->label(__('admin.discount_type'))
                            ->badge()
                            ->formatStateUsing(fn ($state) => __('admin.discount_' . $state)),

                        Infolists\Components\TextEntry::make('discount_value')
                            ->label(__('admin.discount_value'))
                            ->formatStateUsing(function ($state, $record) {
                                if ($record->discount_type === 'percentage') {
                                    return number_format((float) $state, 2, '.', ',') . '%';
                                }

                                return number_format((float) $state, 2, '.', ',') . ' EGP';
                            }),

                        Infolists\Components\TextEntry::make('priority')
                            ->label(__('admin.priority')),

                        Infolists\Components\IconEntry::make('is_active')
                            ->label(__('admin.active'))
                            ->boolean(),
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

                Infolists\Components\Section::make(__('admin.notes'))
                    ->schema([
                        Infolists\Components\TextEntry::make('notes')
                            ->label(__('admin.notes'))
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
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
            'index' => Pages\ListProductDiscounts::route('/'),
            'create' => Pages\CreateProductDiscount::route('/create'),
            'view' => Pages\ViewProductDiscount::route('/{record}'),
            'edit' => Pages\EditProductDiscount::route('/{record}/edit'),
        ];
    }
}