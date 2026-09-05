<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CartResource\Pages;
use App\Models\Cart;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use App\Filament\Resources\CartResource\RelationManagers\ItemsRelationManager;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CartResource extends Resource
{
    protected static ?string $model = Cart::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.customers_management');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.carts');
    }

    public static function getModelLabel(): string
    {
        return __('admin.cart');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.carts');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('admin.cart_information'))
                    ->schema([
                        Forms\Components\Select::make('customer_id')
                            ->label(__('admin.customer'))
                            ->options(fn () => Customer::query()
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->toArray())
                            ->searchable()
                            ->preload()
                            ->nullable(),

                        Forms\Components\TextInput::make('session_id')
                            ->label(__('admin.session_id'))
                            ->maxLength(255),

                        Forms\Components\Select::make('status')
                            ->label(__('admin.status'))
                            ->options([
                                'active' => __('admin.active_cart'),
                                'converted' => __('admin.converted_cart'),
                                'abandoned' => __('admin.abandoned_cart'),
                                'expired' => __('admin.expired_cart'),
                            ])
                            ->required()
                            ->default('active'),

                        Forms\Components\DateTimePicker::make('last_activity_at')
                            ->label(__('admin.last_activity_at'))
                            ->seconds(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make(__('admin.cart_totals'))
                    ->schema([
                        Forms\Components\TextInput::make('subtotal')
                            ->label(__('admin.subtotal'))
                            ->numeric()
                            ->default(0)
                            ->prefix('EGP'),

                        Forms\Components\TextInput::make('discount_total')
                            ->label(__('admin.discount_total'))
                            ->numeric()
                            ->default(0)
                            ->prefix('EGP'),

                        Forms\Components\TextInput::make('shipping_total')
                            ->label(__('admin.shipping_total'))
                            ->numeric()
                            ->default(0)
                            ->prefix('EGP'),

                        Forms\Components\TextInput::make('tax_total')
                            ->label(__('admin.tax_total'))
                            ->numeric()
                            ->default(0)
                            ->prefix('EGP'),

                        Forms\Components\TextInput::make('grand_total')
                            ->label(__('admin.grand_total'))
                            ->numeric()
                            ->default(0)
                            ->prefix('EGP'),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('customer')->withCount('items'))
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label(__('admin.customer'))
                    ->searchable()
                    ->placeholder(__('admin.guest')),

                Tables\Columns\TextColumn::make('customer.phone')
                    ->label(__('admin.phone'))
                    ->searchable()
                    ->copyable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('admin.status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => __('admin.' . $state . '_cart'))
                    ->color(fn ($state) => match ($state) {
                        'active' => 'success',
                        'converted' => 'info',
                        'abandoned' => 'warning',
                        'expired' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('items_count')
                    ->label(__('admin.items_count'))
                    ->badge(),

                Tables\Columns\TextColumn::make('grand_total')
                    ->label(__('admin.grand_total'))
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2, '.', ',') . ' EGP')
                    ->badge()
                    ->color('success')
                    ->sortable(),

                Tables\Columns\TextColumn::make('last_activity_at')
                    ->label(__('admin.last_activity_at'))
                    ->dateTime()
                    ->placeholder('-')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('admin.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('admin.status'))
                    ->options([
                        'active' => __('admin.active_cart'),
                        'converted' => __('admin.converted_cart'),
                        'abandoned' => __('admin.abandoned_cart'),
                        'expired' => __('admin.expired_cart'),
                    ]),

                Tables\Filters\SelectFilter::make('customer_id')
                    ->label(__('admin.customer'))
                    ->options(fn () => Customer::query()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('last_activity_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make(__('admin.cart_information'))
                    ->schema([
                        Infolists\Components\TextEntry::make('id')
                            ->label('#'),

                        Infolists\Components\TextEntry::make('customer.name')
                            ->label(__('admin.customer'))
                            ->placeholder(__('admin.guest')),

                        Infolists\Components\TextEntry::make('customer.phone')
                            ->label(__('admin.phone'))
                            ->copyable()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('session_id')
                            ->label(__('admin.session_id'))
                            ->copyable()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('status')
                            ->label(__('admin.status'))
                            ->badge()
                            ->formatStateUsing(fn ($state) => __('admin.' . $state . '_cart')),

                        Infolists\Components\TextEntry::make('last_activity_at')
                            ->label(__('admin.last_activity_at'))
                            ->dateTime()
                            ->placeholder('-'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make(__('admin.cart_totals'))
                    ->schema([
                        Infolists\Components\TextEntry::make('subtotal')
                            ->label(__('admin.subtotal'))
                            ->formatStateUsing(fn ($state) => number_format((float) $state, 2, '.', ',') . ' EGP'),

                        Infolists\Components\TextEntry::make('discount_total')
                            ->label(__('admin.discount_total'))
                            ->formatStateUsing(fn ($state) => number_format((float) $state, 2, '.', ',') . ' EGP'),

                        Infolists\Components\TextEntry::make('shipping_total')
                            ->label(__('admin.shipping_total'))
                            ->formatStateUsing(fn ($state) => number_format((float) $state, 2, '.', ',') . ' EGP'),

                        Infolists\Components\TextEntry::make('tax_total')
                            ->label(__('admin.tax_total'))
                            ->formatStateUsing(fn ($state) => number_format((float) $state, 2, '.', ',') . ' EGP'),

                        Infolists\Components\TextEntry::make('grand_total')
                            ->label(__('admin.grand_total'))
                            ->formatStateUsing(fn ($state) => number_format((float) $state, 2, '.', ',') . ' EGP')
                            ->badge()
                            ->color('success'),
                    ])
                    ->columns(3),
            ]);
    }
public static function getRelations(): array
{
    return [
        ItemsRelationManager::class,
    ];
}
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCarts::route('/'),
            'create' => Pages\CreateCart::route('/create'),
            'view' => Pages\ViewCart::route('/{record}'),
            'edit' => Pages\EditCart::route('/{record}/edit'),
        ];
    }
}