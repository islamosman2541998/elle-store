<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContactMessageResource\Pages;
use App\Models\ContactMessage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ContactMessageResource extends Resource
{
    protected static ?string $model = ContactMessage::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.content_management');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.contact_messages');
    }

    public static function getModelLabel(): string
    {
        return __('admin.contact_message');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.contact_messages');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('admin.contact_message_information'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('admin.name'))
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label(__('admin.email'))
                            ->email()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('phone')
                            ->label(__('admin.phone'))
                            ->maxLength(255),

                        Forms\Components\TextInput::make('subject')
                            ->label(__('admin.subject'))
                            ->maxLength(255),

                        Forms\Components\Textarea::make('message')
                            ->label(__('admin.message'))
                            ->required()
                            ->rows(6)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('status')
                            ->label(__('admin.status'))
                            ->options(fn() => ContactMessage::statuses())
                            ->required()
                            ->default('new'),

                        Forms\Components\Textarea::make('admin_note')
                            ->label(__('admin.admin_note'))
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make(__('admin.status_dates'))
                    ->schema([
                        Forms\Components\DateTimePicker::make('read_at')
                            ->label(__('admin.read_at'))
                            ->seconds(false),

                        Forms\Components\DateTimePicker::make('replied_at')
                            ->label(__('admin.replied_at'))
                            ->seconds(false),

                        Forms\Components\DateTimePicker::make('archived_at')
                            ->label(__('admin.archived_at'))
                            ->seconds(false),
                    ])
                    ->columns(3)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('admin.name'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label(__('admin.email'))
                    ->searchable()
                    ->copyable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('phone')
                    ->label(__('admin.phone'))
                    ->searchable()
                    ->copyable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('subject')
                    ->label(__('admin.subject'))
                    ->searchable()
                    ->limit(35)
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('admin.status'))
                    ->badge()
                    ->formatStateUsing(fn($state) => ContactMessage::statuses()[$state] ?? $state)
                    ->color(fn($state) => match ($state) {
                        'new' => 'warning',
                        'read' => 'info',
                        'replied' => 'success',
                        'archived' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('admin.created_at'))
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('admin.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('admin.status'))
                    ->options(fn() => ContactMessage::statuses()),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('mark_as_read')
                    ->label(__('admin.mark_as_read'))
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->visible(fn($record) => $record->status !== 'read')
                    ->action(function ($record) {
                        $record->markAsRead();
                    }),

                Tables\Actions\Action::make('mark_as_replied')
                    ->label(__('admin.mark_as_replied'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn($record) => $record->status !== 'replied')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->markAsReplied();
                    }),

                Tables\Actions\Action::make('archive')
                    ->label(__('admin.archive'))
                    ->icon('heroicon-o-archive-box')
                    ->color('gray')
                    ->visible(fn($record) => $record->status !== 'archived')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->archive();
                    }),

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
                    Tables\Actions\BulkAction::make('mark_selected_as_read')
                        ->label(__('admin.mark_selected_as_read'))
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->requiresConfirmation()
                        ->action(fn($records) => $records->each->markAsRead()),

                    Tables\Actions\BulkAction::make('archive_selected')
                        ->label(__('admin.archive_selected'))
                        ->icon('heroicon-o-archive-box')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->action(fn($records) => $records->each->archive()),

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
                Infolists\Components\Section::make(__('admin.contact_message_information'))
                    ->schema([
                        Infolists\Components\TextEntry::make('name')
                            ->label(__('admin.name')),

                        Infolists\Components\TextEntry::make('email')
                            ->label(__('admin.email'))
                            ->copyable()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('phone')
                            ->label(__('admin.phone'))
                            ->copyable()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('subject')
                            ->label(__('admin.subject'))
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('status')
                            ->label(__('admin.status'))
                            ->badge()
                            ->formatStateUsing(fn($state) => ContactMessage::statuses()[$state] ?? $state)
                            ->color(fn($state) => match ($state) {
                                'new' => 'warning',
                                'read' => 'info',
                                'replied' => 'success',
                                'archived' => 'gray',
                                default => 'gray',
                            }),

                        Infolists\Components\TextEntry::make('message')
                            ->label(__('admin.message'))
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('admin_note')
                            ->label(__('admin.admin_note'))
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make(__('admin.status_dates'))
                    ->schema([
                        Infolists\Components\TextEntry::make('read_at')
                            ->label(__('admin.read_at'))
                            ->dateTime()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('replied_at')
                            ->label(__('admin.replied_at'))
                            ->dateTime()
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('archived_at')
                            ->label(__('admin.archived_at'))
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContactMessages::route('/'),
            'create' => Pages\CreateContactMessage::route('/create'),
            'view' => Pages\ViewContactMessage::route('/{record}'),
            'edit' => Pages\EditContactMessage::route('/{record}/edit'),
        ];
    }
}