<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use App\Services\CouponService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    public static function getTitle($ownerRecord, string $pageClass): string
    {
        return __('admin.payments');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('admin.payment_information'))
                    ->schema([
                        Forms\Components\Select::make('method')
                            ->label(__('admin.payment_method'))
                            ->options([
                                'cash_on_delivery' => __('admin.cash_on_delivery'),
                                'bank_transfer' => __('admin.bank_transfer'),
                                'wallet_transfer' => __('admin.wallet_transfer'),
                                'manual' => __('admin.manual_payment'),
                            ])
                            ->required()
                            ->default(fn () => $this->ownerRecord->payment_method ?? 'cash_on_delivery'),

                        Forms\Components\Select::make('status')
                            ->label(__('admin.payment_status'))
                            ->options([
                                'pending' => __('admin.payment_pending'),
                                'pending_review' => __('admin.payment_pending_review'),
                                'paid' => __('admin.payment_paid'),
                                'failed' => __('admin.payment_failed'),
                                'rejected' => __('admin.payment_rejected'),
                                'refunded' => __('admin.payment_refunded'),
                            ])
                            ->required()
                            ->default('pending')
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                if ($state === 'paid') {
                                    $set('paid_at', now());
                                }

                                if (in_array($state, ['pending', 'pending_review', 'failed', 'rejected', 'refunded'], true)) {
                                    $set('paid_at', null);
                                }
                            }),

                        Forms\Components\TextInput::make('amount')
                            ->label(__('admin.amount'))
                            ->numeric()
                            ->required()
                            ->default(fn () => $this->ownerRecord->grand_total)
                            ->prefix('EGP'),

                        Forms\Components\TextInput::make('transaction_reference')
                            ->label(__('admin.transaction_reference'))
                            ->maxLength(255),

                        Forms\Components\FileUpload::make('payment_proof')
                            ->label(__('admin.payment_proof'))
                            ->directory('payment-proofs')
                            ->disk('public')
                            ->image()
                            ->imageEditor()
                            ->imagePreviewHeight('180')
                            ->openable()
                            ->downloadable()
                            ->nullable()
                            ->columnSpanFull(),

                        Forms\Components\DateTimePicker::make('paid_at')
                            ->label(__('admin.paid_at'))
                            ->seconds(false),

                        Forms\Components\Textarea::make('notes')
                            ->label(__('admin.notes'))
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('method')
            ->columns([
                Tables\Columns\TextColumn::make('method')
                    ->label(__('admin.payment_method'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'cash_on_delivery' => __('admin.cash_on_delivery'),
                        'bank_transfer' => __('admin.bank_transfer'),
                        'wallet_transfer' => __('admin.wallet_transfer'),
                        'manual' => __('admin.manual_payment'),
                        default => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'cash_on_delivery' => 'warning',
                        'bank_transfer' => 'info',
                        'wallet_transfer' => 'success',
                        'manual' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('admin.payment_status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pending' => __('admin.payment_pending'),
                        'pending_review' => __('admin.payment_pending_review'),
                        'paid' => __('admin.payment_paid'),
                        'failed' => __('admin.payment_failed'),
                        'rejected' => __('admin.payment_rejected'),
                        'refunded' => __('admin.payment_refunded'),
                        default => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'paid' => 'success',
                        'pending' => 'warning',
                        'pending_review' => 'info',
                        'failed', 'rejected' => 'danger',
                        'refunded' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('amount')
                    ->label(__('admin.amount'))
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2, '.', ',') . ' EGP')
                    ->sortable(),

                Tables\Columns\TextColumn::make('transaction_reference')
                    ->label(__('admin.transaction_reference'))
                    ->copyable()
                    ->placeholder('-'),

                Tables\Columns\ImageColumn::make('payment_proof')
                    ->label(__('admin.payment_proof'))
                    ->disk('public')
                    ->height(56)
                    ->square()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('paid_at')
                    ->label(__('admin.paid_at'))
                    ->dateTime()
                    ->placeholder('-')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('admin.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label(__('admin.add_payment'))
                    ->after(function (): void {
                        $this->syncOrderPaymentStatus();
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('view_payment_proof')
                    ->label(__('admin.view_payment_proof'))
                    ->icon('heroicon-o-photo')
                    ->color('info')
                    ->visible(fn ($record) => filled($record->payment_proof))
                    ->url(fn ($record) => $record->payment_proof
                        ? Storage::disk('public')->url($record->payment_proof)
                        : null
                    )
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('approve_payment')
                    ->label(__('admin.approve_payment'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status !== 'paid')
                    ->action(function ($record): void {
                        $record->update([
                            'status' => 'paid',
                            'paid_at' => now(),
                        ]);

                        $this->syncOrderPaymentStatus();

                        Notification::make()
                            ->title(__('admin.payment_approved_successfully'))
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('reject_payment')
                    ->label(__('admin.reject_payment'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => ! in_array($record->status, ['paid', 'rejected', 'refunded'], true))
                    ->action(function ($record): void {
                        $record->update([
                            'status' => 'rejected',
                            'paid_at' => null,
                        ]);

                        $this->syncOrderPaymentStatus();

                        Notification::make()
                            ->title(__('admin.payment_rejected_successfully'))
                            ->danger()
                            ->send();
                    }),

                Tables\Actions\EditAction::make()
                    ->after(function (): void {
                        $this->syncOrderPaymentStatus();
                    }),

                Tables\Actions\DeleteAction::make()
                    ->after(function (): void {
                        $this->syncOrderPaymentStatus();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->after(function (): void {
                        $this->syncOrderPaymentStatus();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    protected function syncOrderPaymentStatus(): void
    {
        $order = $this->ownerRecord->fresh('payments');

        $paidTotal = $order->payments
            ->where('status', 'paid')
            ->sum(fn ($payment) => (float) $payment->amount);

        $hasPending = $order->payments
            ->whereIn('status', ['pending', 'pending_review'])
            ->isNotEmpty();

        $hasFailed = $order->payments
            ->whereIn('status', ['failed', 'rejected'])
            ->isNotEmpty();

        $hasRefunded = $order->payments
            ->where('status', 'refunded')
            ->isNotEmpty();

        $paymentStatus = 'unpaid';

        if ($paidTotal >= (float) $order->grand_total && (float) $order->grand_total > 0) {
            $paymentStatus = 'paid';
        } elseif ($hasPending) {
            $paymentStatus = 'pending';
        } elseif ($hasRefunded) {
            $paymentStatus = 'refunded';
        } elseif ($hasFailed) {
            $paymentStatus = 'failed';
        }

        $latestPayment = $order->payments()
            ->latest()
            ->first();

        $order->update([
            'payment_status' => $paymentStatus,
            'payment_method' => $latestPayment?->method ?? $order->payment_method,
        ]);

        if ($paymentStatus === 'paid') {
            app(CouponService::class)->markCouponAsUsedForOrder($order->fresh());
        }
    }
}