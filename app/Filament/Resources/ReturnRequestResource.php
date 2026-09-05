<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReturnRequestResource\Pages;
use App\Models\Order;
use App\Models\ReturnRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use App\Filament\Resources\ReturnRequestResource\RelationManagers\ItemsRelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ReturnRequestResource extends Resource
{
    protected static ?string $model = ReturnRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-uturn-left';

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.sales');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.returns');
    }

    public static function getModelLabel(): string
    {
        return __('admin.return_request');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.returns');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Return Request Tabs')
                    ->columnSpanFull()
                    ->tabs([
                        Forms\Components\Tabs\Tab::make(__('admin.return_information'))
                            ->schema([
                                Forms\Components\Section::make(__('admin.main_information'))
                                    ->schema([
                                        Forms\Components\TextInput::make('return_number')
                                            ->label(__('admin.return_number'))
                                            ->required()
                                            ->unique(ignoreRecord: true)
                                            ->default(fn () => 'RET-' . now()->format('YmdHis')),

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
                                            }),

                                        Forms\Components\Hidden::make('customer_id'),

                                        Forms\Components\Select::make('status')
                                            ->label(__('admin.return_status'))
                                            ->options([
                                                'requested' => __('admin.return_requested'),
                                                'approved' => __('admin.return_approved'),
                                                'rejected' => __('admin.return_rejected'),
                                                'received' => __('admin.return_received'),
                                                'refunded' => __('admin.return_refunded'),
                                                'cancelled' => __('admin.return_cancelled'),
                                            ])
                                            ->required()
                                            ->default('requested')
                                            ->live()
                                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                                if ($state === 'approved') {
                                                    $set('approved_at', now());
                                                }

                                                if ($state === 'rejected') {
                                                    $set('rejected_at', now());
                                                }

                                                if ($state === 'received') {
                                                    $set('received_at', now());
                                                }

                                                if ($state === 'refunded') {
                                                    $set('refunded_at', now());
                                                }
                                            }),

                                        Forms\Components\Select::make('reason')
                                            ->label(__('admin.return_reason'))
                                            ->options([
                                                'damaged' => __('admin.return_reason_damaged'),
                                                'wrong_item' => __('admin.return_reason_wrong_item'),
                                                'changed_mind' => __('admin.return_reason_changed_mind'),
                                                'size_issue' => __('admin.return_reason_size_issue'),
                                                'other' => __('admin.return_reason_other'),
                                            ])
                                            ->nullable(),

                                        Forms\Components\TextInput::make('refund_total')
                                            ->label(__('admin.refund_total'))
                                            ->numeric()
                                            ->default(0)
                                            ->prefix('EGP'),

                                        Forms\Components\DateTimePicker::make('approved_at')
                                            ->label(__('admin.approved_at'))
                                            ->seconds(false),

                                        Forms\Components\DateTimePicker::make('rejected_at')
                                            ->label(__('admin.rejected_at'))
                                            ->seconds(false),

                                        Forms\Components\DateTimePicker::make('received_at')
                                            ->label(__('admin.received_at'))
                                            ->seconds(false),

                                        Forms\Components\DateTimePicker::make('refunded_at')
                                            ->label(__('admin.refunded_at'))
                                            ->seconds(false),
                                    ])
                                    ->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make(__('admin.notes'))
                            ->schema([
                                Forms\Components\Textarea::make('customer_notes')
                                    ->label(__('admin.customer_notes'))
                                    ->rows(4),

                                Forms\Components\Textarea::make('admin_notes')
                                    ->label(__('admin.admin_notes'))
                                    ->rows(4),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['order', 'customer'])->withCount('items'))
            ->columns([
                Tables\Columns\TextColumn::make('return_number')
                    ->label(__('admin.return_number'))
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

                Tables\Columns\TextColumn::make('items_count')
                    ->label(__('admin.items_count'))
                    ->badge(),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('admin.return_status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => __('admin.return_' . $state))
                    ->color(fn ($state) => match ($state) {
                        'requested' => 'gray',
                        'approved' => 'info',
                        'rejected' => 'danger',
                        'received' => 'warning',
                        'refunded' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('reason')
                    ->label(__('admin.return_reason'))
                    ->formatStateUsing(fn ($state) => $state ? __('admin.return_reason_' . $state) : '-')
                    ->badge()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('refund_total')
                    ->label(__('admin.refund_total'))
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2, '.', ',') . ' EGP')
                    ->badge()
                    ->color('success')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('admin.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('admin.return_status'))
                    ->options([
                        'requested' => __('admin.return_requested'),
                        'approved' => __('admin.return_approved'),
                        'rejected' => __('admin.return_rejected'),
                        'received' => __('admin.return_received'),
                        'refunded' => __('admin.return_refunded'),
                        'cancelled' => __('admin.return_cancelled'),
                    ]),

                Tables\Filters\SelectFilter::make('reason')
                    ->label(__('admin.return_reason'))
                    ->options([
                        'damaged' => __('admin.return_reason_damaged'),
                        'wrong_item' => __('admin.return_reason_wrong_item'),
                        'changed_mind' => __('admin.return_reason_changed_mind'),
                        'size_issue' => __('admin.return_reason_size_issue'),
                        'other' => __('admin.return_reason_other'),
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
                Infolists\Components\Section::make(__('admin.return_overview'))
                    ->schema([
                        Infolists\Components\TextEntry::make('return_number')
                            ->label(__('admin.return_number'))
                            ->copyable()
                            ->weight('bold'),

                        Infolists\Components\TextEntry::make('order.order_number')
                            ->label(__('admin.order_number'))
                            ->copyable(),

                        Infolists\Components\TextEntry::make('order.customer_name')
                            ->label(__('admin.customer_name'))
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('status')
                            ->label(__('admin.return_status'))
                            ->badge()
                            ->formatStateUsing(fn ($state) => __('admin.return_' . $state)),

                        Infolists\Components\TextEntry::make('reason')
                            ->label(__('admin.return_reason'))
                            ->formatStateUsing(fn ($state) => $state ? __('admin.return_reason_' . $state) : '-')
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('refund_total')
                            ->label(__('admin.refund_total'))
                            ->formatStateUsing(fn ($state) => number_format((float) $state, 2, '.', ',') . ' EGP')
                            ->badge()
                            ->color('success'),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make(__('admin.return_dates'))
                    ->schema([
                        Infolists\Components\TextEntry::make('approved_at')
                            ->label(__('admin.approved_at'))
                            ->dateTime()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('rejected_at')
                            ->label(__('admin.rejected_at'))
                            ->dateTime()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('received_at')
                            ->label(__('admin.received_at'))
                            ->dateTime()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('refunded_at')
                            ->label(__('admin.refunded_at'))
                            ->dateTime()
                            ->placeholder('-'),
                    ])
                    ->columns(4)
                    ->collapsible(),

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
    ];
}
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReturnRequests::route('/'),
            'create' => Pages\CreateReturnRequest::route('/create'),
            'view' => Pages\ViewReturnRequest::route('/{record}'),
            'edit' => Pages\EditReturnRequest::route('/{record}/edit'),
        ];
    }
}