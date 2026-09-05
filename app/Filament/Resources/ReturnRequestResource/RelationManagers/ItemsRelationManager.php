<?php

namespace App\Filament\Resources\ReturnRequestResource\RelationManagers;

use App\Models\OrderItem;
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
        return __('admin.return_items');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('admin.return_item_information'))
                    ->schema([
                        Forms\Components\Select::make('order_item_id')
                            ->label(__('admin.order_item'))
                            ->options(function () {
                                return OrderItem::query()
                                    ->where('order_id', $this->ownerRecord->order_id)
                                    ->get()
                                    ->mapWithKeys(function ($item) {
                                        $label = $item->product_name;

                                        if ($item->variant_name) {
                                            $label .= ' - ' . $item->variant_name;
                                        }

                                        $label .= ' | Qty: ' . $item->quantity;
                                        $label .= ' | ' . number_format((float) $item->subtotal, 2, '.', ',') . ' EGP';

                                        return [
                                            $item->id => $label,
                                        ];
                                    })
                                    ->toArray();
                            })
                            ->searchable()
                            ->preload()
                            ->live()
                            ->required()
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                if (! $state) {
                                    return;
                                }

                                $orderItem = OrderItem::query()->find($state);

                                if (! $orderItem) {
                                    return;
                                }

                                $quantity = min((int) ($get('quantity') ?: 1), (int) $orderItem->quantity);
                                $unitPrice = (float) $orderItem->unit_price;

                                $set('product_id', $orderItem->product_id);
                                $set('product_variant_id', $orderItem->product_variant_id);
                                $set('product_name', $orderItem->product_name);
                                $set('variant_name', $orderItem->variant_name);
                                $set('sku', $orderItem->sku);
                                $set('quantity', $quantity);
                                $set('unit_price', $unitPrice);
                                $set('refund_subtotal', $quantity * $unitPrice);
                            }),

                        Forms\Components\Hidden::make('product_id'),
                        Forms\Components\Hidden::make('product_variant_id'),

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
                            ->required()
                            ->default(1)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                $quantity = (int) ($get('quantity') ?: 1);
                                $unitPrice = (float) ($get('unit_price') ?: 0);

                                $set('refund_subtotal', $quantity * $unitPrice);
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

                                $set('refund_subtotal', $quantity * $unitPrice);
                            }),

                        Forms\Components\TextInput::make('refund_subtotal')
                            ->label(__('admin.refund_subtotal'))
                            ->numeric()
                            ->default(0)
                            ->prefix('EGP')
                            ->readOnly(),

                        Forms\Components\Textarea::make('reason')
                            ->label(__('admin.return_reason'))
                            ->rows(3)
                            ->columnSpanFull(),
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
            ->modifyQueryUsing(fn ($query) => $query->with([
                'orderItem',
                'product.arabicTranslation',
                'variant.arabicTranslation',
            ]))
            ->recordTitleAttribute('product_name')
            ->columns([
                Tables\Columns\TextColumn::make('product_name')
                    ->label(__('admin.product_name'))
                    ->searchable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('variant_name')
                    ->label(__('admin.variant_name'))
                    ->placeholder('-')
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
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2, '.', ',') . ' EGP'),

                Tables\Columns\TextColumn::make('refund_subtotal')
                    ->label(__('admin.refund_subtotal'))
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2, '.', ',') . ' EGP')
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('reason')
                    ->label(__('admin.return_reason'))
                    ->limit(40)
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('admin.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label(__('admin.add_return_item'))
                    ->mutateFormDataUsing(function (array $data): array {
                        return $this->prepareReturnItemData($data);
                    })
                    ->after(function (): void {
                        $this->recalculateReturnTotal();
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
                        return $this->prepareReturnItemData($data);
                    })
                    ->after(function (): void {
                        $this->recalculateReturnTotal();
                    }),

                Tables\Actions\DeleteAction::make()
                    ->after(function (): void {
                        $this->recalculateReturnTotal();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->after(function (): void {
                        $this->recalculateReturnTotal();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    protected function prepareReturnItemData(array $data): array
    {
        $orderItem = null;

        if (! empty($data['order_item_id'])) {
            $orderItem = OrderItem::query()->find($data['order_item_id']);
        }

        $quantity = (int) ($data['quantity'] ?? 1);
        $unitPrice = (float) ($data['unit_price'] ?? 0);

        $data['refund_subtotal'] = $quantity * $unitPrice;

        $data['snapshot'] = [
            'order_item_id' => $orderItem?->id,
            'product_id' => $data['product_id'] ?? $orderItem?->product_id,
            'product_variant_id' => $data['product_variant_id'] ?? $orderItem?->product_variant_id,
            'product_name' => $data['product_name'] ?? $orderItem?->product_name,
            'variant_name' => $data['variant_name'] ?? $orderItem?->variant_name,
            'sku' => $data['sku'] ?? $orderItem?->sku,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'refund_subtotal' => $data['refund_subtotal'],
        ];

        unset($data['snapshot_preview']);

        return $data;
    }

    protected function recalculateReturnTotal(): void
    {
        $returnRequest = $this->ownerRecord->fresh('items');

        $refundTotal = $returnRequest->items->sum(fn ($item) => (float) $item->refund_subtotal);

        $returnRequest->update([
            'refund_total' => $refundTotal,
        ]);
    }
}