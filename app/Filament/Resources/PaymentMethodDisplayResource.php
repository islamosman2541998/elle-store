<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentMethodDisplayResource\Pages;
use App\Models\PaymentMethodDisplay;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PaymentMethodDisplayResource extends Resource
{
    protected static ?string $model = PaymentMethodDisplay::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?int $navigationSort = 7;

     public static function getNavigationGroup(): ?string
    {
        return __('admin.storefront_settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.payment_method_displays');
    }

    public static function getModelLabel(): string
    {
        return __('admin.payment_method_display');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.payment_method_displays');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('admin.payment_method_display_information'))
                    ->schema([
                        Forms\Components\Select::make('key')
                            ->label(__('admin.key'))
                            ->options(PaymentMethodDisplay::defaultKeys())
                            ->searchable()
                            ->required()
                            ->unique(ignoreRecord: true),

                        Forms\Components\TextInput::make('name_ar')
                            ->label(__('admin.name_ar'))
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('name_en')
                            ->label(__('admin.name_en'))
                            ->required()
                            ->maxLength(255),

                        Forms\Components\FileUpload::make('image')
                            ->label(__('admin.image'))
                            ->image()
                            ->directory('settings/payment-methods')
                            ->disk('public')
                            ->imageEditor()
                            ->maxSize(2048),

                        Forms\Components\TextInput::make('icon')
                            ->label(__('admin.icon'))
                            ->maxLength(255)
                            ->helperText(__('admin.icon_helper')),

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
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]))
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label(__('admin.image'))
                    ->disk('public')
                    ->square(),

                Tables\Columns\TextColumn::make('key')
                    ->label(__('admin.key'))
                    ->badge()
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name_ar')
                    ->label(__('admin.name_ar'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name_en')
                    ->label(__('admin.name_en'))
                    ->searchable()
                    ->sortable(),

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
                Infolists\Components\Section::make(__('admin.payment_method_display_information'))
                    ->schema([
                        Infolists\Components\ImageEntry::make('image')
                            ->label(__('admin.image'))
                            ->disk('public')
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('key')
                            ->label(__('admin.key'))
                            ->badge(),

                        Infolists\Components\TextEntry::make('name_ar')
                            ->label(__('admin.name_ar')),

                        Infolists\Components\TextEntry::make('name_en')
                            ->label(__('admin.name_en')),

                        Infolists\Components\TextEntry::make('icon')
                            ->label(__('admin.icon'))
                            ->placeholder('-'),

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
            'index' => Pages\ListPaymentMethodDisplays::route('/'),
            'create' => Pages\CreatePaymentMethodDisplay::route('/create'),
            'view' => Pages\ViewPaymentMethodDisplay::route('/{record}'),
            'edit' => Pages\EditPaymentMethodDisplay::route('/{record}/edit'),
        ];
    }
}