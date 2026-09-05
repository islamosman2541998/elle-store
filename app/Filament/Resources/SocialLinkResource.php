<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SocialLinkResource\Pages;
use App\Models\SocialLink;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SocialLinkResource extends Resource
{
    protected static ?string $model = SocialLink::class;

    protected static ?string $navigationIcon = 'heroicon-o-share';

    protected static ?int $navigationSort = 6;

     public static function getNavigationGroup(): ?string
    {
        return __('admin.storefront_settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.social_links');
    }

    public static function getModelLabel(): string
    {
        return __('admin.social_link');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.social_links');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('admin.social_link_information'))
                    ->schema([
                        Forms\Components\Select::make('platform')
                            ->label(__('admin.platform'))
                            ->options(SocialLink::platforms())
                            ->searchable()
                            ->required(),

                        Forms\Components\TextInput::make('url')
                            ->label(__('admin.url'))
                            ->url()
                            ->maxLength(255)
                            ->placeholder('https://facebook.com/your-page'),

                        Forms\Components\TextInput::make('label_ar')
                            ->label(__('admin.label_ar'))
                            ->maxLength(255),

                        Forms\Components\TextInput::make('label_en')
                            ->label(__('admin.label_en'))
                            ->maxLength(255),

                        Forms\Components\TextInput::make('icon')
                            ->label(__('admin.icon'))
                            ->maxLength(255)
                            ->helperText(__('admin.icon_helper')),

                        Forms\Components\Toggle::make('is_active')
                            ->label(__('admin.active'))
                            ->default(true),

                        Forms\Components\Toggle::make('open_in_new_tab')
                            ->label(__('admin.open_in_new_tab'))
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
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]))
            ->columns([
                Tables\Columns\TextColumn::make('platform')
                    ->label(__('admin.platform'))
                    ->badge()
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('label_ar')
                    ->label(__('admin.label_ar'))
                    ->searchable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('label_en')
                    ->label(__('admin.label_en'))
                    ->searchable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('url')
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
                Infolists\Components\Section::make(__('admin.social_link_information'))
                    ->schema([
                        Infolists\Components\TextEntry::make('platform')
                            ->label(__('admin.platform'))
                            ->badge(),

                        Infolists\Components\TextEntry::make('url')
                            ->label(__('admin.url'))
                            ->copyable()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('label_ar')
                            ->label(__('admin.label_ar'))
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('label_en')
                            ->label(__('admin.label_en'))
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('icon')
                            ->label(__('admin.icon'))
                            ->placeholder('-'),

                        Infolists\Components\IconEntry::make('is_active')
                            ->label(__('admin.active'))
                            ->boolean(),

                        Infolists\Components\IconEntry::make('open_in_new_tab')
                            ->label(__('admin.open_in_new_tab'))
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
            'index' => Pages\ListSocialLinks::route('/'),
            'create' => Pages\CreateSocialLink::route('/create'),
            'view' => Pages\ViewSocialLink::route('/{record}'),
            'edit' => Pages\EditSocialLink::route('/{record}/edit'),
        ];
    }
}