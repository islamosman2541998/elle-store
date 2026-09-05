<?php

namespace App\Filament\Resources\FlashSaleResource\RelationManagers;

use App\Models\Product;
use App\Models\ProductVariant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    public static function getTitle($ownerRecord, string $pageClass): string
    {
        return __('admin.flash_sale_items');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('admin.flash_sale_item_information'))
                    ->schema([
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
                            ->nullable(),

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

                        Forms\Components\TextInput::make('quantity_limit')
                            ->label(__('admin.quantity_limit'))
                            ->numeric()
                            ->nullable(),

                        Forms\Components\TextInput::make('sold_count')
                            ->label(__('admin.sold_count'))
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->dehydrated(true),

                        Forms\Components\Toggle::make('is_active')
                            ->label(__('admin.active'))
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with([
                'product.arabicTranslation',
                'variant.arabicTranslation',
                'variant.attributeValues.attribute.arabicTranslation',
                'variant.attributeValues.attributeValue.arabicTranslation',
            ]))
            ->recordTitleAttribute('product_id')
            ->columns([
                Tables\Columns\ImageColumn::make('product.main_image')
                    ->label(__('admin.image'))
                    ->disk('public')
                    ->square(),

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
                    ->wrap(),

                Tables\Columns\TextColumn::make('discount_type')
                    ->label(__('admin.discount_type'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => __('admin.discount_' . $state)),

                Tables\Columns\TextColumn::make('discount_value')
                    ->label(__('admin.discount_value'))
                    ->formatStateUsing(function ($state, $record) {
                        if ($record->discount_type === 'percentage') {
                            return number_format((float) $state, 2, '.', ',') . '%';
                        }

                        return number_format((float) $state, 2, '.', ',') . ' EGP';
                    }),

                Tables\Columns\TextColumn::make('quantity_limit')
                    ->label(__('admin.quantity_limit'))
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('sold_count')
                    ->label(__('admin.sold_count'))
                    ->badge(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('admin.active'))
                    ->boolean(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label(__('admin.add_flash_sale_item')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}