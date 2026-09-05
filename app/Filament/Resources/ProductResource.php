<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Filament\Resources\ProductResource\RelationManagers\ImagesRelationManager;
use App\Filament\Resources\ProductResource\RelationManagers\VariantsRelationManager;
use App\Filament\Resources\ProductResource\RelationManagers\ProductAttributesRelationManager;
use App\Services\StockService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.catalog');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.products');
    }

    public static function getModelLabel(): string
    {
        return __('admin.product');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.products');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Product Tabs')
                    ->columnSpanFull()
                    ->tabs([
                        Forms\Components\Tabs\Tab::make(__('admin.general_data'))
                            ->schema([
                                Forms\Components\Section::make(__('admin.basic_information'))
                                    ->schema([
                                        Forms\Components\Select::make('category_id')
                                            ->label(__('admin.category'))
                                            ->options(function () {
                                                return Category::query()
                                                    ->with('arabicTranslation')
                                                    ->orderBy('sort_order')
                                                    ->get()
                                                    ->mapWithKeys(function ($category) {
                                                        return [
                                                            $category->id => $category->arabicTranslation?->name ?? 'Category #' . $category->id,
                                                        ];
                                                    })
                                                    ->toArray();
                                            })
                                            ->searchable()
                                            ->preload()
                                            ->required(),

                                        Forms\Components\Select::make('brand_id')
                                            ->label(__('admin.brand'))
                                            ->options(function () {
                                                return Brand::query()
                                                    ->with('arabicTranslation')
                                                    ->orderBy('sort_order')
                                                    ->get()
                                                    ->mapWithKeys(function ($brand) {
                                                        return [
                                                            $brand->id => $brand->arabicTranslation?->name ?? 'Brand #' . $brand->id,
                                                        ];
                                                    })
                                                    ->toArray();
                                            })
                                            ->searchable()
                                            ->preload()
                                            ->nullable(),

                                        Forms\Components\TextInput::make('sku')
                                            ->label(__('admin.sku'))
                                            ->maxLength(255)
                                            ->unique(ignoreRecord: true),

                                        Forms\Components\TextInput::make('barcode')
                                            ->label(__('admin.barcode'))
                                            ->maxLength(255),

                                        Forms\Components\FileUpload::make('main_image')
                                            ->label(__('admin.main_image'))
                                            ->image()
                                            ->directory('products')
                                            ->disk('public')
                                            ->imageEditor()
                                            ->columnSpanFull(),

                                        Forms\Components\FileUpload::make('gallery_images')
                                            ->label(__('admin.gallery_images'))
                                            ->image()
                                            ->multiple()
                                            ->reorderable()
                                            ->appendFiles()
                                            ->directory('products/gallery')
                                            ->disk('public')
                                            ->imageEditor()
                                            ->maxFiles(10)
                                            ->helperText(__('admin.gallery_images_helper'))
                                            ->visibleOn('create')
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make(__('admin.status'))
                                    ->schema([
                                        Forms\Components\Toggle::make('is_active')
                                            ->label(__('admin.active'))
                                            ->default(true),

                                        Forms\Components\Toggle::make('is_featured')
                                            ->label(__('admin.featured'))
                                            ->default(false),

                                        Forms\Components\DateTimePicker::make('published_at')
                                            ->label(__('admin.published_at'))
                                            ->seconds(false),

                                        Forms\Components\TextInput::make('weight')
                                            ->label(__('admin.weight'))
                                            ->numeric()
                                            ->suffix('kg'),
                                    ])
                                    ->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make(__('admin.arabic_content'))
                            ->schema([
                                Forms\Components\Section::make(__('admin.arabic_basic_content'))
                                    ->schema([
                                        Forms\Components\TextInput::make('ar_name')
                                            ->label(__('admin.name_ar'))
                                            ->required()
                                            ->maxLength(255)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                                if (! $get('ar_slug')) {
                                                    $set('ar_slug', Str::slug($state, '-'));
                                                }
                                            }),

                                        Forms\Components\TextInput::make('ar_slug')
                                            ->label(__('admin.slug_ar'))
                                            ->required()
                                            ->maxLength(255)
                                            ->rules(fn(?Product $record) => [
                                                Rule::unique('product_translations', 'slug')
                                                    ->where('locale', 'ar')
                                                    ->ignore($record?->arabicTranslation?->id),
                                            ]),

                                        Forms\Components\Textarea::make('ar_short_description')
                                            ->label(__('admin.short_description_ar'))
                                            ->rows(3)
                                            ->columnSpanFull(),

                                        Forms\Components\RichEditor::make('ar_description')
                                            ->label(__('admin.description_ar'))
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make(__('admin.seo_ar'))
                                    ->schema([
                                        Forms\Components\TextInput::make('ar_meta_title')
                                            ->label(__('admin.meta_title_ar'))
                                            ->maxLength(255),

                                        Forms\Components\Textarea::make('ar_meta_description')
                                            ->label(__('admin.meta_description_ar'))
                                            ->rows(3),

                                        Forms\Components\Textarea::make('ar_meta_keywords')
                                            ->label(__('admin.meta_keywords_ar'))
                                            ->rows(3)
                                            ->helperText(__('admin.keywords_helper')),
                                    ]),
                            ]),

                        Forms\Components\Tabs\Tab::make(__('admin.english_content'))
                            ->schema([
                                Forms\Components\Section::make(__('admin.english_basic_content'))
                                    ->schema([
                                        Forms\Components\TextInput::make('en_name')
                                            ->label(__('admin.name_en'))
                                            ->required()
                                            ->maxLength(255)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                                if (! $get('en_slug')) {
                                                    $set('en_slug', Str::slug($state, '-'));
                                                }
                                            }),

                                        Forms\Components\TextInput::make('en_slug')
                                            ->label(__('admin.slug_en'))
                                            ->required()
                                            ->maxLength(255)
                                            ->rules(fn(?Product $record) => [
                                                Rule::unique('product_translations', 'slug')
                                                    ->where('locale', 'en')
                                                    ->ignore($record?->englishTranslation?->id),
                                            ]),

                                        Forms\Components\Textarea::make('en_short_description')
                                            ->label(__('admin.short_description_en'))
                                            ->rows(3)
                                            ->columnSpanFull(),

                                        Forms\Components\RichEditor::make('en_description')
                                            ->label(__('admin.description_en'))
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make(__('admin.seo_en'))
                                    ->schema([
                                        Forms\Components\TextInput::make('en_meta_title')
                                            ->label(__('admin.meta_title_en'))
                                            ->maxLength(255),

                                        Forms\Components\Textarea::make('en_meta_description')
                                            ->label(__('admin.meta_description_en'))
                                            ->rows(3),

                                        Forms\Components\Textarea::make('en_meta_keywords')
                                            ->label(__('admin.meta_keywords_en'))
                                            ->rows(3)
                                            ->helperText(__('admin.keywords_helper')),
                                    ]),
                            ]),

                        Forms\Components\Tabs\Tab::make(__('admin.pricing_inventory'))
                            ->schema([
                                Forms\Components\Section::make(__('admin.pricing'))
                                    ->schema([
                                        Forms\Components\TextInput::make('price')
                                            ->label(__('admin.price'))
                                            ->numeric()
                                            ->required()
                                            ->default(0)
                                            ->prefix('EGP'),

                                        Forms\Components\TextInput::make('sale_price')
                                            ->label(__('admin.sale_price'))
                                            ->numeric()
                                            ->nullable()
                                            ->prefix('EGP')
                                            ->lt('price'),

                                        Forms\Components\TextInput::make('cost_price')
                                            ->label(__('admin.cost_price'))
                                            ->numeric()
                                            ->nullable()
                                            ->prefix('EGP'),
                                    ])
                                    ->columns(3),

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

                                        Forms\Components\Toggle::make('manage_stock')
                                            ->label(__('admin.manage_stock'))
                                            ->default(true),

                                        Forms\Components\Toggle::make('allow_backorder')
                                            ->label(__('admin.allow_backorder'))
                                            ->default(false),
                                    ])
                                    ->columns(2),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('main_image')
                    ->label(__('admin.image'))
                    ->disk('public')
                    ->square(),

                Tables\Columns\TextColumn::make('arabicTranslation.name')
                    ->label(__('admin.name_ar'))
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('englishTranslation.name')
                    ->label(__('admin.name_en'))
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('category.arabicTranslation.name')
                    ->label(__('admin.category'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('brand.arabicTranslation.name')
                    ->label(__('admin.brand'))
                    ->placeholder('-')
                    ->sortable(),

                Tables\Columns\TextColumn::make('sku')
                    ->label(__('admin.sku'))
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('price')
                    ->label(__('admin.price'))
                    ->formatStateUsing(fn($state) => number_format((float) $state, 2, '.', ',') . ' EGP')
                    ->sortable(),

                Tables\Columns\TextColumn::make('sale_price')
                    ->label(__('admin.sale_price'))
                    ->formatStateUsing(fn($state) => $state ? number_format((float) $state, 2, '.', ',') . ' EGP' : '-')
                    ->sortable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('stock_quantity')
                    ->label(__('admin.stock'))
                    ->formatStateUsing(fn($state) => number_format((int) $state))
                    ->sortable()
                    ->badge()
                    ->color(fn($state) => $state <= 5 ? 'danger' : 'success'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('admin.active'))
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_featured')
                    ->label(__('admin.featured'))
                    ->boolean(),

                Tables\Columns\TextColumn::make('published_at')
                    ->label(__('admin.published_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('admin.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->label(__('admin.category'))
                    ->options(function () {
                        return Category::query()
                            ->with('arabicTranslation')
                            ->get()
                            ->mapWithKeys(fn($category) => [
                                $category->id => $category->arabicTranslation?->name ?? 'Category #' . $category->id,
                            ])
                            ->toArray();
                    }),

                Tables\Filters\SelectFilter::make('brand_id')
                    ->label(__('admin.brand'))
                    ->options(function () {
                        return Brand::query()
                            ->with('arabicTranslation')
                            ->get()
                            ->mapWithKeys(fn($brand) => [
                                $brand->id => $brand->arabicTranslation?->name ?? 'Brand #' . $brand->id,
                            ])
                            ->toArray();
                    }),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('admin.active')),

                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label(__('admin.featured')),

                Tables\Filters\TrashedFilter::make(),
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
                    ->action(function (Product $record, array $data): void {
                        $quantity = (int) $data['quantity'];

                        if ($data['movement_direction'] === 'decrease') {
                            $quantity *= -1;
                        }

                        app(StockService::class)->adjustStock(
                            productId: $record->id,
                            variantId: null,
                            quantity: $quantity,
                            type: $quantity > 0 ? 'stock_in' : 'stock_out',
                            reference: $data['reference'] ?? null,
                            notes: $data['notes'] ?? null,
                        );
                    }),
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
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make(__('admin.product_overview'))
                    ->schema([
                        Infolists\Components\Split::make([
                            Infolists\Components\Grid::make(1)
                                ->schema([
                                    Infolists\Components\ImageEntry::make('main_image')
                                        ->label(__('admin.main_image'))
                                        ->disk('public')
                                        ->height(260)
                                        ->extraImgAttributes([
                                            'class' => 'rounded-xl object-cover shadow-sm border',
                                        ]),
                                ])
                                ->grow(false),

                            Infolists\Components\Grid::make(2)
                                ->schema([
                                    Infolists\Components\TextEntry::make('arabicTranslation.name')
                                        ->label(__('admin.name_ar'))
                                        ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                        ->weight('bold'),

                                    Infolists\Components\TextEntry::make('englishTranslation.name')
                                        ->label(__('admin.name_en'))
                                        ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                        ->weight('bold'),

                                    Infolists\Components\TextEntry::make('category.arabicTranslation.name')
                                        ->label(__('admin.category'))
                                        ->badge()
                                        ->color('info'),

                                    Infolists\Components\TextEntry::make('brand.arabicTranslation.name')
                                        ->label(__('admin.brand'))
                                        ->badge()
                                        ->color('gray')
                                        ->placeholder('-'),

                                    Infolists\Components\TextEntry::make('sku')
                                        ->label(__('admin.sku'))
                                        ->copyable()
                                        ->placeholder('-'),

                                    Infolists\Components\TextEntry::make('barcode')
                                        ->label(__('admin.barcode'))
                                        ->copyable()
                                        ->placeholder('-'),

                                    Infolists\Components\IconEntry::make('is_active')
                                        ->label(__('admin.active'))
                                        ->boolean(),

                                    Infolists\Components\IconEntry::make('is_featured')
                                        ->label(__('admin.featured'))
                                        ->boolean(),
                                ]),
                        ])->from('md'),
                    ])
                    ->columnSpanFull(),

                Infolists\Components\Section::make(__('admin.pricing_inventory'))
                    ->schema([
                        Infolists\Components\TextEntry::make('price')
                            ->label(__('admin.price'))
                            ->formatStateUsing(fn($state) => number_format((float) $state, 2, '.', ',') . ' EGP')
                            ->badge()
                            ->color('success'),

                        Infolists\Components\TextEntry::make('sale_price')
                            ->label(__('admin.sale_price'))
                            ->formatStateUsing(fn($state) => $state ? number_format((float) $state, 2, '.', ',') . ' EGP' : '-')
                            ->badge()
                            ->color('warning')
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('cost_price')
                            ->label(__('admin.cost_price'))
                            ->formatStateUsing(fn($state) => $state ? number_format((float) $state, 2, '.', ',') . ' EGP' : '-')
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('stock_quantity')
                            ->label(__('admin.stock_quantity'))
                            ->formatStateUsing(fn($state) => number_format((int) $state))
                            ->badge()
                            ->color(fn($state) => $state <= 5 ? 'danger' : 'success'),

                        Infolists\Components\TextEntry::make('low_stock_alert')
                            ->label(__('admin.low_stock_alert'))
                            ->formatStateUsing(fn($state) => number_format((int) $state)),

                        Infolists\Components\TextEntry::make('weight')
                            ->label(__('admin.weight'))
                            ->formatStateUsing(fn($state) => $state ? number_format((float) $state, 2, '.', ',') . ' kg' : '-')
                            ->placeholder('-'),

                        Infolists\Components\IconEntry::make('manage_stock')
                            ->label(__('admin.manage_stock'))
                            ->boolean(),

                        Infolists\Components\IconEntry::make('allow_backorder')
                            ->label(__('admin.allow_backorder'))
                            ->boolean(),
                    ])
                    ->columns(4),

                Infolists\Components\Section::make(__('admin.arabic_content'))
                    ->schema([
                        Infolists\Components\TextEntry::make('arabicTranslation.name')
                            ->label(__('admin.name_ar'))
                            ->weight('bold'),

                        Infolists\Components\TextEntry::make('arabicTranslation.slug')
                            ->label(__('admin.slug_ar'))
                            ->copyable(),

                        Infolists\Components\TextEntry::make('arabicTranslation.short_description')
                            ->label(__('admin.short_description_ar'))
                            ->columnSpanFull()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('arabicTranslation.description')
                            ->label(__('admin.description_ar'))
                            ->html()
                            ->columnSpanFull()
                            ->placeholder('-'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Infolists\Components\Section::make(__('admin.english_content'))
                    ->schema([
                        Infolists\Components\TextEntry::make('englishTranslation.name')
                            ->label(__('admin.name_en'))
                            ->weight('bold'),

                        Infolists\Components\TextEntry::make('englishTranslation.slug')
                            ->label(__('admin.slug_en'))
                            ->copyable(),

                        Infolists\Components\TextEntry::make('englishTranslation.short_description')
                            ->label(__('admin.short_description_en'))
                            ->columnSpanFull()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('englishTranslation.description')
                            ->label(__('admin.description_en'))
                            ->html()
                            ->columnSpanFull()
                            ->placeholder('-'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Infolists\Components\Section::make(__('admin.seo_data'))
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('arabicTranslation.meta_title')
                                    ->label(__('admin.meta_title_ar'))
                                    ->placeholder('-'),

                                Infolists\Components\TextEntry::make('englishTranslation.meta_title')
                                    ->label(__('admin.meta_title_en'))
                                    ->placeholder('-'),

                                Infolists\Components\TextEntry::make('arabicTranslation.meta_description')
                                    ->label(__('admin.meta_description_ar'))
                                    ->placeholder('-')
                                    ->columnSpan(1),

                                Infolists\Components\TextEntry::make('englishTranslation.meta_description')
                                    ->label(__('admin.meta_description_en'))
                                    ->placeholder('-')
                                    ->columnSpan(1),

                                Infolists\Components\TextEntry::make('arabicTranslation.meta_keywords')
                                    ->label(__('admin.meta_keywords_ar'))
                                    ->badge()
                                    ->separator(',')
                                    ->placeholder('-'),

                                Infolists\Components\TextEntry::make('englishTranslation.meta_keywords')
                                    ->label(__('admin.meta_keywords_en'))
                                    ->badge()
                                    ->separator(',')
                                    ->placeholder('-'),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Infolists\Components\Section::make(__('admin.publishing_data'))
                    ->schema([
                        Infolists\Components\TextEntry::make('published_at')
                            ->label(__('admin.published_at'))
                            ->dateTime()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('created_at')
                            ->label(__('admin.created_at'))
                            ->dateTime(),

                        Infolists\Components\TextEntry::make('updated_at')
                            ->label(__('admin.updated_at'))
                            ->dateTime(),

                        Infolists\Components\TextEntry::make('deleted_at')
                            ->label(__('admin.deleted_at'))
                            ->dateTime()
                            ->placeholder('-'),
                    ])
                    ->columns(4)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'arabicTranslation',
                'englishTranslation',
                'category.arabicTranslation',
                'brand.arabicTranslation',
            ])
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ImagesRelationManager::class,
            ProductAttributesRelationManager::class,
            VariantsRelationManager::class,

        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'view' => Pages\ViewProduct::route('/{record}'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}