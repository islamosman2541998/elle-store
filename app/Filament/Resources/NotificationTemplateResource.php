<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NotificationTemplateResource\Pages;
use App\Models\NotificationTemplate;
use App\Services\Notifications\MessageBuilder;
use App\Services\Notifications\NotificationTemplates;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

/**
 * The wording of every message the store sends, over both channels.
 *
 * The rows are a fixed set - one per event and channel - so the screen only
 * edits; it never creates or deletes.
 */
class NotificationTemplateResource extends Resource
{
    protected static ?string $model = NotificationTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-bottom-center-text';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.system_settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.notification_templates');
    }

    public static function getModelLabel(): string
    {
        return __('admin.notification_template');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.notification_templates');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make(__('admin.notification_template'))
                ->description(__('admin.notification_template_hint'))
                ->schema([
                    Forms\Components\Placeholder::make('event_label')
                        ->label(__('admin.notification_event'))
                        ->content(fn (?NotificationTemplate $record) => $record
                            ? __('admin.notification_event_' . $record->event)
                            : '—'),

                    Forms\Components\Placeholder::make('channel_label')
                        ->label(__('admin.notification_channel'))
                        ->content(fn (?NotificationTemplate $record) => $record
                            ? __('admin.notification_channel_' . $record->channel)
                            : '—'),

                    Forms\Components\Placeholder::make('audience_label')
                        ->label(__('admin.notification_audience'))
                        ->content(fn (?NotificationTemplate $record) => $record
                            ? __('admin.notification_audience_' . NotificationTemplates::audience($record->event))
                            : '—'),

                    Forms\Components\Toggle::make('is_active')
                        ->label(__('admin.notification_template_active'))
                        ->helperText(__('admin.notification_template_active_hint'))
                        ->default(true),
                ])
                ->columns(3),

            Forms\Components\Section::make(__('admin.available_placeholders'))
                ->description(__('admin.available_placeholders_hint'))
                ->collapsible()
                ->schema([
                    Forms\Components\Placeholder::make('placeholder_reference')
                        ->hiddenLabel()
                        ->columnSpanFull()
                        ->content(fn (?NotificationTemplate $record) => new HtmlString(
                            view('filament.notification-placeholders', [
                                'tokens' => $record
                                    ? NotificationTemplates::placeholders($record->event)
                                    : NotificationTemplates::ORDER_PLACEHOLDERS,
                                'samples' => app(MessageBuilder::class)->sampleTokens(),
                            ])->render()
                        )),
                ]),

            Forms\Components\Tabs::make('content')
                ->columnSpanFull()
                ->tabs([
                    Forms\Components\Tabs\Tab::make(__('admin.arabic'))
                        ->schema(static::localeFields('ar')),

                    Forms\Components\Tabs\Tab::make(__('admin.english'))
                        ->schema(static::localeFields('en')),
                ]),
        ]);
    }

    /** The editable fields for one language; WhatsApp has no subject or button. */
    private static function localeFields(string $locale): array
    {
        $isEmail = fn (?NotificationTemplate $record) => $record?->channel === 'email';

        return [
            Forms\Components\TextInput::make("subject_{$locale}")
                ->label(__('admin.message_subject'))
                ->maxLength(255)
                ->visible($isEmail)
                ->helperText(__('admin.message_subject_hint')),

            Forms\Components\Textarea::make("body_{$locale}")
                ->label(__('admin.message_body'))
                ->rows(14)
                ->required()
                ->helperText(__('admin.message_body_hint')),

            Forms\Components\TextInput::make("button_label_{$locale}")
                ->label(__('admin.message_button_label'))
                ->maxLength(60)
                ->visible($isEmail)
                ->helperText(__('admin.message_button_label_hint')),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('event')
            ->columns([
                Tables\Columns\TextColumn::make('event')
                    ->label(__('admin.notification_event'))
                    ->formatStateUsing(fn (string $state) => __('admin.notification_event_' . $state))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('channel')
                    ->label(__('admin.notification_channel'))
                    ->badge()
                    ->color(fn (string $state) => $state === 'whatsapp' ? 'success' : 'info')
                    ->formatStateUsing(fn (string $state) => __('admin.notification_channel_' . $state)),

                Tables\Columns\TextColumn::make('audience')
                    ->label(__('admin.notification_audience'))
                    ->badge()
                    ->state(fn (NotificationTemplate $record) => NotificationTemplates::audience($record->event))
                    ->color(fn (string $state) => $state === 'admin' ? 'warning' : 'gray')
                    ->formatStateUsing(fn (string $state) => __('admin.notification_audience_' . $state)),

                Tables\Columns\TextColumn::make('body_ar')
                    ->label(__('admin.message_body'))
                    ->limit(60)
                    ->wrap()
                    ->toggleable(),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label(__('admin.notification_template_active')),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('admin.updated_at'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('channel')
                    ->label(__('admin.notification_channel'))
                    ->options([
                        'email' => __('admin.notification_channel_email'),
                        'whatsapp' => __('admin.notification_channel_whatsapp'),
                    ]),

                Tables\Filters\SelectFilter::make('event')
                    ->label(__('admin.notification_event'))
                    ->options(fn () => collect(NotificationTemplates::eventKeys())
                        ->mapWithKeys(fn (string $event) => [$event => __('admin.notification_event_' . $event)])
                        ->all()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([])
            ->paginated(false);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNotificationTemplates::route('/'),
            'edit' => Pages\EditNotificationTemplate::route('/{record}/edit'),
        ];
    }
}
