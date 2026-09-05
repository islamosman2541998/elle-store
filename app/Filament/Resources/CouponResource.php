<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CouponResource\Pages;
use App\Models\Coupon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.promotions');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.coupons');
    }

    public static function getModelLabel(): string
    {
        return __('admin.coupon');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.coupons');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Coupon Tabs')
                    ->columnSpanFull()
                    ->tabs([
                        Forms\Components\Tabs\Tab::make(__('admin.coupon_information'))
                            ->schema([
                                Forms\Components\Section::make(__('admin.basic_information'))
                                    ->schema([
                                        Forms\Components\TextInput::make('code')
                                            ->label(__('admin.coupon_code'))
                                            ->required()
                                            ->unique(ignoreRecord: true)
                                            ->maxLength(255)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                                if ($state) {
                                                    $set('code', strtoupper(trim($state)));
                                                }
                                            }),

                                        Forms\Components\TextInput::make('name_ar')
                                            ->label(__('admin.name_ar'))
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('name_en')
                                            ->label(__('admin.name_en'))
                                            ->maxLength(255),

                                        Forms\Components\Toggle::make('is_active')
                                            ->label(__('admin.active'))
                                            ->default(true),

                                        Forms\Components\Textarea::make('notes')
                                            ->label(__('admin.notes'))
                                            ->rows(3)
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make(__('admin.discount_settings'))
                            ->schema([
                                Forms\Components\Section::make(__('admin.discount_settings'))
                                    ->schema([
                                        Forms\Components\Select::make('type')
                                            ->label(__('admin.coupon_type'))
                                            ->options([
                                                'fixed' => __('admin.fixed_amount'),
                                                'percentage' => __('admin.percentage'),
                                            ])
                                            ->required()
                                            ->default('fixed')
                                            ->live(),

                                        Forms\Components\TextInput::make('value')
                                            ->label(__('admin.discount_value'))
                                            ->numeric()
                                            ->required()
                                            ->default(0)
                                            ->prefix(fn (Forms\Get $get) => $get('type') === 'fixed' ? 'EGP' : null)
                                            ->suffix(fn (Forms\Get $get) => $get('type') === 'percentage' ? '%' : null),

                                        Forms\Components\TextInput::make('minimum_order_amount')
                                            ->label(__('admin.minimum_order_amount'))
                                            ->numeric()
                                            ->nullable()
                                            ->prefix('EGP'),

                                        Forms\Components\TextInput::make('maximum_discount_amount')
                                            ->label(__('admin.maximum_discount_amount'))
                                            ->numeric()
                                            ->nullable()
                                            ->prefix('EGP')
                                            ->visible(fn (Forms\Get $get) => $get('type') === 'percentage'),

                                        Forms\Components\Toggle::make('free_shipping')
                                            ->label(__('admin.free_shipping'))
                                            ->default(false),
                                    ])
                                    ->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make(__('admin.usage_limits'))
                            ->schema([
                                Forms\Components\Section::make(__('admin.usage_limits'))
                                    ->schema([
                                        Forms\Components\TextInput::make('usage_limit')
                                            ->label(__('admin.usage_limit'))
                                            ->numeric()
                                            ->nullable(),

                                        Forms\Components\TextInput::make('usage_limit_per_customer')
                                            ->label(__('admin.usage_limit_per_customer'))
                                            ->numeric()
                                            ->nullable(),

                                        Forms\Components\TextInput::make('used_count')
                                            ->label(__('admin.used_count'))
                                            ->numeric()
                                            ->default(0)
                                            ->disabled()
                                            ->dehydrated(true),
                                    ])
                                    ->columns(3),
                            ]),

                        Forms\Components\Tabs\Tab::make(__('admin.validity_period'))
                            ->schema([
                                Forms\Components\Section::make(__('admin.validity_period'))
                                    ->schema([
                                        Forms\Components\DateTimePicker::make('starts_at')
                                            ->label(__('admin.starts_at'))
                                            ->seconds(false),

                                        Forms\Components\DateTimePicker::make('expires_at')
                                            ->label(__('admin.expires_at'))
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
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label(__('admin.coupon_code'))
                    ->searchable()
                    ->copyable()
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name_ar')
                    ->label(__('admin.name_ar'))
                    ->searchable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('type')
                    ->label(__('admin.coupon_type'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => __('admin.coupon_' . $state))
                    ->color(fn ($state) => match ($state) {
                        'percentage' => 'info',
                        'fixed' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('value')
                    ->label(__('admin.discount_value'))
                    ->formatStateUsing(function ($state, $record) {
                        if ($record->type === 'percentage') {
                            return number_format((float) $state, 2, '.', ',') . '%';
                        }

                        return number_format((float) $state, 2, '.', ',') . ' EGP';
                    }),

                Tables\Columns\IconColumn::make('free_shipping')
                    ->label(__('admin.free_shipping'))
                    ->boolean(),

                Tables\Columns\TextColumn::make('used_count')
                    ->label(__('admin.used_count'))
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('usage_limit')
                    ->label(__('admin.usage_limit'))
                    ->formatStateUsing(fn ($state) => $state ?: '-'),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label(__('admin.expires_at'))
                    ->dateTime()
                    ->placeholder('-')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('admin.active'))
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('admin.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label(__('admin.coupon_type'))
                    ->options([
                        'fixed' => __('admin.fixed_amount'),
                        'percentage' => __('admin.percentage'),
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('admin.active')),

                Tables\Filters\TernaryFilter::make('free_shipping')
                    ->label(__('admin.free_shipping')),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
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
                Infolists\Components\Section::make(__('admin.coupon_overview'))
                    ->schema([
                        Infolists\Components\TextEntry::make('code')
                            ->label(__('admin.coupon_code'))
                            ->copyable()
                            ->badge()
                            ->weight('bold'),

                        Infolists\Components\TextEntry::make('name_ar')
                            ->label(__('admin.name_ar'))
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('name_en')
                            ->label(__('admin.name_en'))
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('type')
                            ->label(__('admin.coupon_type'))
                            ->badge()
                            ->formatStateUsing(fn ($state) => __('admin.coupon_' . $state)),

                        Infolists\Components\TextEntry::make('value')
                            ->label(__('admin.discount_value'))
                            ->formatStateUsing(function ($state, $record) {
                                if ($record->type === 'percentage') {
                                    return number_format((float) $state, 2, '.', ',') . '%';
                                }

                                return number_format((float) $state, 2, '.', ',') . ' EGP';
                            }),

                        Infolists\Components\IconEntry::make('free_shipping')
                            ->label(__('admin.free_shipping'))
                            ->boolean(),

                        Infolists\Components\IconEntry::make('is_active')
                            ->label(__('admin.active'))
                            ->boolean(),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make(__('admin.coupon_conditions'))
                    ->schema([
                        Infolists\Components\TextEntry::make('minimum_order_amount')
                            ->label(__('admin.minimum_order_amount'))
                            ->formatStateUsing(fn ($state) => $state ? number_format((float) $state, 2, '.', ',') . ' EGP' : '-'),

                        Infolists\Components\TextEntry::make('maximum_discount_amount')
                            ->label(__('admin.maximum_discount_amount'))
                            ->formatStateUsing(fn ($state) => $state ? number_format((float) $state, 2, '.', ',') . ' EGP' : '-'),

                        Infolists\Components\TextEntry::make('usage_limit')
                            ->label(__('admin.usage_limit'))
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('usage_limit_per_customer')
                            ->label(__('admin.usage_limit_per_customer'))
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('used_count')
                            ->label(__('admin.used_count'))
                            ->badge(),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make(__('admin.validity_period'))
                    ->schema([
                        Infolists\Components\TextEntry::make('starts_at')
                            ->label(__('admin.starts_at'))
                            ->dateTime()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('expires_at')
                            ->label(__('admin.expires_at'))
                            ->dateTime()
                            ->placeholder('-'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make(__('admin.notes'))
                    ->schema([
                        Infolists\Components\TextEntry::make('notes')
                            ->label(__('admin.notes'))
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCoupons::route('/'),
            'create' => Pages\CreateCoupon::route('/create'),
            'view' => Pages\ViewCoupon::route('/{record}'),
            'edit' => Pages\EditCoupon::route('/{record}/edit'),
        ];
    }
}