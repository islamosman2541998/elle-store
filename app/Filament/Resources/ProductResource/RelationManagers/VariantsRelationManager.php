<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use App\Services\StockService;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

class VariantsRelationManager extends RelationManager
{
    protected static string $relationship = 'variants';

    protected array $translationsData = [];

    protected array $attributeValuesData = [];

    public static function getTitle($ownerRecord, string $pageClass): string
    {
        return __('admin.product_variants');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Variant Tabs')
                    ->columnSpanFull()
                    ->tabs([
                        Forms\Components\Tabs\Tab::make(__('admin.general_data'))
                            ->schema([
                                Forms\Components\Section::make(__('admin.basic_information'))
                                    ->schema([
                                        Forms\Components\TextInput::make('sku')
                                            ->label(__('admin.sku'))
                                            ->maxLength(255)
                                            ->unique(ignoreRecord: true),

                                        Forms\Components\TextInput::make('barcode')
                                            ->label(__('admin.barcode'))
                                            ->maxLength(255),

                                        Forms\Components\FileUpload::make('image')
                                            ->label(__('admin.image'))
                                            ->image()
                                            ->directory('products/variants')
                                            ->disk('public')
                                            ->imageEditor()
                                            ->columnSpanFull(),

                                        Forms\Components\Toggle::make('is_active')
                                            ->label(__('admin.active'))
                                            ->default(true),

                                        Forms\Components\TextInput::make('sort_order')
                                            ->label(__('admin.sort_order'))
                                            ->numeric()
                                            ->default(0),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make(__('admin.variant_attributes'))
                                    ->schema(function () {
                                        $productAttributes = $this->ownerRecord
                                            ->productAttributes()
                                            ->with([
                                                'attribute.arabicTranslation',
                                                'attribute.englishTranslation',
                                                'attribute.values.arabicTranslation',
                                                'attribute.values.englishTranslation',
                                            ])
                                            ->orderBy('sort_order')
                                            ->get();

                                        if ($productAttributes->isEmpty()) {
                                            return [
                                                Forms\Components\Placeholder::make('no_product_attributes')
                                                    ->label(__('admin.no_product_attributes'))
                                                    ->content(__('admin.add_product_attributes_first')),
                                            ];
                                        }

                                        return $productAttributes->map(function ($productAttribute) {
                                            $attribute = $productAttribute->attribute;

                                            return Forms\Components\Select::make('attribute_values.' . $attribute->id)
                                                ->label(
                                                    app()->getLocale() === 'ar'
                                                        ? ($attribute->arabicTranslation?->name ?? 'Attribute #' . $attribute->id)
                                                        : ($attribute->englishTranslation?->name ?? 'Attribute #' . $attribute->id)
                                                )
                                                ->options(
                                                    $attribute->values()
                                                        ->with('arabicTranslation', 'englishTranslation')
                                                        ->where('is_active', true)
                                                        ->orderBy('sort_order')
                                                        ->get()
                                                        ->mapWithKeys(function ($value) {
                                                            return [
                                                                $value->id => app()->getLocale() === 'ar'
                                                                    ? ($value->arabicTranslation?->value ?? 'Value #' . $value->id)
                                                                    : ($value->englishTranslation?->value ?? 'Value #' . $value->id),
                                                            ];
                                                        })
                                                        ->toArray()
                                                )
                                                ->searchable()
                                                ->preload()
                                                ->required();
                                        })->toArray();
                                    })
                                    ->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make(__('admin.arabic_content'))
                            ->schema([
                                Forms\Components\TextInput::make('ar_name')
                                    ->label(__('admin.name_ar'))
                                    ->required()
                                    ->maxLength(255),
                            ]),

                        Forms\Components\Tabs\Tab::make(__('admin.english_content'))
                            ->schema([
                                Forms\Components\TextInput::make('en_name')
                                    ->label(__('admin.name_en'))
                                    ->required()
                                    ->maxLength(255),
                            ]),

                        Forms\Components\Tabs\Tab::make(__('admin.pricing_inventory'))
                            ->schema([
                                Forms\Components\Section::make(__('admin.pricing'))
                                    ->schema([
                                        Forms\Components\TextInput::make('price')
                                            ->label(__('admin.price'))
                                            ->numeric()
                                            ->nullable()
                                            ->prefix('EGP'),

                                        Forms\Components\TextInput::make('sale_price')
                                            ->label(__('admin.sale_price'))
                                            ->numeric()
                                            ->nullable()
                                            ->prefix('EGP')
                                            ->lt('price'),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make(__('admin.inventory'))
                                    ->schema([
                                        Forms\Components\TextInput::make('stock_quantity')
                                            ->label(__('admin.stock_quantity'))
                                            ->numeric()
                                            ->required()
                                            ->default(0),

                                        Forms\Components\TextInput::make('low_stock_alert')
                                            ->label(__('admin.low_stock_alert'))
                                            ->numeric()
                                            ->required()
                                            ->default(5),
                                    ])
                                    ->columns(2),
                            ]),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn($query) => $query->with([
                'arabicTranslation',
                'englishTranslation',
                'attributeValues.attribute.arabicTranslation',
                'attributeValues.attribute.englishTranslation',
                'attributeValues.attributeValue.arabicTranslation',
                'attributeValues.attributeValue.englishTranslation',
            ]))
            ->recordTitleAttribute('sku')
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label(__('admin.image'))
                    ->disk('public')
                    ->square(),

                Tables\Columns\TextColumn::make('arabicTranslation.name')
                    ->label(__('admin.name_ar'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('englishTranslation.name')
                    ->label(__('admin.name_en'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('attribute_values_display')
                    ->label(__('admin.attribute_values'))
                    ->state(function ($record) {
                        $record->loadMissing([
                            'attributeValues.attribute.arabicTranslation',
                            'attributeValues.attribute.englishTranslation',
                            'attributeValues.attributeValue.arabicTranslation',
                            'attributeValues.attributeValue.englishTranslation',
                        ]);

                        return $record->attributeValues
                            ->map(function ($item) {
                                $attributeName = app()->getLocale() === 'ar'
                                    ? ($item->attribute?->arabicTranslation?->name ?? '-')
                                    : ($item->attribute?->englishTranslation?->name ?? '-');

                                $valueName = app()->getLocale() === 'ar'
                                    ? ($item->attributeValue?->arabicTranslation?->value ?? '-')
                                    : ($item->attributeValue?->englishTranslation?->value ?? '-');

                                return $attributeName . ': ' . $valueName;
                            })
                            ->implode(' | ');
                    })
                    ->wrap(),

                Tables\Columns\TextColumn::make('sku')
                    ->label(__('admin.sku'))
                    ->searchable()
                    ->copyable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('price')
                    ->label(__('admin.price'))
                    ->formatStateUsing(fn($state) => $state ? number_format((float) $state, 2, '.', ',') . ' EGP' : '-')
                    ->sortable(),

                Tables\Columns\TextColumn::make('sale_price')
                    ->label(__('admin.sale_price'))
                    ->formatStateUsing(fn($state) => $state ? number_format((float) $state, 2, '.', ',') . ' EGP' : '-')
                    ->sortable(),

                Tables\Columns\TextColumn::make('stock_quantity')
                    ->label(__('admin.stock'))
                    ->formatStateUsing(fn($state) => number_format((int) $state))
                    ->badge()
                    ->color(fn($state) => $state <= 5 ? 'danger' : 'success')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('admin.active'))
                    ->boolean(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label(__('admin.sort_order'))
                    ->sortable(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label(__('admin.add_variant'))
                    ->mutateFormDataUsing(function (array $data): array {
                        $this->translationsData = [
                            'ar' => [
                                'name' => $data['ar_name'],
                            ],
                            'en' => [
                                'name' => $data['en_name'],
                            ],
                        ];

                        $this->attributeValuesData = $data['attribute_values'] ?? [];

                        unset(
                            $data['ar_name'],
                            $data['en_name'],
                            $data['attribute_values'],
                        );

                        $this->ensureUniqueVariantCombination();

                        return $data;
                    })
                    ->after(function ($record): void {
                        foreach ($this->translationsData as $locale => $translationData) {
                            $record->translations()->create([
                                'locale' => $locale,
                                ...$translationData,
                            ]);
                        }

                        foreach ($this->attributeValuesData as $attributeId => $attributeValueId) {
                            if (! $attributeValueId) {
                                continue;
                            }

                            $record->attributeValues()->create([
                                'attribute_id' => $attributeId,
                                'attribute_value_id' => $attributeValueId,
                            ]);
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('adjust_stock')
                    ->label(__('admin.adjust_stock'))
                    ->icon('heroicon-o-arrows-up-down')->color('warning')
                    ->form([
                        Forms\Components\Select::make('movement_direction')
                            ->label(__('admin.movement_direction'))
                            ->options([
                                'increase' => __('admin.increase_stock'),
                                'decrease' => __('admin.decrease_stock'),
                            ])
                            ->required()
                            ->default('increase'),

                        Forms\Components\TextInput::make('quantity')
                            ->label(__('admin.quantity'))
                            ->numeric()
                            ->minValue(1)
                            ->required(),

                        Forms\Components\TextInput::make('reference')
                            ->label(__('admin.reference'))
                            ->maxLength(255),

                        Forms\Components\Textarea::make('notes')
                            ->label(__('admin.notes'))
                            ->rows(3),
                    ])
                    ->action(function ($record, array $data): void {
                        $quantity = (int) $data['quantity'];

                        if ($data['movement_direction'] === 'decrease') {
                            $quantity *= -1;
                        }

                        app(StockService::class)->adjustStock(
                            productId: $record->product_id,
                            variantId: $record->id,
                            quantity: $quantity,
                            type: $quantity > 0 ? 'stock_in' : 'stock_out',
                            reference: $data['reference'] ?? null,
                            notes: $data['notes'] ?? null,
                        );
                    }),
                Tables\Actions\EditAction::make()
                    ->mutateRecordDataUsing(function (array $data, $record): array {
                        $record->load([
                            'arabicTranslation',
                            'englishTranslation',
                            'attributeValues',
                        ]);

                        $data['ar_name'] = $record->arabicTranslation?->name;
                        $data['en_name'] = $record->englishTranslation?->name;

                        $data['attribute_values'] = $record->attributeValues
                            ->pluck('attribute_value_id', 'attribute_id')
                            ->toArray();

                        return $data;
                    })
                    ->mutateFormDataUsing(function (array $data): array {
                        $this->translationsData = [
                            'ar' => [
                                'name' => $data['ar_name'],
                            ],
                            'en' => [
                                'name' => $data['en_name'],
                            ],
                        ];

                        $this->attributeValuesData = $data['attribute_values'] ?? [];

                        unset(
                            $data['ar_name'],
                            $data['en_name'],
                            $data['attribute_values'],
                        );

                        $ignoreVariantId = $this->mountedTableActionRecord
                            ? (int) $this->mountedTableActionRecord
                            : null;

                        $this->ensureUniqueVariantCombination($ignoreVariantId);

                        return $data;
                    })
                    ->after(function ($record): void {
                        foreach ($this->translationsData as $locale => $translationData) {
                            $record->translations()->updateOrCreate(
                                [
                                    'locale' => $locale,
                                ],
                                $translationData
                            );
                        }

                        $record->attributeValues()->delete();

                        foreach ($this->attributeValuesData as $attributeId => $attributeValueId) {
                            if (! $attributeValueId) {
                                continue;
                            }

                            $record->attributeValues()->create([
                                'attribute_id' => $attributeId,
                                'attribute_value_id' => $attributeValueId,
                            ]);
                        }
                    }),

                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('sort_order');
    }

    protected function ensureUniqueVariantCombination(?int $ignoreVariantId = null): void
    {
        $selectedValues = collect($this->attributeValuesData)
            ->filter()
            ->mapWithKeys(fn($valueId, $attributeId) => [
                (int) $attributeId => (int) $valueId,
            ])
            ->sortKeys();

        if ($selectedValues->isEmpty()) {
            return;
        }

        $variants = $this->ownerRecord
            ->variants()
            ->with('attributeValues')
            ->when($ignoreVariantId, fn($query) => $query->where('id', '!=', $ignoreVariantId))
            ->get();

        foreach ($variants as $variant) {
            $existingValues = $variant->attributeValues
                ->mapWithKeys(fn($item) => [
                    (int) $item->attribute_id => (int) $item->attribute_value_id,
                ])
                ->sortKeys();

            if ($selectedValues->toArray() === $existingValues->toArray()) {
                Notification::make()
                    ->title(__('admin.duplicate_variant_title'))
                    ->body(__('admin.duplicate_variant_body'))
                    ->danger()
                    ->send();

                throw ValidationException::withMessages([
                    'attribute_values' => __('admin.duplicate_variant_body'),
                ]);
            }
        }
    }
}