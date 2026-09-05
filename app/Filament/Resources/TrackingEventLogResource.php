<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TrackingEventLogResource\Pages;
use App\Models\TrackingEventLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TrackingEventLogResource extends Resource
{
    protected static ?string $model = TrackingEventLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.system_settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.tracking_event_logs');
    }

    public static function getModelLabel(): string
    {
        return __('admin.tracking_event_log');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.tracking_event_logs');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->with(['order', 'customer']))
            ->columns([
                Tables\Columns\TextColumn::make('event_name')
                    ->label(__('admin.event_name'))
                    ->badge()
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('event_id')
                    ->label(__('admin.event_id'))
                    ->copyable()
                    ->searchable()
                    ->limit(25)
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('platform')
                    ->label(__('admin.platform'))
                    ->badge()
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('source')
                    ->label(__('admin.source'))
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('admin.status'))
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'success' => 'success',
                        'failed' => 'danger',
                        'pending' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('order.order_number')
                    ->label(__('admin.order_number'))
                    ->searchable()
                    ->copyable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label(__('admin.customer'))
                    ->searchable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('sent_at')
                    ->label(__('admin.sent_at'))
                    ->dateTime()
                    ->sortable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('admin.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('event_name')
                    ->label(__('admin.event_name'))
                    ->options([
                        'PageView' => 'PageView',
                        'ViewContent' => 'ViewContent',
                        'AddToCart' => 'AddToCart',
                        'InitiateCheckout' => 'InitiateCheckout',
                        'Purchase' => 'Purchase',
                        'Search' => 'Search',
                        'Lead' => 'Lead',
                    ]),

                Tables\Filters\SelectFilter::make('platform')
                    ->label(__('admin.platform'))
                    ->options([
                        'meta_capi' => 'Meta CAPI',
                        'meta_pixel' => 'Meta Pixel',
                        'gtm' => 'Google Tag Manager',
                        'ga4' => 'GA4',
                        'tiktok' => 'TikTok',
                        'snapchat' => 'Snapchat',
                        'pinterest' => 'Pinterest',
                        'linkedin' => 'LinkedIn',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->label(__('admin.status'))
                    ->options([
                        'pending' => __('admin.pending'),
                        'success' => __('admin.success'),
                        'failed' => __('admin.failed'),
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label(__('admin.view')),

                Tables\Actions\DeleteAction::make()
                    ->label(__('admin.delete')),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->label(__('admin.delete_selected')),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make(__('admin.event_information'))
                    ->schema([
                        Infolists\Components\TextEntry::make('event_name')
                            ->label(__('admin.event_name'))
                            ->badge(),

                        Infolists\Components\TextEntry::make('event_id')
                            ->label(__('admin.event_id'))
                            ->copyable()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('platform')
                            ->label(__('admin.platform'))
                            ->badge()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('source')
                            ->label(__('admin.source'))
                            ->badge(),

                        Infolists\Components\TextEntry::make('status')
                            ->label(__('admin.status'))
                            ->badge()
                            ->color(fn($state) => match ($state) {
                                'success' => 'success',
                                'failed' => 'danger',
                                'pending' => 'warning',
                                default => 'gray',
                            }),

                        Infolists\Components\TextEntry::make('sent_at')
                            ->label(__('admin.sent_at'))
                            ->dateTime()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('ip_address')
                            ->label(__('admin.ip_address'))
                            ->copyable()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('user_agent')
                            ->label(__('admin.user_agent'))
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make(__('admin.related_data'))
                    ->schema([
                        Infolists\Components\TextEntry::make('order.order_number')
                            ->label(__('admin.order_number'))
                            ->copyable()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('customer.name')
                            ->label(__('admin.customer'))
                            ->placeholder('-'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Infolists\Components\Section::make(__('admin.payload'))
                    ->schema([
                        Infolists\Components\TextEntry::make('payload')
                            ->label(__('admin.payload'))
                            ->formatStateUsing(fn($state) => $state ? json_encode($state, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : '-')
                            ->copyable()
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Infolists\Components\Section::make(__('admin.response'))
                    ->schema([
                        Infolists\Components\TextEntry::make('response')
                            ->label(__('admin.response'))
                            ->formatStateUsing(fn($state) => $state ? json_encode($state, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : '-')
                            ->copyable()
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('error_message')
                            ->label(__('admin.error_message'))
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTrackingEventLogs::route('/'),
            'view' => Pages\ViewTrackingEventLog::route('/{record}'),
        ];
    }
}