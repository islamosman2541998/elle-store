<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers\InvoicesRelationManager;
use App\Filament\Resources\OrderResource\RelationManagers\ItemsRelationManager;
use App\Filament\Resources\OrderResource\RelationManagers\PaymentsRelationManager;
use App\Filament\Resources\OrderResource\RelationManagers\ShipmentsRelationManager;
use App\Filament\Resources\OrderResource\RelationManagers\StatusHistoriesRelationManager;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\ShippingCity;
use App\Services\OrderPaymentService;
use App\Services\OrderShippingService;
use App\Services\PaymentSettingsService;
use App\Services\SecuritySettingsService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.sales');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.orders');
    }

    public static function getModelLabel(): string
    {
        return __('admin.order');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.orders');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Order Tabs')
                    ->columnSpanFull()
                    ->tabs([
                        Forms\Components\Tabs\Tab::make(__('admin.order_information'))
                            ->schema([
                                Forms\Components\Section::make(__('admin.main_information'))
                                    ->schema([
                                        Forms\Components\TextInput::make('order_number')
                                            ->label(__('admin.order_number'))
                                            ->required()
                                            ->maxLength(255)
                                            ->unique(ignoreRecord: true)
                                            ->default(fn() => 'ORD-' . now()->format('YmdHis')),

                                        Forms\Components\Select::make('customer_id')
                                            ->label(__('admin.customer'))
                                            ->options(fn() => Customer::query()
                                                ->orderBy('name')
                                                ->pluck('name', 'id')
                                                ->toArray())
                                            ->searchable()
                                            ->preload()
                                            ->live()
                                            ->nullable()
                                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                                if (! $state) {
                                                    return;
                                                }

                                                $customer = Customer::query()->find($state);

                                                if (! $customer) {
                                                    return;
                                                }

                                                $set('customer_name', $customer->name);
                                                $set('customer_email', $customer->email);
                                                $set('customer_phone', $customer->phone);

                                                $defaultAddress = $customer->defaultAddress;

                                                if ($defaultAddress) {
                                                    $set('customer_address_id', $defaultAddress->id);
                                                }
                                            }),

                                        Forms\Components\Select::make('customer_address_id')
                                            ->label(__('admin.customer_address'))
                                            ->options(function (Forms\Get $get) {
                                                $customerId = $get('customer_id');

                                                if (! $customerId) {
                                                    return [];
                                                }

                                                return CustomerAddress::query()
                                                    ->where('customer_id', $customerId)
                                                    ->get()
                                                    ->mapWithKeys(function ($address) {
                                                        $label = $address->label ?: $address->city;

                                                        return [
                                                            $address->id => $label . ' - ' . $address->city . ' - ' . $address->street,
                                                        ];
                                                    })
                                                    ->toArray();
                                            })
                                            ->searchable()
                                            ->preload()
                                            ->nullable(),

                                        Forms\Components\Select::make('shipping_city_id')
                                            ->label(__('admin.shipping_city'))
                                            ->options(function () {
                                                return ShippingCity::query()
                                                    ->where('is_active', true)
                                                    ->orderBy('sort_order')
                                                    ->get()
                                                    ->mapWithKeys(fn($city) => [
                                                        $city->id => app()->getLocale() === 'ar'
                                                            ? $city->name_ar
                                                            : $city->name_en,
                                                    ])
                                                    ->toArray();
                                            })
                                            ->searchable()
                                            ->preload()
                                            ->live()
                                            ->nullable()
                                            ->afterStateUpdated(function ($state, $record) {
                                                if (! $record) {
                                                    return;
                                                }

                                                app(OrderShippingService::class)->applyShippingToOrder(
                                                    $record->fresh(),
                                                    $state ? (int) $state : null
                                                );
                                            }),

                                        Forms\Components\Select::make('status')
                                            ->label(__('admin.order_status'))
                                            ->options([
                                                'pending' => __('admin.order_pending'),
                                                'confirmed' => __('admin.order_confirmed'),
                                                'processing' => __('admin.order_processing'),
                                                'shipped' => __('admin.order_shipped'),
                                                'delivered' => __('admin.order_delivered'),
                                                'cancelled' => __('admin.order_cancelled'),
                                                'returned' => __('admin.order_returned'),
                                            ])
                                            ->required()
                                            ->default('pending'),

                                        Forms\Components\Select::make('payment_status')
                                            ->label(__('admin.payment_status'))
                                            ->options([
                                                'unpaid' => __('admin.payment_unpaid'),
                                                'pending' => __('admin.payment_pending'),
                                                'paid' => __('admin.payment_paid'),
                                                'failed' => __('admin.payment_failed'),
                                                'refunded' => __('admin.payment_refunded'),
                                            ])
                                            ->required()
                                            ->default('unpaid'),

                                        Forms\Components\Select::make('payment_method')
                                            ->label(__('admin.payment_method'))
                                            ->options(fn() => app(PaymentSettingsService::class)->availablePaymentMethods())
                                            ->searchable()
                                            ->preload()
                                            ->nullable()
                                            ->live()
                                            ->afterStateUpdated(function ($state, $record) {
                                                if (! $record) {
                                                    return;
                                                }

                                                app(OrderPaymentService::class)->applyPaymentMethodToOrder(
                                                    $record->fresh(),
                                                    $state
                                                );
                                            }),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make(__('admin.customer_information'))
                                    ->schema([
                                        Forms\Components\TextInput::make('customer_name')
                                            ->label(__('admin.customer_name'))
                                            ->required()
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('customer_email')
                                            ->label(__('admin.customer_email'))
                                            ->email()
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('customer_phone')
                                            ->label(__('admin.customer_phone'))
                                            ->required()
                                            ->tel()
                                            ->maxLength(255),
                                    ])
                                    ->columns(3),
                            ]),

                        Forms\Components\Tabs\Tab::make(__('admin.order_totals'))
                            ->schema([
                                Forms\Components\Section::make(__('admin.totals'))
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

                                        Forms\Components\TextInput::make('payment_fee')
                                            ->label(__('admin.payment_fee'))
                                            ->numeric()
                                            ->default(0)
                                            ->prefix('EGP'),

                                        Forms\Components\TextInput::make('grand_total')
                                            ->label(__('admin.grand_total'))
                                            ->numeric()
                                            ->default(0)
                                            ->prefix('EGP'),

                                        Forms\Components\TextInput::make('coupon_code')
                                            ->label(__('admin.coupon_code'))
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('shipping_discount_source')
                                            ->label(__('admin.shipping_discount_source'))
                                            ->maxLength(255)
                                            ->disabled()
                                            ->dehydrated(true),

                                        Forms\Components\TextInput::make('free_shipping_offer_id')
                                            ->label(__('admin.free_shipping_offer'))
                                            ->numeric()
                                            ->disabled()
                                            ->dehydrated(true),
                                    ])
                                    ->columns(3),
                            ]),

                        Forms\Components\Tabs\Tab::make(__('admin.notes'))
                            ->schema([
                                Forms\Components\Section::make(__('admin.notes'))
                                    ->schema([
                                        Forms\Components\Textarea::make('customer_notes')
                                            ->label(__('admin.customer_notes'))
                                            ->rows(4),

                                        Forms\Components\Textarea::make('admin_notes')
                                            ->label(__('admin.admin_notes'))
                                            ->rows(4),
                                    ])
                                    ->columns(1),
                            ]),

                        Forms\Components\Tabs\Tab::make(__('admin.timestamps'))
                            ->schema([
                                Forms\Components\Section::make(__('admin.timestamps'))
                                    ->schema([
                                        Forms\Components\DateTimePicker::make('confirmed_at')
                                            ->label(__('admin.confirmed_at'))
                                            ->seconds(false),

                                        Forms\Components\DateTimePicker::make('shipped_at')
                                            ->label(__('admin.shipped_at'))
                                            ->seconds(false),

                                        Forms\Components\DateTimePicker::make('delivered_at')
                                            ->label(__('admin.delivered_at'))
                                            ->seconds(false),

                                        Forms\Components\DateTimePicker::make('cancelled_at')
                                            ->label(__('admin.cancelled_at'))
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
            ->modifyQueryUsing(
                fn(Builder $query) => $query
                    ->with(['customer', 'latestPayment', 'shippingCity', 'freeShippingOffer'])
                    ->withCount('items')
            )
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->label(__('admin.order_number'))
                    ->searchable()
                    ->copyable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('customer_name')
                    ->label(__('admin.customer'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('customer_phone')
                    ->label(__('admin.phone'))
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('items_count')
                    ->label(__('admin.items_count'))
                    ->badge(),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('admin.order_status'))
                    ->badge()
                    ->formatStateUsing(fn($state) => __('admin.order_' . $state))
                    ->color(fn($state) => match ($state) {
                        'pending' => 'gray',
                        'confirmed' => 'info',
                        'processing' => 'warning',
                        'shipped' => 'primary',
                        'delivered' => 'success',
                        'cancelled' => 'danger',
                        'returned' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('payment_status')
                    ->label(__('admin.payment_status'))
                    ->badge()
                    ->formatStateUsing(fn($state) => __('admin.payment_' . $state))
                    ->color(fn($state) => match ($state) {
                        'paid' => 'success',
                        'pending' => 'warning',
                        'unpaid' => 'gray',
                        'failed' => 'danger',
                        'refunded' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label(__('admin.payment_method'))
                    ->badge()
                    ->formatStateUsing(fn($state) => $state ? self::formatPaymentMethod($state) : '-'),

                Tables\Columns\TextColumn::make('grand_total')
                    ->label(__('admin.grand_total'))
                    ->formatStateUsing(fn($state) => number_format((float) $state, 2, '.', ',') . ' EGP')
                    ->badge()
                    ->color('success')
                    ->sortable(),

                Tables\Columns\TextColumn::make('shippingCity.name_ar')
                    ->label(__('admin.shipping_city'))
                    ->placeholder('-')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('shipping_total')
                    ->label(__('admin.shipping_total'))
                    ->formatStateUsing(fn($state) => number_format((float) $state, 2, '.', ',') . ' EGP')
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('payment_fee')
                    ->label(__('admin.payment_fee'))
                    ->formatStateUsing(fn($state) => number_format((float) $state, 2, '.', ',') . ' EGP')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('shipping_discount_source')
                    ->label(__('admin.shipping_discount_source'))
                    ->formatStateUsing(fn($state) => $state ? __('admin.' . $state) : '-')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'free_shipping_offer' => 'success',
                        'coupon_free_shipping' => 'success',
                        default => 'gray',
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('admin.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('admin.order_status'))
                    ->options([
                        'pending' => __('admin.order_pending'),
                        'confirmed' => __('admin.order_confirmed'),
                        'processing' => __('admin.order_processing'),
                        'shipped' => __('admin.order_shipped'),
                        'delivered' => __('admin.order_delivered'),
                        'cancelled' => __('admin.order_cancelled'),
                        'returned' => __('admin.order_returned'),
                    ]),

                Tables\Filters\SelectFilter::make('payment_status')
                    ->label(__('admin.payment_status'))
                    ->options([
                        'unpaid' => __('admin.payment_unpaid'),
                        'pending' => __('admin.payment_pending'),
                        'paid' => __('admin.payment_paid'),
                        'failed' => __('admin.payment_failed'),
                        'refunded' => __('admin.payment_refunded'),
                    ]),

                Tables\Filters\SelectFilter::make('payment_method')
                    ->label(__('admin.payment_method'))
                    ->options(fn() => app(PaymentSettingsService::class)->availablePaymentMethods()),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()->visible(fn($record) => app(SecuritySettingsService::class)->canEditOrder($record)),
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
                Infolists\Components\Section::make(__('admin.order_overview'))
                    ->schema([
                        Infolists\Components\TextEntry::make('order_number')
                            ->label(__('admin.order_number'))
                            ->copyable()
                            ->weight('bold'),

                        Infolists\Components\TextEntry::make('customer_name')
                            ->label(__('admin.customer_name')),

                        Infolists\Components\TextEntry::make('customer_phone')
                            ->label(__('admin.customer_phone'))
                            ->copyable(),

                        Infolists\Components\TextEntry::make('customer_email')
                            ->label(__('admin.customer_email'))
                            ->copyable()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('status')
                            ->label(__('admin.order_status'))
                            ->badge()
                            ->formatStateUsing(fn($state) => __('admin.order_' . $state)),

                        Infolists\Components\TextEntry::make('payment_status')
                            ->label(__('admin.payment_status'))
                            ->badge()
                            ->formatStateUsing(fn($state) => __('admin.payment_' . $state)),

                        Infolists\Components\TextEntry::make('payment_method')
                            ->label(__('admin.payment_method'))
                            ->badge()
                            ->formatStateUsing(fn($state) => $state ? self::formatPaymentMethod($state) : '-'),

                        Infolists\Components\TextEntry::make('grand_total')
                            ->label(__('admin.grand_total'))
                            ->formatStateUsing(fn($state) => number_format((float) $state, 2, '.', ',') . ' EGP')
                            ->badge()
                            ->color('success'),
                    ])
                    ->columns(4),

                Infolists\Components\Section::make(__('admin.order_totals'))
                    ->schema([
                        Infolists\Components\TextEntry::make('subtotal')
                            ->label(__('admin.subtotal'))
                            ->formatStateUsing(fn($state) => number_format((float) $state, 2, '.', ',') . ' EGP'),

                        Infolists\Components\TextEntry::make('discount_total')
                            ->label(__('admin.discount_total'))
                            ->formatStateUsing(fn($state) => number_format((float) $state, 2, '.', ',') . ' EGP'),

                        Infolists\Components\TextEntry::make('shipping_total')
                            ->label(__('admin.shipping_total'))
                            ->formatStateUsing(fn($state) => number_format((float) $state, 2, '.', ',') . ' EGP'),

                        Infolists\Components\TextEntry::make('tax_total')
                            ->label(__('admin.tax_total'))
                            ->formatStateUsing(fn($state) => number_format((float) $state, 2, '.', ',') . ' EGP'),

                        Infolists\Components\TextEntry::make('payment_fee')
                            ->label(__('admin.payment_fee'))
                            ->formatStateUsing(fn($state) => number_format((float) $state, 2, '.', ',') . ' EGP'),

                        Infolists\Components\TextEntry::make('grand_total')
                            ->label(__('admin.grand_total'))
                            ->formatStateUsing(fn($state) => number_format((float) $state, 2, '.', ',') . ' EGP')
                            ->weight('bold'),

                        Infolists\Components\TextEntry::make('coupon_code')
                            ->label(__('admin.coupon_code'))
                            ->placeholder('-'),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make(__('admin.shipping_information'))
                    ->schema([
                        Infolists\Components\TextEntry::make('shippingCity.name_ar')
                            ->label(__('admin.shipping_city'))
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('shippingZone.name_ar')
                            ->label(__('admin.shipping_zone'))
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('shipping_discount_source')
                            ->label(__('admin.shipping_discount_source'))
                            ->formatStateUsing(fn($state) => $state ? __('admin.' . $state) : '-')
                            ->badge()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('freeShippingOffer.name_ar')
                            ->label(__('admin.free_shipping_offer'))
                            ->placeholder('-'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make(__('admin.notes'))
                    ->schema([
                        Infolists\Components\TextEntry::make('customer_notes')
                            ->label(__('admin.customer_notes'))
                            ->placeholder('-')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('admin_notes')
                            ->label(__('admin.admin_notes'))
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->columns(1)
                    ->collapsible()
                    ->collapsed(),

                Infolists\Components\Section::make(__('admin.timestamps'))
                    ->schema([
                        Infolists\Components\TextEntry::make('confirmed_at')
                            ->label(__('admin.confirmed_at'))
                            ->dateTime()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('shipped_at')
                            ->label(__('admin.shipped_at'))
                            ->dateTime()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('delivered_at')
                            ->label(__('admin.delivered_at'))
                            ->dateTime()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('cancelled_at')
                            ->label(__('admin.cancelled_at'))
                            ->dateTime()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('created_at')
                            ->label(__('admin.created_at'))
                            ->dateTime(),

                        Infolists\Components\TextEntry::make('updated_at')
                            ->label(__('admin.updated_at'))
                            ->dateTime(),
                    ])
                    ->columns(3)
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

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
            PaymentsRelationManager::class,
            InvoicesRelationManager::class,
            ShipmentsRelationManager::class,
            StatusHistoriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'view' => Pages\ViewOrder::route('/{record}'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }

    protected static function formatPaymentMethod(?string $method): string
    {
        return match ($method) {
            'cash_on_delivery' => __('admin.payment_cash_on_delivery'),
            'bank_transfer' => __('admin.payment_bank_transfer'),
            'wallet_transfer' => __('admin.payment_wallet_transfer'),
            'manual' => __('admin.manual_payment'),
            default => $method ? __('admin.' . $method) : '-',
        };
    }
    public static function canEdit($record): bool
    {
        if (! $record) {
            return true;
        }

        return app(SecuritySettingsService::class)->canEditOrder($record);
    }
}