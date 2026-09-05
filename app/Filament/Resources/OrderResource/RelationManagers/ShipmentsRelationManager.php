<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use App\Models\ShippingCity;
use App\Models\ShippingCompany;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ShipmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'shipments';

    public static function getTitle($ownerRecord, string $pageClass): string
    {
        return __('admin.shipments');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('admin.shipment_information'))
                    ->schema([
                        Forms\Components\TextInput::make('shipment_number')
                            ->label(__('admin.shipment_number'))
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->default(fn () => 'SHP-' . now()->format('YmdHis')),

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
                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                if (! $state) {
                                    return;
                                }

                                $city = ShippingCity::query()->find($state);

                                if (! $city) {
                                    return;
                                }

                                $set('shipping_fee', $city->calculateDeliveryFee((float) $this->ownerRecord->grand_total));
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
                            ->default(fn () => $this->ownerRecord->shipping_total)
                            ->prefix('EGP'),

                        Forms\Components\Hidden::make('shipping_address_snapshot')
                            ->default(fn () => $this->ownerRecord->shipping_address_snapshot),
                    ])
                    ->columns(2),

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
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),

                Forms\Components\Textarea::make('notes')
                    ->label(__('admin.notes'))
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['company', 'city']))
            ->recordTitleAttribute('shipment_number')
            ->columns([
                Tables\Columns\TextColumn::make('shipment_number')
                    ->label(__('admin.shipment_number'))
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('company.name')
                    ->label(__('admin.shipping_company'))
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('city.name_ar')
                    ->label(__('admin.shipping_city'))
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
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2, '.', ',') . ' EGP'),

                Tables\Columns\TextColumn::make('delivered_at')
                    ->label(__('admin.delivered_at'))
                    ->dateTime()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('admin.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label(__('admin.add_shipment'))
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['shipping_address_snapshot'] = $this->ownerRecord->shipping_address_snapshot;

                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['shipping_address_snapshot'] = $this->ownerRecord->shipping_address_snapshot;

                        return $data;
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}