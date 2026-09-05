<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FooterLinkResource\Pages;
use App\Models\FooterLink;
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

class FooterLinkResource extends Resource
{
    protected static ?string $model = FooterLink::class;

    protected static ?string $navigationIcon = 'heroicon-o-link';

    protected static ?int $navigationSort = 5;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.storefront_settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.footer_links');
    }

    public static function getModelLabel(): string
    {
        return __('admin.footer_link');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.footer_links');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('admin.footer_link_information'))
                    ->schema([
                        Forms\Components\Select::make('group')
                            ->label(__('admin.group'))
                            ->options(fn () => FooterLink::groups())
                            ->searchable()
                            ->required()
                            ->default('main'),

                        Forms\Components\Select::make('link_type')
                            ->label(__('admin.link_type'))
                            ->options(fn () => FooterLink::linkTypes())
                            ->required()
                            ->default('custom')
                            ->live(),

                        Forms\Components\Select::make('page_id')
                            ->label(__('admin.page'))
                            ->options(function () {
                                return Page::query()
                                    ->where('is_active', true)
                                    ->orderBy('sort_order')
                                    ->get()
                                    ->mapWithKeys(fn ($page) => [
                                        $page->id => app()->getLocale() === 'ar'
                                            ? $page->title_ar
                                            : $page->title_en,
                                    ])
                                    ->toArray();
                            })
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->visible(fn (Forms\Get $get) => $get('link_type') === 'page')
                            ->required(fn (Forms\Get $get) => $get('link_type') === 'page'),

                        Forms\Components\TextInput::make('url')
                            ->label(__('admin.url'))
                            ->maxLength(255)
                            ->placeholder('/privacy-policy or https://example.com')
                            ->visible(fn (Forms\Get $get) => $get('link_type') === 'custom')
                            ->required(fn (Forms\Get $get) => $get('link_type') === 'custom'),

                        Forms\Components\TextInput::make('title_ar')
                            ->label(__('admin.title_ar'))
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('title_en')
                            ->label(__('admin.title_en'))
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Toggle::make('open_in_new_tab')
                            ->label(__('admin.open_in_new_tab'))
                            ->default(false),

                        Forms\Components\Toggle::make('is_active')
                            ->label(__('admin.active'))
                            ->default(true),

                        Forms\Components\TextInput::make('sort_order')
                            ->label(__('admin.sort_order'))
                            ->numeric()
                            ->default(0),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with('page')
                ->withoutGlobalScopes([
                    SoftDeletingScope::class,
                ]))
            ->columns([
                Tables\Columns\TextColumn::make('group')
                    ->label(__('admin.group'))
                    ->formatStateUsing(fn ($state) => FooterLink::groups()[$state] ?? $state)
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('link_type')
                    ->label(__('admin.link_type'))
                    ->formatStateUsing(fn ($state) => FooterLink::linkTypes()[$state] ?? $state)
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('page.title_ar')
                    ->label(__('admin.page'))
                    ->placeholder('-')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('title_ar')
                    ->label(__('admin.title_ar'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('title_en')
                    ->label(__('admin.title_en'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('resolved_url')
                    ->label(__('admin.url'))
                    ->copyable()
                    ->limit(40)
                    ->placeholder('-'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('admin.active'))
                    ->boolean(),

                Tables\Columns\IconColumn::make('open_in_new_tab')
                    ->label(__('admin.open_in_new_tab'))
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
                Tables\Filters\SelectFilter::make('group')
                    ->label(__('admin.group'))
                    ->options(fn () => FooterLink::groups()),

                Tables\Filters\SelectFilter::make('link_type')
                    ->label(__('admin.link_type'))
                    ->options(fn () => FooterLink::linkTypes()),

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
                Infolists\Components\Section::make(__('admin.footer_link_information'))
                    ->schema([
                        Infolists\Components\TextEntry::make('group')
                            ->label(__('admin.group'))
                            ->formatStateUsing(fn ($state) => FooterLink::groups()[$state] ?? $state)
                            ->badge(),

                        Infolists\Components\TextEntry::make('link_type')
                            ->label(__('admin.link_type'))
                            ->formatStateUsing(fn ($state) => FooterLink::linkTypes()[$state] ?? $state)
                            ->badge(),

                        Infolists\Components\TextEntry::make('page.title_ar')
                            ->label(__('admin.page'))
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('title_ar')
                            ->label(__('admin.title_ar')),

                        Infolists\Components\TextEntry::make('title_en')
                            ->label(__('admin.title_en')),

                        Infolists\Components\TextEntry::make('resolved_url')
                            ->label(__('admin.url'))
                            ->copyable()
                            ->placeholder('-'),

                        Infolists\Components\IconEntry::make('open_in_new_tab')
                            ->label(__('admin.open_in_new_tab'))
                            ->boolean(),

                        Infolists\Components\IconEntry::make('is_active')
                            ->label(__('admin.active'))
                            ->boolean(),

                        Infolists\Components\TextEntry::make('sort_order')
                            ->label(__('admin.sort_order')),
                    ])
                    ->columns(2),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFooterLinks::route('/'),
            'create' => Pages\CreateFooterLink::route('/create'),
            'view' => Pages\ViewFooterLink::route('/{record}'),
            'edit' => Pages\EditFooterLink::route('/{record}/edit'),
        ];
    }
}