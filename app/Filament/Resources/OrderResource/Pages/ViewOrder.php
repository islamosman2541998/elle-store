<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Shipment;
use App\Models\ShippingCity;
use App\Models\ShippingCompany;
use App\Services\CouponService;
use App\Services\FlashSaleService;
use App\Services\InvoiceService;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\StockService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Validation\ValidationException;
use Throwable;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('generate_shipment')
                ->label(__('admin.generate_shipment'))
                ->icon('heroicon-o-truck')
                ->color('info')
                ->form([
                    Forms\Components\Select::make('shipping_company_id')
                        ->label(__('admin.shipping_company'))
                        ->options(fn() => ShippingCompany::query()
                            ->where('is_active', true)
                            ->orderBy('sort_order')
                            ->pluck('name', 'id')
                            ->toArray())
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
                        ->nullable(),

                    Forms\Components\TextInput::make('tracking_number')
                        ->label(__('admin.tracking_number'))
                        ->maxLength(255),

                    Forms\Components\Select::make('status')
                        ->label(__('admin.shipment_status'))
                        ->options([
                            'pending' => __('admin.shipment_pending'),
                            'assigned' => __('admin.shipment_assigned'),
                        ])
                        ->required()
                        ->default('pending'),

                    Forms\Components\Textarea::make('notes')
                        ->label(__('admin.notes'))
                        ->rows(3),
                ])
                ->requiresConfirmation()
                ->modalHeading(__('admin.generate_shipment'))
                ->modalDescription(__('admin.generate_shipment_confirmation'))
                ->action(function (array $data): void {
                    $existingShipment = $this->record->shipments()->latest()->first();

                    if ($existingShipment) {
                        Notification::make()
                            ->title(__('admin.shipment_already_exists'))
                            ->warning()
                            ->send();

                        return;
                    }

                    $trackingUrl = null;

                    if (! empty($data['shipping_company_id']) && ! empty($data['tracking_number'])) {
                        $company = ShippingCompany::query()->find($data['shipping_company_id']);
                        $trackingUrl = $company?->generateTrackingUrl($data['tracking_number']);
                    }

                    Shipment::create([
                        'shipment_number' => 'SHP-' . now()->format('YmdHis'),
                        'order_id' => $this->record->id,
                        'shipping_company_id' => $data['shipping_company_id'] ?? null,
                        'shipping_city_id' => $data['shipping_city_id'] ?? $this->record->shipping_city_id,
                        'status' => $data['status'],
                        'tracking_number' => $data['tracking_number'] ?? null,
                        'tracking_url' => $trackingUrl,
                        'shipping_fee' => $this->record->shipping_total,
                        'shipping_address_snapshot' => $this->record->shipping_address_snapshot,
                        'assigned_at' => $data['status'] === 'assigned' ? now() : null,
                        'notes' => $data['notes'] ?? null,
                    ]);

                    Notification::make()
                        ->title(__('admin.shipment_generated_successfully'))
                        ->success()
                        ->send();
                }),

            Actions\Action::make('generate_invoice')
                ->label(__('admin.generate_invoice'))
                ->icon('heroicon-o-document-text')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading(__('admin.generate_invoice'))
                ->modalDescription(__('admin.generate_invoice_confirmation'))
                ->action(function (): void {
                    try {
                        $invoice = app(InvoiceService::class)->createForOrder(
                            $this->record->fresh()
                        );

                        app(NotificationDispatcher::class)->customerInvoiceCreated($invoice);

                        Notification::make()
                            ->title(__('admin.invoice_generated_successfully'))
                            ->body($invoice->invoice_number)
                            ->success()
                            ->send();

                        $this->redirect(OrderResource::getUrl('view', [
                            'record' => $this->record->id,
                        ]));
                    } catch (ValidationException $e) {
                        Notification::make()
                            ->title(__('admin.invoice_generation_failed'))
                            ->body(collect($e->errors())->flatten()->first())
                            ->danger()
                            ->send();

                        return;
                    } catch (Throwable $e) {
                        report($e);

                        Notification::make()
                            ->title(__('admin.invoice_generation_failed'))
                            ->body($e->getMessage())
                            ->danger()
                            ->send();

                        return;
                    }
                }),

            Actions\Action::make('apply_coupon')
                ->label(__('admin.apply_coupon'))
                ->icon('heroicon-o-ticket')
                ->color('success')
                ->form([
                    Forms\Components\TextInput::make('coupon_code')
                        ->label(__('admin.coupon_code'))
                        ->required()
                        ->maxLength(255)
                        ->default(fn() => $this->record->coupon_code),
                ])
                ->action(function (array $data): void {
                    try {
                        app(CouponService::class)->applyToOrder(
                            $this->record->fresh(),
                            $data['coupon_code']
                        );

                        Notification::make()
                            ->title(__('admin.coupon_applied_successfully'))
                            ->success()
                            ->send();

                        $this->redirect(OrderResource::getUrl('view', [
                            'record' => $this->record->id,
                        ]));
                    } catch (ValidationException $e) {
                        Notification::make()
                            ->title(__('admin.coupon_not_valid'))
                            ->body(collect($e->errors())->flatten()->first())
                            ->danger()
                            ->send();

                        return;
                    } catch (Throwable $e) {
                        report($e);

                        Notification::make()
                            ->title('Coupon action error')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();

                        return;
                    }
                }),

            Actions\Action::make('remove_coupon')
                ->label(__('admin.remove_coupon'))
                ->icon('heroicon-o-x-mark')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn() => filled($this->record->fresh()->coupon_code))
                ->action(function (): void {
                    try {
                        app(CouponService::class)->removeFromOrder($this->record->fresh());

                        Notification::make()
                            ->title(__('admin.coupon_removed_successfully'))
                            ->success()
                            ->send();

                        $this->redirect(OrderResource::getUrl('view', [
                            'record' => $this->record->id,
                        ]));
                    } catch (Throwable $e) {
                        report($e);

                        Notification::make()
                            ->title('Coupon action error')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();

                        return;
                    }
                }),

            Actions\Action::make('change_status')
                ->label(__('admin.change_status'))
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->form([
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
                        ->default(fn() => $this->record->status),

                    Forms\Components\Textarea::make('note')
                        ->label(__('admin.note'))
                        ->rows(3),
                ])
                ->action(function (array $data): void {
                    $oldStatus = $this->record->status;
                    $newStatus = $data['status'];

                    if ($oldStatus === $newStatus) {
                        Notification::make()
                            ->title(__('admin.status_not_changed'))
                            ->warning()
                            ->send();

                        return;
                    }

                    $timestamps = [];

                    if ($newStatus === 'confirmed') {
                        $timestamps['confirmed_at'] = now();
                    }

                    if ($newStatus === 'shipped') {
                        $timestamps['shipped_at'] = now();
                    }

                    if ($newStatus === 'delivered') {
                        $timestamps['delivered_at'] = now();
                    }

                    if ($newStatus === 'cancelled') {
                        $timestamps['cancelled_at'] = now();
                    }

                    $this->record->update([
                        'status' => $newStatus,
                        ...$timestamps,
                    ]);

                    $this->record->statusHistories()->create([
                        'user_id' => auth()->id(),
                        'from_status' => $oldStatus,
                        'to_status' => $newStatus,
                        'note' => $data['note'] ?? null,
                    ]);
                    app(NotificationDispatcher::class)->customerOrderStatus($this->record->fresh());

                    if (in_array($newStatus, ['confirmed', 'processing']) && ! $this->record->stock_deducted_at) {
                        app(StockService::class)->deductOrderStock($this->record->fresh());
                    }

                    if (in_array($newStatus, ['confirmed', 'processing', 'delivered'])) {
                        app(FlashSaleService::class)->countFlashSaleItemsForOrder($this->record->fresh());
                    }

                    if (in_array($newStatus, ['cancelled', 'returned']) && $this->record->stock_deducted_at) {
                        app(StockService::class)->restoreOrderStock(
                            $this->record->fresh(),
                            $newStatus === 'returned' ? 'return' : 'order_cancelled_restore'
                        );
                    }

                    Notification::make()
                        ->title(__('admin.status_updated_successfully'))
                        ->success()
                        ->send();
                }),

            Actions\EditAction::make()
                ->label(__('admin.edit')),
        ];
    }
}