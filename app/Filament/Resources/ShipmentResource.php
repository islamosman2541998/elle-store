<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShipmentResource\Pages;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\ShippingCity;
use App\Models\ShippingCompany;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ShipmentResource extends Resource
{
    protected static ?string $model = Shipment::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.shipping_delivery');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.shipments');
    }

    public static function getModelLabel(): string
    {
        return __('admin.shipment');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.shipments');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Shipment Tabs')
                    ->columnSpanFull()
                    ->tabs([
                        Forms\Components\Tabs\Tab::make(__('admin.shipment_information'))
                            ->schema([
                                Forms\Components\Section::make(__('admin.main_information'))
                                    ->schema([
                                        Forms\Components\TextInput::make('shipment_number')
                                            ->label(__('admin.shipment_number'))
                                            ->required()
                                            ->unique(ignoreRecord: true)
                                            ->default(fn () => 'SHP-' . now()->format('YmdHis')),

                                        Forms\Components\Select::make('order_id')
                                            ->label(__('admin.order'))
                                            ->options(function () {
                                                return Order::query()
                                                    ->latest()
                                                    ->get()
                                                    ->mapWithKeys(fn ($order) => [
                                                        $order->id => $order->order_number . ' - ' . $order->customer_name,
                                                    ])
                                                    ->toArray();
                                            })
                                            ->searchable()
                                            ->preload()
                                            ->live()
                                            ->required()
                                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                                if (! $state) {
                                                    return;
                                                }

                                                $order = Order::query()->find($state);

                                                if (! $order) {
                                                    return;
                                                }

                                                $set('shipping_fee', $order->shipping_total);

                                                if ($order->shipping_address_snapshot) {
                                                    $set('shipping_address_preview', json_encode(
                                                        $order->shipping_address_snapshot,
                                                        JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
                                                    ));
                                                }
                                            }),

                                        Forms\Components\Select::make('shipping_company_id')
                                            ->label(__('admin.shipping_company'))
                                            ->options(fn () => ShippingCompany::query()
                                                ->where('is_active', true)
                                                ->orderBy('sort_order')
                                                ->pluck('name', 'id')
                                                ->toArray())
                                            ->searchable()
                                            ->preload()
                                            ->live()
                                            ->nullable()
                                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                                $trackingNumber = $get('tracking_number');

                                                if (! $state || ! $trackingNumber) {
                                                    return;
                                                }

                                                $company = ShippingCompany::query()->find($state);

                                                if (! $company) {
                                                    return;
                                                }

                                                $set('tracking_url', $company->generateTrackingUrl($trackingNumber));
                                            }),

                                        Forms\Components\Select::make('shipping_city_id')
                                            ->label(__('admin.shipping_city'))
                                            ->options(function () {
                                                return ShippingCity::query()
                                                    ->where('is_active', true)
                                                    ->orderBy('sort_order')
                                                    ->get()
                                                    ->mapWithKeys(fn ($city) => [
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
                                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                                if (! $state) {
                                                    return;
                                                }

                                                $city = ShippingCity::query()->find($state);

                                                if (! $city) {
                                                    return;
                                                }

                                                $orderId = $get('order_id');
                                                $orderTotal = 0;

                                                if ($orderId) {
                                                    $order = Order::query()->find($orderId);
                                                    $orderTotal = (float) ($order?->grand_total ?? 0);
                                                }

                                                $set('shipping_fee', $city->calculateDeliveryFee($orderTotal));
                                            }),

                                        Forms\Components\Select::make('status')
                                            ->label(__('admin.shipment_status'))
                                            ->options([
                                                'pending' => __('admin.shipment_pending'),
                                                'assigned' => __('admin.shipment_assigned'),
                                                'picked_up' => __('admin.shipment_picked_up'),
                                                'in_transit' => __('admin.shipment_in_transit'),
                                                'delivered' => __('admin.shipment_delivered'),
                                                'failed' => __('admin.shipment_failed'),
                                                'returned' => __('admin.shipment_returned'),
                                                'cancelled' => __('admin.shipment_cancelled'),
                                            ])
                                            ->required()
                                            ->default('pending')
                                            ->live()
                                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                                if ($state === 'assigned') {
                                                    $set('assigned_at', now());
                                                }

                                                if ($state === 'picked_up') {
                                                    $set('picked_up_at', now());
                                                }

                                                if ($state === 'in_transit') {
                                                    $set('in_transit_at', now());
                                                }

                                                if ($state === 'delivered') {
                                                    $set('delivered_at', now());
                                                }

                                                if ($state === 'failed') {
                                                    $set('failed_at', now());
                                                }

                                                if ($state === 'returned') {
                                                    $set('returned_at', now());
                                                }
                                            }),

                                        Forms\Components\TextInput::make('tracking_number')
                                            ->label(__('admin.tracking_number'))
                                            ->maxLength(255)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                                $companyId = $get('shipping_company_id');

                                                if (! $companyId || ! $state) {
                                                    return;
                                                }

                                                $company = ShippingCompany::query()->find($companyId);

                                                if (! $company) {
                                                    return;
                                                }

                                                $set('tracking_url', $company->generateTrackingUrl($state));
                                            }),

                                        Forms\Components\TextInput::make('tracking_url')
                                            ->label(__('admin.tracking_url'))
                                            ->maxLength(255)
                                            ->columnSpanFull(),

                                        Forms\Components\TextInput::make('shipping_fee')
                                            ->label(__('admin.shipping_fee'))
                                            ->numeric()
                                            ->default(0)
                                            ->prefix('EGP'),
                                    ])
                                    ->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make(__('admin.shipment_dates'))
                            ->schema([
                                Forms\Components\Section::make(__('admin.shipment_dates'))
                                    ->schema([
                                        Forms\Components\DateTimePicker::make('assigned_at')
                                            ->label(__('admin.assigned_at'))
                                            ->seconds(false),

                                        Forms\Components\DateTimePicker::make('picked_up_at')
                                            ->label(__('admin.picked_up_at'))
                                            ->seconds(false),

                                        Forms\Components\DateTimePicker::make('in_transit_at')
                                            ->label(__('admin.in_transit_at'))
                                            ->seconds(false),

                                        Forms\Components\DateTimePicker::make('delivered_at')
                                            ->label(__('admin.delivered_at'))
                                            ->seconds(false),

                                        Forms\Components\DateTimePicker::make('failed_at')
                                            ->label(__('admin.failed_at'))
                                            ->seconds(false),

                                        Forms\Components\DateTimePicker::make('returned_at')
                                            ->label(__('admin.returned_at'))
                                            ->seconds(false),
                                    ])
                                    ->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make(__('admin.notes'))
                            ->schema([
                                Forms\Components\Textarea::make('notes')
                                    ->label(__('admin.notes'))
                                    ->rows(4)
                                    ->columnSpanFull(),

                                Forms\Components\Textarea::make('shipping_address_preview')
                                    ->label(__('admin.shipping_address_snapshot'))
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->rows(6)
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['order', 'company', 'city']))
            ->columns([
                Tables\Columns\TextColumn::make('shipment_number')
                    ->label(__('admin.shipment_number'))
                    ->searchable()
                    ->copyable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('order.order_number')
                    ->label(__('admin.order_number'))
                    ->searchable()
                    ->copyable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('order.customer_name')
                    ->label(__('admin.customer'))
                    ->searchable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('company.name')
                    ->label(__('admin.shipping_company'))
                    ->searchable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('city.name_ar')
                    ->label(__('admin.shipping_city'))
                    ->searchable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('admin.shipment_status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => __('admin.shipment_' . $state))
                    ->color(fn ($state) => match ($state) {
                        'pending' => 'gray',
                        'assigned' => 'info',
                        'picked_up' => 'warning',
                        'in_transit' => 'primary',
                        'delivered' => 'success',
                        'failed' => 'danger',
                        'returned' => 'danger',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('tracking_number')
                    ->label(__('admin.tracking_number'))
                    ->copyable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('shipping_fee')
                    ->label(__('admin.shipping_fee'))
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2, '.', ',') . ' EGP')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('admin.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('admin.shipment_status'))
                    ->options([
                        'pending' => __('admin.shipment_pending'),
                        'assigned' => __('admin.shipment_assigned'),
                        'picked_up' => __('admin.shipment_picked_up'),
                        'in_transit' => __('admin.shipment_in_transit'),
                        'delivered' => __('admin.shipment_delivered'),
                        'failed' => __('admin.shipment_failed'),
                        'returned' => __('admin.shipment_returned'),
                        'cancelled' => __('admin.shipment_cancelled'),
                    ]),

                Tables\Filters\SelectFilter::make('shipping_company_id')
                    ->label(__('admin.shipping_company'))
                    ->options(fn () => ShippingCompany::query()
                        ->orderBy('sort_order')
                        ->pluck('name', 'id')
                        ->toArray()),

                Tables\Filters\SelectFilter::make('shipping_city_id')
                    ->label(__('admin.shipping_city'))
                    ->options(function () {
                        return ShippingCity::query()
                            ->orderBy('sort_order')
                            ->get()
                            ->mapWithKeys(fn ($city) => [
                                $city->id => app()->getLocale() === 'ar'
                                    ? $city->name_ar
                                    : $city->name_en,
                            ])
                            ->toArray();
                    }),

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
                Infolists\Components\Section::make(__('admin.shipment_overview'))
                    ->schema([
                        Infolists\Components\TextEntry::make('shipment_number')
                            ->label(__('admin.shipment_number'))
                            ->copyable()
                            ->weight('bold'),

                        Infolists\Components\TextEntry::make('order.order_number')
                            ->label(__('admin.order_number'))
                            ->copyable(),

                        Infolists\Components\TextEntry::make('order.customer_name')
                            ->label(__('admin.customer_name'))
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('company.name')
                            ->label(__('admin.shipping_company'))
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('city.name_ar')
                            ->label(__('admin.shipping_city'))
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('status')
                            ->label(__('admin.shipment_status'))
                            ->badge()
                            ->formatStateUsing(fn ($state) => __('admin.shipment_' . $state)),

                        Infolists\Components\TextEntry::make('tracking_number')
                            ->label(__('admin.tracking_number'))
                            ->copyable()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('tracking_url')
                            ->label(__('admin.tracking_url'))
                            ->copyable()
                            ->url(fn ($state) => $state)
                            ->openUrlInNewTab()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('shipping_fee')
                            ->label(__('admin.shipping_fee'))
                            ->formatStateUsing(fn ($state) => number_format((float) $state, 2, '.', ',') . ' EGP'),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make(__('admin.shipment_dates'))
                    ->schema([
                        Infolists\Components\TextEntry::make('assigned_at')
                            ->label(__('admin.assigned_at'))
                            ->dateTime()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('picked_up_at')
                            ->label(__('admin.picked_up_at'))
                            ->dateTime()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('in_transit_at')
                            ->label(__('admin.in_transit_at'))
                            ->dateTime()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('delivered_at')
                            ->label(__('admin.delivered_at'))
                            ->dateTime()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('failed_at')
                            ->label(__('admin.failed_at'))
                            ->dateTime()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('returned_at')
                            ->label(__('admin.returned_at'))
                            ->dateTime()
                            ->placeholder('-'),
                    ])
                    ->columns(3)
                    ->collapsible(),

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
            'index' => Pages\ListShipments::route('/'),
            'create' => Pages\CreateShipment::route('/create'),
            'view' => Pages\ViewShipment::route('/{record}'),
            'edit' => Pages\EditShipment::route('/{record}/edit'),
        ];
    }
}