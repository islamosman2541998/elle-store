<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HomepageSettingResource\Pages;
use App\Models\HomepageSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class HomepageSettingResource extends Resource
{
    protected static ?string $model = HomepageSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.storefront_settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.homepage_settings');
    }

    public static function getModelLabel(): string
    {
        return __('admin.homepage_setting');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.homepage_settings');
    }

    public static function canCreate(): bool
    {
        return HomepageSetting::query()->count() === 0;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Homepage Settings Tabs')
                    ->columnSpanFull()
                    ->tabs([
                        Forms\Components\Tabs\Tab::make(__('admin.homepage_general'))
                            ->schema([
                                Forms\Components\Section::make(__('admin.homepage_general'))
                                    ->schema([
                                        Forms\Components\Toggle::make('is_active')
                                            ->label(__('admin.active'))
                                            ->default(true),

                                        Forms\Components\Toggle::make('slider_enabled')
                                            ->label(__('admin.homepage_slider_enabled'))
                                            ->default(true),
                                    ])
                                    ->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make(__('admin.featured_categories'))
                            ->schema([
                                Forms\Components\Section::make(__('admin.featured_categories'))
                                    ->schema([
                                        Forms\Components\Toggle::make('featured_categories_enabled')
                                            ->label(__('admin.enabled'))
                                            ->default(true)
                                            ->live(),

                                        Forms\Components\TextInput::make('featured_categories_limit')
                                            ->label(__('admin.items_limit'))
                                            ->numeric()
                                            ->minValue(1)
                                            ->default(8)
                                            ->visible(fn(Forms\Get $get) => (bool) $get('featured_categories_enabled')),

                                        Forms\Components\TextInput::make('featured_categories_title_ar')
                                            ->label(__('admin.title_ar'))
                                            ->maxLength(255)
                                            ->visible(fn(Forms\Get $get) => (bool) $get('featured_categories_enabled')),

                                        Forms\Components\TextInput::make('featured_categories_title_en')
                                            ->label(__('admin.title_en'))
                                            ->maxLength(255)
                                            ->visible(fn(Forms\Get $get) => (bool) $get('featured_categories_enabled')),
                                    ])
                                    ->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make(__('admin.featured_products'))
                            ->schema([
                                Forms\Components\Section::make(__('admin.featured_products'))
                                    ->schema([
                                        Forms\Components\Toggle::make('featured_products_enabled')
                                            ->label(__('admin.enabled'))
                                            ->default(true)
                                            ->live(),

                                        Forms\Components\TextInput::make('featured_products_limit')
                                            ->label(__('admin.items_limit'))
                                            ->numeric()
                                            ->minValue(1)
                                            ->default(8)
                                            ->visible(fn(Forms\Get $get) => (bool) $get('featured_products_enabled')),

                                        Forms\Components\TextInput::make('featured_products_title_ar')
                                            ->label(__('admin.title_ar'))
                                            ->maxLength(255)
                                            ->visible(fn(Forms\Get $get) => (bool) $get('featured_products_enabled')),

                                        Forms\Components\TextInput::make('featured_products_title_en')
                                            ->label(__('admin.title_en'))
                                            ->maxLength(255)
                                            ->visible(fn(Forms\Get $get) => (bool) $get('featured_products_enabled')),
                                    ])
                                    ->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make(__('admin.new_products'))
                            ->schema([
                                Forms\Components\Section::make(__('admin.new_products'))
                                    ->schema([
                                        Forms\Components\Toggle::make('new_products_enabled')
                                            ->label(__('admin.enabled'))
                                            ->default(true)
                                            ->live(),

                                        Forms\Components\TextInput::make('new_products_limit')
                                            ->label(__('admin.items_limit'))
                                            ->numeric()
                                            ->minValue(1)
                                            ->default(8)
                                            ->visible(fn(Forms\Get $get) => (bool) $get('new_products_enabled')),

                                        Forms\Components\TextInput::make('new_products_title_ar')
                                            ->label(__('admin.title_ar'))
                                            ->maxLength(255)
                                            ->visible(fn(Forms\Get $get) => (bool) $get('new_products_enabled')),

                                        Forms\Components\TextInput::make('new_products_title_en')
                                            ->label(__('admin.title_en'))
                                            ->maxLength(255)
                                            ->visible(fn(Forms\Get $get) => (bool) $get('new_products_enabled')),
                                    ])
                                    ->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make(__('admin.flash_sales'))
                            ->schema([
                                Forms\Components\Section::make(__('admin.flash_sales'))
                                    ->schema([
                                        Forms\Components\Toggle::make('flash_sales_enabled')
                                            ->label(__('admin.enabled'))
                                            ->default(true)
                                            ->live(),

                                        Forms\Components\TextInput::make('flash_sales_limit')
                                            ->label(__('admin.items_limit'))
                                            ->numeric()
                                            ->minValue(1)
                                            ->default(8)
                                            ->visible(fn(Forms\Get $get) => (bool) $get('flash_sales_enabled')),

                                        Forms\Components\TextInput::make('flash_sales_title_ar')
                                            ->label(__('admin.title_ar'))
                                            ->maxLength(255)
                                            ->visible(fn(Forms\Get $get) => (bool) $get('flash_sales_enabled')),

                                        Forms\Components\TextInput::make('flash_sales_title_en')
                                            ->label(__('admin.title_en'))
                                            ->maxLength(255)
                                            ->visible(fn(Forms\Get $get) => (bool) $get('flash_sales_enabled')),
                                    ])
                                    ->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make(__('admin.brands'))
                            ->schema([
                                Forms\Components\Section::make(__('admin.brands'))
                                    ->schema([
                                        Forms\Components\Toggle::make('brands_enabled')
                                            ->label(__('admin.enabled'))
                                            ->default(true)
                                            ->live(),

                                        Forms\Components\TextInput::make('brands_limit')
                                            ->label(__('admin.items_limit'))
                                            ->numeric()
                                            ->minValue(1)
                                            ->default(10)
                                            ->visible(fn(Forms\Get $get) => (bool) $get('brands_enabled')),

                                        Forms\Components\TextInput::make('brands_title_ar')
                                            ->label(__('admin.title_ar'))
                                            ->maxLength(255)
                                            ->visible(fn(Forms\Get $get) => (bool) $get('brands_enabled')),

                                        Forms\Components\TextInput::make('brands_title_en')
                                            ->label(__('admin.title_en'))
                                            ->maxLength(255)
                                            ->visible(fn(Forms\Get $get) => (bool) $get('brands_enabled')),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make(__('admin.best_sellers'))
                                    ->description(__('admin.best_sellers_hint'))
                                    ->schema([
                                        Forms\Components\Toggle::make('best_sellers_enabled')
                                            ->label(__('admin.enabled'))
                                            ->default(true)
                                            ->live(),

                                        Forms\Components\TextInput::make('best_sellers_limit')
                                            ->label(__('admin.items_limit'))
                                            ->numeric()
                                            ->minValue(1)
                                            ->default(8)
                                            ->visible(fn(Forms\Get $get) => (bool) $get('best_sellers_enabled')),

                                        Forms\Components\TextInput::make('best_sellers_title_ar')
                                            ->label(__('admin.title_ar'))
                                            ->maxLength(255)
                                            ->visible(fn(Forms\Get $get) => (bool) $get('best_sellers_enabled')),

                                        Forms\Components\TextInput::make('best_sellers_title_en')
                                            ->label(__('admin.title_en'))
                                            ->maxLength(255)
                                            ->visible(fn(Forms\Get $get) => (bool) $get('best_sellers_enabled')),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make(__('admin.extra_sections'))
                                    ->schema([
                                        Forms\Components\Toggle::make('features_strip_enabled')
                                            ->label(__('admin.features_strip'))
                                            ->helperText(__('admin.features_strip_hint'))
                                            ->default(true),

                                        Forms\Components\Toggle::make('category_banner_enabled')
                                            ->label(__('admin.category_banner'))
                                            ->helperText(__('admin.category_banner_hint'))
                                            ->default(true),

                                        Forms\Components\Toggle::make('social_strip_enabled')
                                            ->label(__('admin.social_strip'))
                                            ->helperText(__('admin.social_strip_hint'))
                                            ->default(true),
                                    ])
                                    ->columns(3),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('admin.active'))
                    ->boolean(),

                Tables\Columns\IconColumn::make('slider_enabled')
                    ->label(__('admin.homepage_slider_enabled'))
                    ->boolean(),

                Tables\Columns\IconColumn::make('featured_categories_enabled')
                    ->label(__('admin.featured_categories'))
                    ->boolean(),

                Tables\Columns\IconColumn::make('featured_products_enabled')
                    ->label(__('admin.featured_products'))
                    ->boolean(),

                Tables\Columns\IconColumn::make('new_products_enabled')
                    ->label(__('admin.new_products'))
                    ->boolean(),

                Tables\Columns\IconColumn::make('flash_sales_enabled')
                    ->label(__('admin.flash_sales'))
                    ->boolean(),

                Tables\Columns\IconColumn::make('brands_enabled')
                    ->label(__('admin.brands'))
                    ->boolean(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('admin.updated_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label(__('admin.view')),

                Tables\Actions\EditAction::make()
                    ->label(__('admin.edit')),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make(__('admin.homepage_general'))
                    ->schema([
                        Infolists\Components\IconEntry::make('is_active')
                            ->label(__('admin.active'))
                            ->boolean(),

                        Infolists\Components\IconEntry::make('slider_enabled')
                            ->label(__('admin.homepage_slider_enabled'))
                            ->boolean(),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make(__('admin.homepage_sections'))
                    ->schema([
                        Infolists\Components\IconEntry::make('featured_categories_enabled')
                            ->label(__('admin.featured_categories'))
                            ->boolean(),

                        Infolists\Components\TextEntry::make('featured_categories_title_ar')
                            ->label(__('admin.title_ar'))
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('featured_categories_title_en')
                            ->label(__('admin.title_en'))
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('featured_categories_limit')
                            ->label(__('admin.items_limit')),

                        Infolists\Components\IconEntry::make('featured_products_enabled')
                            ->label(__('admin.featured_products'))
                            ->boolean(),

                        Infolists\Components\TextEntry::make('featured_products_title_ar')
                            ->label(__('admin.title_ar'))
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('featured_products_title_en')
                            ->label(__('admin.title_en'))
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('featured_products_limit')
                            ->label(__('admin.items_limit')),

                        Infolists\Components\IconEntry::make('new_products_enabled')
                            ->label(__('admin.new_products'))
                            ->boolean(),

                        Infolists\Components\TextEntry::make('new_products_title_ar')
                            ->label(__('admin.title_ar'))
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('new_products_title_en')
                            ->label(__('admin.title_en'))
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('new_products_limit')
                            ->label(__('admin.items_limit')),

                        Infolists\Components\IconEntry::make('flash_sales_enabled')
                            ->label(__('admin.flash_sales'))
                            ->boolean(),

                        Infolists\Components\TextEntry::make('flash_sales_title_ar')
                            ->label(__('admin.title_ar'))
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('flash_sales_title_en')
                            ->label(__('admin.title_en'))
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('flash_sales_limit')
                            ->label(__('admin.items_limit')),

                        Infolists\Components\IconEntry::make('brands_enabled')
                            ->label(__('admin.brands'))
                            ->boolean(),

                        Infolists\Components\TextEntry::make('brands_title_ar')
                            ->label(__('admin.title_ar'))
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('brands_title_en')
                            ->label(__('admin.title_en'))
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('brands_limit')
                            ->label(__('admin.items_limit')),
                    ])
                    ->columns(4)
                    ->collapsible(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHomepageSettings::route('/'),
            'view' => Pages\ViewHomepageSetting::route('/{record}'),
            'edit' => Pages\EditHomepageSetting::route('/{record}/edit'),
        ];
    }
}