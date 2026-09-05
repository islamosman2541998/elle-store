<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PageResource\Pages;
use App\Filament\Resources\PageResource\RelationManagers\ImagesRelationManager;
use App\Models\Page;
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

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 2;

   public static function getNavigationGroup(): ?string
{
    return __('admin.content_management');
}

    public static function getNavigationLabel(): string
    {
        return __('admin.pages');
    }

    public static function getModelLabel(): string
    {
        return __('admin.page');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.pages');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Page Tabs')
                    ->columnSpanFull()
                    ->tabs([
                        Forms\Components\Tabs\Tab::make(__('admin.main_information'))
                            ->schema([
                                Forms\Components\Section::make(__('admin.main_information'))
                                    ->schema([
                                        Forms\Components\TextInput::make('title_ar')
                                            ->label(__('admin.title_ar'))
                                            ->required()
                                            ->maxLength(255)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                                if ($state) {
                                                    $set('slug_ar', Str::slug($state));
                                                }
                                            }),

                                        Forms\Components\TextInput::make('title_en')
                                            ->label(__('admin.title_en'))
                                            ->required()
                                            ->maxLength(255)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                                if ($state) {
                                                    $set('slug_en', Str::slug($state));
                                                }
                                            }),

                                        Forms\Components\TextInput::make('slug_ar')
                                            ->label(__('admin.slug_ar'))
                                            ->required()
                                            ->unique(ignoreRecord: true)
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('slug_en')
                                            ->label(__('admin.slug_en'))
                                            ->required()
                                            ->unique(ignoreRecord: true)
                                            ->maxLength(255),

                                        Forms\Components\FileUpload::make('main_image')
                                            ->label(__('admin.main_image'))
                                            ->image()
                                            ->directory('pages')
                                            ->disk('public')
                                            ->imageEditor()
                                            ->maxSize(4096)
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
                            ]),

                        Forms\Components\Tabs\Tab::make(__('admin.content'))
                            ->schema([
                                Forms\Components\Section::make(__('admin.arabic_content'))
                                    ->schema([
                                        Forms\Components\Textarea::make('short_description_ar')
                                            ->label(__('admin.short_description_ar'))
                                            ->rows(3)
                                            ->columnSpanFull(),

                                        Forms\Components\RichEditor::make('content_ar')
                                            ->label(__('admin.content_ar'))
                                            ->columnSpanFull(),
                                    ]),

                                Forms\Components\Section::make(__('admin.english_content'))
                                    ->schema([
                                        Forms\Components\Textarea::make('short_description_en')
                                            ->label(__('admin.short_description_en'))
                                            ->rows(3)
                                            ->columnSpanFull(),

                                        Forms\Components\RichEditor::make('content_en')
                                            ->label(__('admin.content_en'))
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Forms\Components\Tabs\Tab::make(__('admin.seo_settings'))
                            ->schema([
                                Forms\Components\Section::make(__('admin.seo_settings'))
                                    ->schema([
                                        Forms\Components\TextInput::make('meta_title_ar')
                                            ->label(__('admin.meta_title_ar'))
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('meta_title_en')
                                            ->label(__('admin.meta_title_en'))
                                            ->maxLength(255),

                                        Forms\Components\Textarea::make('meta_description_ar')
                                            ->label(__('admin.meta_description_ar'))
                                            ->rows(3),

                                        Forms\Components\Textarea::make('meta_description_en')
                                            ->label(__('admin.meta_description_en'))
                                            ->rows(3),
                                    ])
                                    ->columns(2),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]))
            ->columns([
                Tables\Columns\ImageColumn::make('main_image')
                    ->label(__('admin.image'))
                    ->disk('public')
                    ->square(),

                Tables\Columns\TextColumn::make('title_ar')
                    ->label(__('admin.title_ar'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('title_en')
                    ->label(__('admin.title_en'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('slug_ar')
                    ->label(__('admin.slug_ar'))
                    ->copyable()
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('slug_en')
                    ->label(__('admin.slug_en'))
                    ->copyable()
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('admin.active'))
                    ->boolean(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label(__('admin.sort_order'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('admin.updated_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('admin.active')),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label(__('admin.view')),

                Tables\Actions\EditAction::make()
                    ->label(__('admin.edit')),

                Tables\Actions\DeleteAction::make()
                    ->label(__('admin.delete')),

                Tables\Actions\RestoreAction::make()
                    ->label(__('admin.restore')),

                Tables\Actions\ForceDeleteAction::make()
                    ->label(__('admin.force_delete')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label(__('admin.delete_selected')),

                    Tables\Actions\RestoreBulkAction::make()
                        ->label(__('admin.restore_selected')),

                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->label(__('admin.force_delete_selected')),
                ]),
            ])
            ->defaultSort('sort_order');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make(__('admin.main_information'))
                    ->schema([
                        Infolists\Components\ImageEntry::make('main_image')
                            ->label(__('admin.main_image'))
                            ->disk('public')
                            ->placeholder('-'),

                        Infolists\Components\IconEntry::make('is_active')
                            ->label(__('admin.active'))
                            ->boolean(),

                        Infolists\Components\TextEntry::make('title_ar')
                            ->label(__('admin.title_ar')),

                        Infolists\Components\TextEntry::make('title_en')
                            ->label(__('admin.title_en')),

                        Infolists\Components\TextEntry::make('slug_ar')
                            ->label(__('admin.slug_ar'))
                            ->copyable(),

                        Infolists\Components\TextEntry::make('slug_en')
                            ->label(__('admin.slug_en'))
                            ->copyable(),

                        Infolists\Components\TextEntry::make('sort_order')
                            ->label(__('admin.sort_order')),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make(__('admin.content'))
                    ->schema([
                        Infolists\Components\TextEntry::make('short_description_ar')
                            ->label(__('admin.short_description_ar'))
                            ->placeholder('-')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('short_description_en')
                            ->label(__('admin.short_description_en'))
                            ->placeholder('-')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('content_ar')
                            ->label(__('admin.content_ar'))
                            ->html()
                            ->placeholder('-')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('content_en')
                            ->label(__('admin.content_en'))
                            ->html()
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Infolists\Components\Section::make(__('admin.seo_settings'))
                    ->schema([
                        Infolists\Components\TextEntry::make('meta_title_ar')
                            ->label(__('admin.meta_title_ar'))
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('meta_title_en')
                            ->label(__('admin.meta_title_en'))
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('meta_description_ar')
                            ->label(__('admin.meta_description_ar'))
                            ->placeholder('-')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('meta_description_en')
                            ->label(__('admin.meta_description_en'))
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ImagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPages::route('/'),
            'create' => Pages\CreatePage::route('/create'),
            'view' => Pages\ViewPage::route('/{record}'),
            'edit' => Pages\EditPage::route('/{record}/edit'),
        ];
    }
}