<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

use Illuminate\Validation\Rule;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.catalog');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.categories');
    }

    public static function getModelLabel(): string
    {
        return __('admin.category');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.categories');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Category Tabs')
                    ->columnSpanFull()
                    ->tabs([
                        Forms\Components\Tabs\Tab::make(__('admin.general_data'))
                            ->schema([
                                Forms\Components\Section::make(__('admin.basic_information'))
                                    ->schema([
                                        Forms\Components\Select::make('parent_id')
                                            ->label(__('admin.parent_category'))
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
                                            ->nullable(),

                                        Forms\Components\FileUpload::make('image')
                                            ->label(__('admin.category_image'))
                                            ->image()
                                            ->directory('categories')
                                            ->disk('public')
                                            ->imageEditor()
                                            ->columnSpanFull(),

                                        Forms\Components\Toggle::make('is_active')
                                            ->label(__('admin.active'))
                                            ->default(true),

                                        Forms\Components\Toggle::make('is_featured')
                                            ->label(__('admin.featured'))
                                            ->default(false),

                                        Forms\Components\TextInput::make('sort_order')
                                            ->label(__('admin.sort_order'))
                                            ->numeric()
                                            ->default(0),
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
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('ar_slug')
                                            ->label(__('admin.slug_ar'))
                                            ->required()
                                            ->maxLength(255)
                                            ->rules(fn(?Category $record) => [
                                                Rule::unique('category_translations', 'slug')
                                                    ->where('locale', 'ar')
                                                    ->ignore($record?->arabicTranslation?->id),
                                            ]),

                                        Forms\Components\Textarea::make('ar_description')
                                            ->label(__('admin.description_ar'))
                                            ->rows(4)
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
                                    ])
                                    ->columns(1),
                            ]),

                        Forms\Components\Tabs\Tab::make(__('admin.english_content'))
                            ->schema([
                                Forms\Components\Section::make(__('admin.english_basic_content'))
                                    ->schema([
                                        Forms\Components\TextInput::make('en_name')
                                            ->label(__('admin.name_en'))
                                            ->required()
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('en_slug')
                                            ->label(__('admin.slug_en'))
                                            ->required()
                                            ->maxLength(255)
                                            ->rules(fn(?Category $record) => [
                                                Rule::unique('category_translations', 'slug')
                                                    ->where('locale', 'en')
                                                    ->ignore($record?->englishTranslation?->id),
                                            ]),

                                        Forms\Components\Textarea::make('en_description')
                                            ->label(__('admin.description_en'))
                                            ->rows(4)
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
                                    ])
                                    ->columns(1),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label(__('admin.image'))
                    ->disk('public')
                    ->circular(),

                Tables\Columns\TextColumn::make('arabicTranslation.name')
                    ->label(__('admin.name_ar'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('englishTranslation.name')
                    ->label(__('admin.name_en'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('parent.arabicTranslation.name')
                    ->label(__('admin.parent_category'))
                    ->placeholder('-'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('admin.active'))
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_featured')
                    ->label(__('admin.featured'))
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

                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label(__('admin.featured')),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'arabicTranslation',
                'englishTranslation',
                'parent.arabicTranslation',
            ])
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}