<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HomepageSliderResource\Pages;
use App\Models\HomepageSlider;
use App\Rules\LinkUrl;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class HomepageSliderResource extends Resource
{
    protected static ?string $model = HomepageSlider::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.storefront_settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.homepage_sliders');
    }

    public static function getModelLabel(): string
    {
        return __('admin.homepage_slider');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.homepage_sliders');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Homepage Slider Tabs')
                    ->columnSpanFull()
                    ->tabs([
                        Forms\Components\Tabs\Tab::make(__('admin.main_information'))
                            ->schema([
                                Forms\Components\Section::make(__('admin.slider_images'))
                                    ->schema([
                                        Forms\Components\FileUpload::make('image')
                                            ->label(__('admin.image'))
                                            ->image()
                                            ->directory('homepage/sliders')
                                            ->disk('public')
                                            ->imageEditor()
                                            ->required()
                                            ->maxSize(4096),

                                        Forms\Components\FileUpload::make('mobile_image')
                                            ->label(__('admin.mobile_image'))
                                            ->image()
                                            ->directory('homepage/sliders/mobile')
                                            ->disk('public')
                                            ->imageEditor()
                                            ->maxSize(4096),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make(__('admin.status_and_order'))
                                    ->schema([
                                        Forms\Components\Toggle::make('is_active')
                                            ->label(__('admin.active'))
                                            ->default(true),

                                        Forms\Components\Toggle::make('open_in_new_tab')
                                            ->label(__('admin.open_in_new_tab'))
                                            ->default(false),

                                        Forms\Components\TextInput::make('sort_order')
                                            ->label(__('admin.sort_order'))
                                            ->numeric()
                                            ->default(0),
                                    ])
                                    ->columns(3),
                            ]),

                        Forms\Components\Tabs\Tab::make(__('admin.content'))
                            ->schema([
                                Forms\Components\Section::make(__('admin.arabic_content'))
                                    ->schema([
                                        Forms\Components\TextInput::make('title_ar')
                                            ->label(__('admin.title_ar'))
                                            ->maxLength(255),

                                        Forms\Components\Textarea::make('description_ar')
                                            ->label(__('admin.description_ar'))
                                            ->rows(3),

                                        Forms\Components\TextInput::make('button_text_ar')
                                            ->label(__('admin.button_text_ar'))
                                            ->maxLength(255),
                                    ])
                                    ->columns(1),

                                Forms\Components\Section::make(__('admin.english_content'))
                                    ->schema([
                                        Forms\Components\TextInput::make('title_en')
                                            ->label(__('admin.title_en'))
                                            ->maxLength(255),

                                        Forms\Components\Textarea::make('description_en')
                                            ->label(__('admin.description_en'))
                                            ->rows(3),

                                        Forms\Components\TextInput::make('button_text_en')
                                            ->label(__('admin.button_text_en'))
                                            ->maxLength(255),
                                    ])
                                    ->columns(1),

                                Forms\Components\Section::make(__('admin.button_settings'))
                                    ->schema([
                                        Forms\Components\TextInput::make('button_url')
                                            ->label(__('admin.button_url'))
                                            // Internal links such as /shop are valid here, which
                                            // Laravel's `url` rule rejects.
                                            ->rule(new LinkUrl())
                                            ->maxLength(255)
                                            ->placeholder('https://example.com or /products'),
                                    ]),
                            ]),

                        Forms\Components\Tabs\Tab::make(__('admin.schedule'))
                            ->schema([
                                Forms\Components\Section::make(__('admin.schedule'))
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
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]))
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label(__('admin.image'))
                    ->disk('public')
                    ->square(),

                Tables\Columns\TextColumn::make('title_ar')
                    ->label(__('admin.title_ar'))
                    ->searchable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('title_en')
                    ->label(__('admin.title_en'))
                    ->searchable()
                    ->placeholder('-'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('admin.active'))
                    ->boolean(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label(__('admin.sort_order'))
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
                Infolists\Components\Section::make(__('admin.slider_images'))
                    ->schema([
                        Infolists\Components\ImageEntry::make('image')
                            ->label(__('admin.image'))
                            ->disk('public'),

                        Infolists\Components\ImageEntry::make('mobile_image')
                            ->label(__('admin.mobile_image'))
                            ->disk('public')
                            ->placeholder('-'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make(__('admin.content'))
                    ->schema([
                        Infolists\Components\TextEntry::make('title_ar')
                            ->label(__('admin.title_ar'))
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('title_en')
                            ->label(__('admin.title_en'))
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('description_ar')
                            ->label(__('admin.description_ar'))
                            ->placeholder('-')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('description_en')
                            ->label(__('admin.description_en'))
                            ->placeholder('-')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('button_text_ar')
                            ->label(__('admin.button_text_ar'))
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('button_text_en')
                            ->label(__('admin.button_text_en'))
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('button_url')
                            ->label(__('admin.button_url'))
                            ->copyable()
                            ->placeholder('-'),

                        Infolists\Components\IconEntry::make('open_in_new_tab')
                            ->label(__('admin.open_in_new_tab'))
                            ->boolean(),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make(__('admin.status_and_order'))
                    ->schema([
                        Infolists\Components\IconEntry::make('is_active')
                            ->label(__('admin.active'))
                            ->boolean(),

                        Infolists\Components\TextEntry::make('sort_order')
                            ->label(__('admin.sort_order')),

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
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHomepageSliders::route('/'),
            'create' => Pages\CreateHomepageSlider::route('/create'),
            'view' => Pages\ViewHomepageSlider::route('/{record}'),
            'edit' => Pages\EditHomepageSlider::route('/{record}/edit'),
        ];
    }
}