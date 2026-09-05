<?php

namespace App\Filament\Resources\StoreSettingResource\Pages;

use App\Filament\Resources\StoreSettingResource;
use App\Mail\StoreNotificationMail;
use App\Services\Notifications\MailConfigurator;
use App\Services\Notifications\WhatsappGateway;
use Illuminate\Support\Facades\Mail;
use App\Services\MetaCapiService;
use App\Services\TrackingEventService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditStoreSetting extends EditRecord
{
    protected static string $resource = StoreSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('send_test_email')
                ->label(__('admin.send_test_email'))
                ->icon('heroicon-o-envelope')
                ->color('success')
                ->form([
                    \Filament\Forms\Components\TextInput::make('to')
                        ->label(__('admin.send_to'))
                        ->email()
                        ->required()
                        ->default(fn () => $this->record->admin_notification_email ?: $this->record->email),
                ])
                ->action(function (array $data): void {
                    $config = app(MailConfigurator::class);
                    $config->reset();
                    $config->apply();

                    if (! $config->isConfigured()) {
                        Notification::make()
                            ->title(__('admin.mail_not_configured'))
                            ->body(__('admin.mail_not_configured_hint'))
                            ->danger()
                            ->persistent()
                            ->send();

                        return;
                    }

                    try {
                        Mail::to($data['to'])->send(new StoreNotificationMail(
                            __('admin.test_email_subject'),
                            __('admin.test_email_body')
                        ));

                        Notification::make()
                            ->title(__('admin.test_email_sent'))
                            ->body($data['to'])
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title(__('admin.test_email_failed'))
                            ->body($e->getMessage())
                            ->danger()
                            ->persistent()
                            ->send();
                    }
                }),

            Actions\Action::make('send_test_whatsapp')
                ->label(__('admin.send_test_whatsapp'))
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('success')
                ->form([
                    \Filament\Forms\Components\TextInput::make('to')
                        ->label(__('admin.send_to'))
                        ->tel()
                        ->required()
                        ->default(fn () => $this->record->whatsapp ?: $this->record->phone),
                ])
                ->action(function (array $data): void {
                    \App\Models\StoreSetting::flushCurrent();

                    $gateway = app(WhatsappGateway::class);

                    if (! $gateway->isConfigured()) {
                        Notification::make()
                            ->title(__('admin.whatsapp_not_configured'))
                            ->body(__('admin.whatsapp_missing_fields') . ': ' . implode(', ', $gateway->missingFields()))
                            ->danger()
                            ->persistent()
                            ->send();

                        return;
                    }

                    $result = $gateway->send($data['to'], __('admin.test_whatsapp_body'), 'test');

                    $result['ok']
                        ? Notification::make()
                            ->title(__('admin.test_whatsapp_sent'))
                            ->body($gateway->normalisePhone($data['to']))
                            ->success()
                            ->send()
                        : Notification::make()
                            ->title(__('admin.test_whatsapp_failed'))
                            ->body($result['error'])
                            ->danger()
                            ->persistent()
                            ->send();
                }),
            Actions\Action::make('send_test_meta_capi_event')
                ->label(__('admin.send_test_meta_capi_event'))
                ->icon('heroicon-o-paper-airplane')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading(__('admin.send_test_meta_capi_event'))
                ->modalDescription(__('admin.send_test_meta_capi_event_confirmation'))
                ->action(function (): void {
                    $eventId = app(TrackingEventService::class)->eventId('test_lead');

                    $payload = [
                        'currency' => $this->record->currency_code ?? 'EGP',
                        'value' => 1,
                        'content_name' => 'Test Meta CAPI Event',
                    ];

                    app(MetaCapiService::class)->sendEvent(
                        eventName: 'Lead',
                        payload: $payload,
                        eventId: $eventId
                    );

                    Notification::make()
                        ->title(__('admin.test_meta_capi_event_sent'))
                        ->body(__('admin.check_tracking_event_logs'))
                        ->success()
                        ->send();
                }),

            Actions\ViewAction::make()
                ->label(__('admin.view')),
        ];
    }
}