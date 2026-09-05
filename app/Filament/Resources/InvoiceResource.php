<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Models\Invoice;
use App\Models\Order;
use App\Services\InvoiceSettingsService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.sales');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.invoices');
    }

    public static function getModelLabel(): string
    {
        return __('admin.invoice');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.invoices');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Invoice Tabs')
                    ->columnSpanFull()
                    ->tabs([
                        Forms\Components\Tabs\Tab::make(__('admin.invoice_information'))
                            ->schema([
                                Forms\Components\Section::make(__('admin.main_information'))
                                    ->schema([
                                        Forms\Components\TextInput::make('invoice_number')
                                            ->label(__('admin.invoice_number'))
                                            ->required()
                                            ->unique(ignoreRecord: true)
                                            ->default(fn () => 'INV-' . now()->format('YmdHis')),

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

                                                $set('customer_id', $order->customer_id);
                                                $set('customer_name', $order->customer_name);
                                                $set('customer_email', $order->customer_email);
                                                $set('customer_phone', $order->customer_phone);

                                                $set('subtotal', $order->subtotal);
                                                $set('discount_total', $order->discount_total);
                                                $set('shipping_total', $order->shipping_total);
                                                $set('tax_total', $order->tax_total);
                                                $set('grand_total', $order->grand_total);

                                                $set('status', $order->payment_status === 'paid' ? 'paid' : 'unpaid');
                                                $set('issued_at', now());

                                                if ($order->payment_status === 'paid') {
                                                    $set('paid_at', now());
                                                }
                                            }),

                                        Forms\Components\Hidden::make('customer_id'),

                                        Forms\Components\Select::make('status')
                                            ->label(__('admin.invoice_status'))
                                            ->options([
                                                'unpaid' => __('admin.invoice_unpaid'),
                                                'paid' => __('admin.invoice_paid'),
                                                'cancelled' => __('admin.invoice_cancelled'),
                                            ])
                                            ->required()
                                            ->default('unpaid')
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
                                            ->tel()
                                            ->maxLength(255),
                                    ])
                                    ->columns(3),
                            ]),

                        Forms\Components\Tabs\Tab::make(__('admin.invoice_totals'))
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

                                        Forms\Components\TextInput::make('grand_total')
                                            ->label(__('admin.grand_total'))
                                            ->numeric()
                                            ->default(0)
                                            ->prefix('EGP'),
                                    ])
                                    ->columns(3),
                            ]),

                        Forms\Components\Tabs\Tab::make(__('admin.notes'))
                            ->schema([
                                Forms\Components\Textarea::make('notes')
                                    ->label(__('admin.notes'))
                                    ->rows(4)
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['order', 'customer']))
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label(__('admin.invoice_number'))
                    ->searchable()
                    ->copyable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('order.order_number')
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
                    ->copyable()
                    ->placeholder('-'),

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
                    ->color('success')
                    ->sortable(),

                Tables\Columns\TextColumn::make('issued_at')
                    ->label(__('admin.issued_at'))
                    ->dateTime()
                    ->placeholder('-')
                    ->sortable(),

                Tables\Columns\TextColumn::make('paid_at')
                    ->label(__('admin.paid_at'))
                    ->dateTime()
                    ->placeholder('-')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('admin.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('admin.invoice_status'))
                    ->options([
                        'unpaid' => __('admin.invoice_unpaid'),
                        'paid' => __('admin.invoice_paid'),
                        'cancelled' => __('admin.invoice_cancelled'),
                    ]),

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
                Infolists\Components\Section::make(__('admin.invoice_overview'))
                    ->schema([
                        Infolists\Components\TextEntry::make('invoice_number')
                            ->label(__('admin.invoice_number'))
                            ->copyable()
                            ->weight('bold'),

                        Infolists\Components\TextEntry::make('order.order_number')
                            ->label(__('admin.order_number'))
                            ->copyable(),

                        Infolists\Components\TextEntry::make('customer_name')
                            ->label(__('admin.customer_name')),

                        Infolists\Components\TextEntry::make('customer_phone')
                            ->label(__('admin.customer_phone'))
                            ->copyable()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('customer_email')
                            ->label(__('admin.customer_email'))
                            ->copyable()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('status')
                            ->label(__('admin.invoice_status'))
                            ->badge()
                            ->formatStateUsing(fn ($state) => __('admin.invoice_' . $state)),

                        Infolists\Components\TextEntry::make('order.payment_status')
                            ->label(__('admin.payment_status'))
                            ->badge()
                            ->formatStateUsing(fn ($state) => $state ? __('admin.payment_' . $state) : '-')
                            ->visible(fn () => app(InvoiceSettingsService::class)->shouldShowPaymentStatus()),

                        Infolists\Components\TextEntry::make('issued_at')
                            ->label(__('admin.issued_at'))
                            ->dateTime()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('paid_at')
                            ->label(__('admin.paid_at'))
                            ->dateTime()
                            ->placeholder('-'),
                    ])
                    ->columns(4),

                Infolists\Components\Section::make(__('admin.invoice_seller_information'))
                    ->schema([
                        Infolists\Components\TextEntry::make('seller_name_setting')
                            ->label(__('admin.invoice_seller_name'))
                            ->state(fn () => app(InvoiceSettingsService::class)->sellerName())
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('tax_number_setting')
                            ->label(__('admin.tax_number'))
                            ->state(fn () => app(InvoiceSettingsService::class)->taxNumber())
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('commercial_registration_number_setting')
                            ->label(__('admin.commercial_registration_number'))
                            ->state(fn () => app(InvoiceSettingsService::class)->commercialRegistrationNumber())
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('invoice_address_setting')
                            ->label(__('admin.invoice_address'))
                            ->state(fn () => app(InvoiceSettingsService::class)->invoiceAddress())
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Infolists\Components\Section::make(__('admin.invoice_totals'))
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
                            ->formatStateUsing(fn ($state) => number_format((float) $state, 2, '.', ',') . ' EGP')
                            ->visible(fn () => app(InvoiceSettingsService::class)->shouldShowTax()),

                        Infolists\Components\TextEntry::make('grand_total')
                            ->label(__('admin.grand_total'))
                            ->formatStateUsing(fn ($state) => number_format((float) $state, 2, '.', ',') . ' EGP')
                            ->weight('bold'),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make(__('admin.notes'))
                    ->schema([
                        Infolists\Components\TextEntry::make('notes')
                            ->label(__('admin.notes'))
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Infolists\Components\Section::make(__('admin.invoice_notes_and_terms'))
                    ->schema([
                        Infolists\Components\TextEntry::make('invoice_notes_setting')
                            ->label(__('admin.invoice_notes'))
                            ->state(fn () => app(InvoiceSettingsService::class)->invoiceNotes())
                            ->placeholder('-')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('invoice_terms_setting')
                            ->label(__('admin.invoice_terms'))
                            ->state(fn () => app(InvoiceSettingsService::class)->invoiceTerms())
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

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'view' => Pages\ViewInvoice::route('/{record}'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }
}