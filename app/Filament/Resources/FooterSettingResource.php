<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FooterSettingResource\Pages;
use App\Models\FooterSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FooterSettingResource extends Resource
{
    protected static ?string $model = FooterSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-bars-3-bottom-left';

    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.storefront_settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.footer_settings');
    }

    public static function getModelLabel(): string
    {
        return __('admin.footer_setting');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.footer_settings');
    }

    public static function canCreate(): bool
    {
        return FooterSetting::query()->count() === 0;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Footer Settings Tabs')
                    ->columnSpanFull()
                    ->tabs([
                        Forms\Components\Tabs\Tab::make(__('admin.footer_general'))
                            ->schema([
                                Forms\Components\Section::make(__('admin.footer_general'))
                                    ->schema([
                                        Forms\Components\Toggle::make('is_active')
                                            ->label(__('admin.active'))
                                            ->default(true),

                                        Forms\Components\FileUpload::make('logo')
                                            ->label(__('admin.logo'))
                                            ->image()
                                            ->directory('settings/footer')
                                            ->disk('public')
                                            ->imageEditor()
                                            ->maxSize(2048),

                                        Forms\Components\TextInput::make('title_ar')
                                            ->label(__('admin.title_ar'))
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('title_en')
                                            ->label(__('admin.title_en'))
                                            ->maxLength(255),

                                        Forms\Components\Textarea::make('description_ar')
                                            ->label(__('admin.description_ar'))
                                            ->rows(3),

                                        Forms\Components\Textarea::make('description_en')
                                            ->label(__('admin.description_en'))
                                            ->rows(3),
                                    ])
                                    ->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make(__('admin.footer_display_options'))
                            ->schema([
                                Forms\Components\Section::make(__('admin.footer_display_options'))
                                    ->schema([
                                        Forms\Components\Toggle::make('show_social_links')
                                            ->label(__('admin.show_social_links'))
                                            ->default(true),

                                        Forms\Components\Toggle::make('show_payment_methods')
                                            ->label(__('admin.show_payment_methods'))
                                            ->default(true),

                                        Forms\Components\Toggle::make('show_newsletter')
                                            ->label(__('admin.show_newsletter'))
                                            ->default(false)
                                            ->live(),
                                    ])
                                    ->columns(3),

                                Forms\Components\Section::make(__('admin.newsletter_settings'))
                                    ->schema([
                                        Forms\Components\TextInput::make('newsletter_title_ar')
                                            ->label(__('admin.newsletter_title_ar'))
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('newsletter_title_en')
                                            ->label(__('admin.newsletter_title_en'))
                                            ->maxLength(255),

                                        Forms\Components\Textarea::make('newsletter_description_ar')
                                            ->label(__('admin.newsletter_description_ar'))
                                            ->rows(3),

                                        Forms\Components\Textarea::make('newsletter_description_en')
                                            ->label(__('admin.newsletter_description_en'))
                                            ->rows(3),
                                    ])
                                    ->columns(2)
                                    ->visible(fn (Forms\Get $get) => (bool) $get('show_newsletter')),
                            ]),

                        Forms\Components\Tabs\Tab::make(__('admin.copyright_settings'))
                            ->schema([
                                Forms\Components\Section::make(__('admin.copyright_settings'))
                                    ->schema([
                                        Forms\Components\TextInput::make('copyright_ar')
                                            ->label(__('admin.copyright_ar'))
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('copyright_en')
                                            ->label(__('admin.copyright_en'))
                                            ->maxLength(255),
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
                Tables\Columns\ImageColumn::make('logo')
                    ->label(__('admin.logo'))
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

                Tables\Columns\IconColumn::make('show_social_links')
                    ->label(__('admin.show_social_links'))
                    ->boolean(),

                Tables\Columns\IconColumn::make('show_payment_methods')
                    ->label(__('admin.show_payment_methods'))
                    ->boolean(),

                Tables\Columns\IconColumn::make('show_newsletter')
                    ->label(__('admin.show_newsletter'))
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
                Infolists\Components\Section::make(__('admin.footer_general'))
                    ->schema([
                        Infolists\Components\ImageEntry::make('logo')
                            ->label(__('admin.logo'))
                            ->disk('public')
                            ->placeholder('-'),

                        Infolists\Components\IconEntry::make('is_active')
                            ->label(__('admin.active'))
                            ->boolean(),

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
                    ])
                    ->columns(2),

                Infolists\Components\Section::make(__('admin.footer_display_options'))
                    ->schema([
                        Infolists\Components\IconEntry::make('show_social_links')
                            ->label(__('admin.show_social_links'))
                            ->boolean(),

                        Infolists\Components\IconEntry::make('show_payment_methods')
                            ->label(__('admin.show_payment_methods'))
                            ->boolean(),

                        Infolists\Components\IconEntry::make('show_newsletter')
                            ->label(__('admin.show_newsletter'))
                            ->boolean(),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make(__('admin.newsletter_settings'))
                    ->schema([
                        Infolists\Components\TextEntry::make('newsletter_title_ar')
                            ->label(__('admin.newsletter_title_ar'))
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('newsletter_title_en')
                            ->label(__('admin.newsletter_title_en'))
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('newsletter_description_ar')
                            ->label(__('admin.newsletter_description_ar'))
                            ->placeholder('-')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('newsletter_description_en')
                            ->label(__('admin.newsletter_description_en'))
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),

                Infolists\Components\Section::make(__('admin.copyright_settings'))
                    ->schema([
                        Infolists\Components\TextEntry::make('copyright_ar')
                            ->label(__('admin.copyright_ar'))
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('copyright_en')
                            ->label(__('admin.copyright_en'))
                            ->placeholder('-'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFooterSettings::route('/'),
            'view' => Pages\ViewFooterSetting::route('/{record}'),
            'edit' => Pages\EditFooterSetting::route('/{record}/edit'),
        ];
    }
}