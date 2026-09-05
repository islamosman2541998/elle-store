<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NewsletterSubscriberResource\Pages;
use App\Models\NewsletterSubscriber;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class NewsletterSubscriberResource extends Resource
{
    protected static ?string $model = NewsletterSubscriber::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope-open';

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.content_management');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.newsletter_subscribers');
    }

    public static function getModelLabel(): string
    {
        return __('admin.newsletter_subscriber');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.newsletter_subscribers');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('admin.newsletter_subscriber_information'))
                    ->schema([
                        Forms\Components\TextInput::make('email')
                            ->label(__('admin.email'))
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Forms\Components\TextInput::make('name')
                            ->label(__('admin.name'))
                            ->maxLength(255),

                        Forms\Components\Select::make('status')
                            ->label(__('admin.status'))
                            ->options(fn () => NewsletterSubscriber::statuses())
                            ->required()
                            ->default('subscribed'),

                        Forms\Components\TextInput::make('source')
                            ->label(__('admin.source'))
                            ->maxLength(255)
                            ->placeholder('footer, popup, checkout'),

                        Forms\Components\DateTimePicker::make('subscribed_at')
                            ->label(__('admin.subscribed_at'))
                            ->seconds(false)
                            ->default(now()),

                        Forms\Components\DateTimePicker::make('unsubscribed_at')
                            ->label(__('admin.unsubscribed_at'))
                            ->seconds(false),

                        Forms\Components\Textarea::make('notes')
                            ->label(__('admin.notes'))
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]))
            ->columns([
                Tables\Columns\TextColumn::make('email')
                    ->label(__('admin.email'))
                    ->searchable()
                    ->copyable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label(__('admin.name'))
                    ->searchable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('admin.status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => NewsletterSubscriber::statuses()[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'subscribed' => 'success',
                        'unsubscribed' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('source')
                    ->label(__('admin.source'))
                    ->badge()
                    ->placeholder('-')
                    ->sortable(),

                Tables\Columns\TextColumn::make('subscribed_at')
                    ->label(__('admin.subscribed_at'))
                    ->dateTime()
                    ->placeholder('-')
                    ->sortable(),

                Tables\Columns\TextColumn::make('unsubscribed_at')
                    ->label(__('admin.unsubscribed_at'))
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
                    ->label(__('admin.status'))
                    ->options(fn () => NewsletterSubscriber::statuses()),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('subscribe')
                    ->label(__('admin.subscribe'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->status !== 'subscribed')
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->subscribe()),

                Tables\Actions\Action::make('unsubscribe')
                    ->label(__('admin.unsubscribe'))
                    ->icon('heroicon-o-x-circle')
                    ->color('warning')
                    ->visible(fn ($record) => $record->status !== 'unsubscribed')
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->unsubscribe()),

                Tables\Actions\ViewAction::make()
                    ->label(__('admin.view')),

                Tables\Actions\EditAction::make()
                    ->label(__('admin.edit')),

                Tables\Actions\DeleteAction::make()
                    ->label(__('admin.delete')),

                Tables\Actions\RestoreAction::make()
                    ->label(__('admin.restore')),

                Tables\Actions\ForceDeleteAction::make()
                    ->label(__('admin.force_delete')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('subscribe_selected')
                        ->label(__('admin.subscribe_selected'))
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->subscribe()),

                    Tables\Actions\BulkAction::make('unsubscribe_selected')
                        ->label(__('admin.unsubscribe_selected'))
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->unsubscribe()),

                    Tables\Actions\DeleteBulkAction::make()
                        ->label(__('admin.delete_selected')),

                    Tables\Actions\RestoreBulkAction::make()
                        ->label(__('admin.restore_selected')),

                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->label(__('admin.force_delete_selected')),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make(__('admin.newsletter_subscriber_information'))
                    ->schema([
                        Infolists\Components\TextEntry::make('email')
                            ->label(__('admin.email'))
                            ->copyable(),

                        Infolists\Components\TextEntry::make('name')
                            ->label(__('admin.name'))
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('status')
                            ->label(__('admin.status'))
                            ->badge()
                            ->formatStateUsing(fn ($state) => NewsletterSubscriber::statuses()[$state] ?? $state)
                            ->color(fn ($state) => match ($state) {
                                'subscribed' => 'success',
                                'unsubscribed' => 'gray',
                                default => 'gray',
                            }),

                        Infolists\Components\TextEntry::make('source')
                            ->label(__('admin.source'))
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('subscribed_at')
                            ->label(__('admin.subscribed_at'))
                            ->dateTime()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('unsubscribed_at')
                            ->label(__('admin.unsubscribed_at'))
                            ->dateTime()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('notes')
                            ->label(__('admin.notes'))
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNewsletterSubscribers::route('/'),
            'create' => Pages\CreateNewsletterSubscriber::route('/create'),
            'view' => Pages\ViewNewsletterSubscriber::route('/{record}'),
            'edit' => Pages\EditNewsletterSubscriber::route('/{record}/edit'),
        ];
    }
}