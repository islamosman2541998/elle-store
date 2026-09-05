<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class InvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'invoices';

    public static function getTitle($ownerRecord, string $pageClass): string
    {
        return __('admin.invoices');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('admin.invoice_information'))
                    ->schema([
                        Forms\Components\TextInput::make('invoice_number')
                            ->label(__('admin.invoice_number'))
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->default(fn () => 'INV-' . now()->format('YmdHis')),

                        Forms\Components\Hidden::make('customer_id')
                            ->default(fn () => $this->ownerRecord->customer_id),

                        Forms\Components\TextInput::make('customer_name')
                            ->label(__('admin.customer_name'))
                            ->required()
                            ->default(fn () => $this->ownerRecord->customer_name),

                        Forms\Components\TextInput::make('customer_email')
                            ->label(__('admin.customer_email'))
                            ->email()
                            ->default(fn () => $this->ownerRecord->customer_email),

                        Forms\Components\TextInput::make('customer_phone')
                            ->label(__('admin.customer_phone'))
                            ->default(fn () => $this->ownerRecord->customer_phone),

                        Forms\Components\Select::make('status')
                            ->label(__('admin.invoice_status'))
                            ->options([
                                'unpaid' => __('admin.invoice_unpaid'),
                                'paid' => __('admin.invoice_paid'),
                                'cancelled' => __('admin.invoice_cancelled'),
                            ])
                            ->required()
                            ->default(fn () => $this->ownerRecord->payment_status === 'paid' ? 'paid' : 'unpaid')
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                if ($state === 'paid') {
                                    $set('paid_at', now());
                                }

                                if ($state === 'cancelled') {
                                    $set('cancelled_at', now());
                                }
                            }),

                        Forms\Components\DateTimePicker::make('issued_at')
                            ->label(__('admin.issued_at'))
                            ->seconds(false)
                            ->default(now()),

                        Forms\Components\DateTimePicker::make('paid_at')
                            ->label(__('admin.paid_at'))
                            ->seconds(false),

                        Forms\Components\DateTimePicker::make('cancelled_at')
                            ->label(__('admin.cancelled_at'))
                            ->seconds(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make(__('admin.invoice_totals'))
                    ->schema([
                        Forms\Components\TextInput::make('subtotal')
                            ->label(__('admin.subtotal'))
                            ->numeric()
                            ->default(fn () => $this->ownerRecord->subtotal)
                            ->prefix('EGP'),

                        Forms\Components\TextInput::make('discount_total')
                            ->label(__('admin.discount_total'))
                            ->numeric()
                            ->default(fn () => $this->ownerRecord->discount_total)
                            ->prefix('EGP'),

                        Forms\Components\TextInput::make('shipping_total')
                            ->label(__('admin.shipping_total'))
                            ->numeric()
                            ->default(fn () => $this->ownerRecord->shipping_total)
                            ->prefix('EGP'),

                        Forms\Components\TextInput::make('tax_total')
                            ->label(__('admin.tax_total'))
                            ->numeric()
                            ->default(fn () => $this->ownerRecord->tax_total)
                            ->prefix('EGP'),

                        Forms\Components\TextInput::make('grand_total')
                            ->label(__('admin.grand_total'))
                            ->numeric()
                            ->default(fn () => $this->ownerRecord->grand_total)
                            ->prefix('EGP'),
                    ])
                    ->columns(3),

                Forms\Components\Textarea::make('notes')
                    ->label(__('admin.notes'))
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('invoice_number')
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label(__('admin.invoice_number'))
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('admin.invoice_status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => __('admin.invoice_' . $state))
                    ->color(fn ($state) => match ($state) {
                        'paid' => 'success',
                        'unpaid' => 'warning',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('grand_total')
                    ->label(__('admin.grand_total'))
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2, '.', ',') . ' EGP')
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('issued_at')
                    ->label(__('admin.issued_at'))
                    ->dateTime()
                    ->placeholder('-')
                    ->sortable(),

                Tables\Columns\TextColumn::make('paid_at')
                    ->label(__('admin.paid_at'))
                    ->dateTime()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('admin.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label(__('admin.add_invoice')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}