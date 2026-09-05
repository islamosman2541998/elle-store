<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\ProductPricingService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use App\Services\OrderShippingService;
use Filament\Tables;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    public static function getTitle($ownerRecord, string $pageClass): string
    {
        return __('admin.order_items');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('admin.item_information'))
                    ->schema([
                        Forms\Components\Select::make('product_id')
                            ->label(__('admin.product'))
                            ->options(function () {
                                return Product::query()
                                    ->with('arabicTranslation')
                                    ->where('is_active', true)
                                    ->orderByDesc('created_at')
                                    ->get()
                                    ->mapWithKeys(fn($product) => [
                                        $product->id => $product->arabicTranslation?->name ?? 'Product #' . $product->id,
                                    ])
                                    ->toArray();
                            })
                            ->searchable()
                            ->preload()
                            ->live()
                            ->required()
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                $set('product_variant_id', null);

                                $product = Product::query()
                                    ->with(['arabicTranslation', 'englishTranslation'])
                                    ->find($state);

                                if (! $product) {
                                    $set('product_name', null);
                                    $set('variant_name', null);
                                    $set('sku', null);
                                    $set('unit_price', 0);
                                    $set('subtotal', 0);

                                    return;
                                }

                                $pricing = app(ProductPricingService::class)->getProductPrice($product);
                                $unitPrice = (float) ($pricing['final_price'] ?? 0);
                                $quantity = (int) ($get('quantity') ?: 1);

                                $set('product_name', $product->arabicTranslation?->name ?? $product->englishTranslation?->name ?? 'Product #' . $product->id);
                                $set('variant_name', null);
                                $set('sku', $product->sku);
                                $set('unit_price', $unitPrice);
                                $set('subtotal', $quantity * $unitPrice);
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
                            ->live()
                            ->nullable()
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                $this->updateOrderItemFromVariant($set, $get, $state);
                            }),

                        Forms\Components\TextInput::make('product_name')
                            ->label(__('admin.product_name'))
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('variant_name')
                            ->label(__('admin.variant_name'))
                            ->maxLength(255),

                        Forms\Components\TextInput::make('sku')
                            ->label(__('admin.sku'))
                            ->maxLength(255),

                        Forms\Components\TextInput::make('quantity')
                            ->label(__('admin.quantity'))
                            ->numeric()
                            ->minValue(1)
                            ->default(1)
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                $quantity = (int) ($get('quantity') ?: 1);
                                $unitPrice = (float) ($get('unit_price') ?: 0);

                                $set('subtotal', $quantity * $unitPrice);
                            }),

                        Forms\Components\TextInput::make('unit_price')
                            ->label(__('admin.unit_price'))
                            ->numeric()
                            ->required()
                            ->default(0)
                            ->prefix('EGP')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                $quantity = (int) ($get('quantity') ?: 1);
                                $unitPrice = (float) ($get('unit_price') ?: 0);

                                $set('subtotal', $quantity * $unitPrice);
                            }),

                        Forms\Components\TextInput::make('subtotal')
                            ->label(__('admin.subtotal'))
                            ->numeric()
                            ->default(0)
                            ->prefix('EGP')
                            ->readOnly(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make(__('admin.snapshot'))
                    ->schema([
                        Forms\Components\Textarea::make('snapshot_preview')
                            ->label(__('admin.snapshot'))
                            ->disabled()
                            ->dehydrated(false)
                            ->rows(5)
                            ->helperText(__('admin.snapshot_helper')),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn($query) => $query->with([
                'product.arabicTranslation',
                'product.englishTranslation',
                'variant.arabicTranslation',
                'variant.englishTranslation',
                'variant.attributeValues.attribute.arabicTranslation',
                'variant.attributeValues.attributeValue.arabicTranslation',
            ]))
            ->recordTitleAttribute('product_name')
            ->columns([
                Tables\Columns\ImageColumn::make('product.main_image')
                    ->label(__('admin.image'))
                    ->disk('public')
                    ->square(),

                Tables\Columns\TextColumn::make('product_name')
                    ->label(__('admin.product_name'))
                    ->searchable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('variant_display')
                    ->label(__('admin.variant'))
                    ->state(function ($record) {
                        if ($record->variant_name) {
                            return $record->variant_name;
                        }

                        if (! $record->variant) {
                            return '-';
                        }

                        $variantName = $record->variant->arabicTranslation?->name
                            ?? $record->variant->englishTranslation?->name
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

                Tables\Columns\TextColumn::make('sku')
                    ->label(__('admin.sku'))
                    ->copyable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('quantity')
                    ->label(__('admin.quantity'))
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('unit_price')
                    ->label(__('admin.unit_price'))
                    ->formatStateUsing(fn($state) => number_format((float) $state, 2, '.', ',') . ' EGP'),
                Tables\Columns\TextColumn::make('original_unit_price')
                    ->label(__('admin.original_unit_price'))
                    ->formatStateUsing(fn($state) => $state ? number_format((float) $state, 2, '.', ',') . ' EGP' : '-')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('discount_amount')
                    ->label(__('admin.discount_amount'))
                    ->formatStateUsing(fn($state) => number_format((float) $state, 2, '.', ',') . ' EGP')
                    ->badge()
                    ->color('warning')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('discount_source')
                    ->label(__('admin.discount_source'))
                    ->formatStateUsing(fn($state) => $state ? __('admin.' . $state) : '-')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'flash_sale' => 'danger',
                        'regular_sale' => 'info',
                        default => 'gray',
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('subtotal')
                    ->label(__('admin.subtotal'))
                    ->formatStateUsing(fn($state) => number_format((float) $state, 2, '.', ',') . ' EGP')
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('admin.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label(__('admin.add_order_item'))
                    ->mutateFormDataUsing(function (array $data): array {
                        return $this->prepareOrderItemData($data);
                    })
                    ->after(function (): void {
                        $this->recalculateOrderTotals();
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateRecordDataUsing(function (array $data, $record): array {
                        $data['snapshot_preview'] = $record->snapshot
                            ? json_encode($record->snapshot, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
                            : null;

                        return $data;
                    })
                    ->mutateFormDataUsing(function (array $data): array {
                        return $this->prepareOrderItemData($data);
                    })
                    ->after(function (): void {
                        $this->recalculateOrderTotals();
                    }),

                Tables\Actions\DeleteAction::make()
                    ->after(function (): void {
                        $this->recalculateOrderTotals();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->after(function (): void {
                        $this->recalculateOrderTotals();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    protected function updateOrderItemFromVariant(Forms\Set $set, Forms\Get $get, $variantId): void
    {
        $productId = $get('product_id');
        $quantity = (int) ($get('quantity') ?: 1);

        if (! $productId) {
            return;
        }

        if (! $variantId) {
            $product = Product::query()
                ->with(['arabicTranslation', 'englishTranslation'])
                ->find($productId);

            if (! $product) {
                return;
            }

            $pricing = app(ProductPricingService::class)->getProductPrice($product);
            $unitPrice = (float) ($pricing['final_price'] ?? 0);

            $set('variant_name', null);
            $set('sku', $product->sku);
            $set('unit_price', $unitPrice);
            $set('subtotal', $quantity * $unitPrice);

            return;
        }

        $variant = ProductVariant::query()
            ->with([
                'product',
                'arabicTranslation',
                'englishTranslation',
                'attributeValues.attribute.arabicTranslation',
                'attributeValues.attribute.englishTranslation',
                'attributeValues.attributeValue.arabicTranslation',
                'attributeValues.attributeValue.englishTranslation',
            ])
            ->find($variantId);

        if (! $variant) {
            return;
        }

        $pricing = app(ProductPricingService::class)->getVariantPrice($variant);
        $unitPrice = (float) ($pricing['final_price'] ?? 0);

        $attributesAr = $variant->attributeValues
            ->map(function ($item) {
                $attributeName = $item->attribute?->arabicTranslation?->name ?? '-';
                $valueName = $item->attributeValue?->arabicTranslation?->value ?? '-';

                return $attributeName . ': ' . $valueName;
            })
            ->implode(' | ');

        $variantName = $variant->arabicTranslation?->name
            ?? $variant->englishTranslation?->name
            ?? 'Variant #' . $variant->id;

        if ($attributesAr) {
            $variantName .= ' — ' . $attributesAr;
        }

        $set('variant_name', $variantName);
        $set('sku', $variant->sku);
        $set('unit_price', $unitPrice);
        $set('subtotal', $quantity * $unitPrice);
    }

    protected function prepareOrderItemData(array $data): array
    {
        $product = Product::query()
            ->with(['arabicTranslation', 'englishTranslation'])
            ->find($data['product_id']);

        $variant = null;

        if (! empty($data['product_variant_id'])) {
            $variant = ProductVariant::query()
                ->with([
                    'arabicTranslation',
                    'englishTranslation',
                    'attributeValues.attribute.arabicTranslation',
                    'attributeValues.attribute.englishTranslation',
                    'attributeValues.attributeValue.arabicTranslation',
                    'attributeValues.attributeValue.englishTranslation',
                ])
                ->find($data['product_variant_id']);
        }

        $quantity = (int) ($data['quantity'] ?? 1);
        $unitPrice = (float) ($data['unit_price'] ?? 0);
        $pricing = [
            'original_price' => $unitPrice,
            'final_price' => $unitPrice,
            'discount_amount' => 0,
            'discount_source' => null,
            'flash_sale_item_id' => null,
        ];

        if ($variant) {
            $pricing = app(ProductPricingService::class)->getVariantPrice($variant);
        } elseif ($product) {
            $pricing = app(ProductPricingService::class)->getProductPrice($product);
        }

        $data['original_unit_price'] = (float) ($pricing['original_price'] ?? $unitPrice);
        $data['discount_amount'] = (float) ($pricing['discount_amount'] ?? 0);
        $data['discount_source'] = $pricing['discount_source'] ?? null;
        $data['flash_sale_item_id'] = $pricing['flash_sale_item_id'] ?? null;
        $data['product_discount_id'] = $pricing['product_discount_id'] ?? null;

        $data['subtotal'] = $quantity * $unitPrice;

        $data['snapshot'] = [
            'product_id' => $product?->id,
            'product_name_ar' => $product?->arabicTranslation?->name,
            'product_name_en' => $product?->englishTranslation?->name,
            'product_sku' => $product?->sku,
            'product_image' => $product?->main_image,

            'variant_id' => $variant?->id,
            'variant_name_ar' => $variant?->arabicTranslation?->name,
            'variant_name_en' => $variant?->englishTranslation?->name,
            'variant_sku' => $variant?->sku,
            'variant_attributes' => $variant
                ? $variant->attributeValues->map(function ($item) {
                    return [
                        'attribute_id' => $item->attribute_id,
                        'attribute_name_ar' => $item->attribute?->arabicTranslation?->name,
                        'attribute_name_en' => $item->attribute?->englishTranslation?->name,
                        'value_id' => $item->attribute_value_id,
                        'value_ar' => $item->attributeValue?->arabicTranslation?->value,
                        'value_en' => $item->attributeValue?->englishTranslation?->value,
                    ];
                })->values()->toArray()
                : [],
        ];

        unset($data['snapshot_preview']);

        return $data;
    }

    protected function recalculateOrderTotals(): void
    {
        $order = $this->ownerRecord->fresh('items');

        $subtotal = $order->items->sum(fn($item) => (float) $item->subtotal);

        $grandTotal = $subtotal
            - (float) $order->discount_total
            + (float) $order->shipping_total
            + (float) $order->tax_total;

        $order->update([
            'subtotal' => $subtotal,
            'grand_total' => max($grandTotal, 0),
        ]);

        if ($order->shipping_city_id) {
            app(OrderShippingService::class)->applyShippingToOrder(
                $order->fresh(),
                (int) $order->shipping_city_id
            );
        }
    }
}