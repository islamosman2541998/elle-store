<?php

namespace App\Filament\Resources\NotificationTemplateResource\Pages;

use App\Filament\Resources\NotificationTemplateResource;
use App\Mail\StoreNotificationMail;
use App\Services\Notifications\MailConfigurator;
use App\Services\Notifications\MessageBuilder;
use App\Services\Notifications\WhatsappGateway;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\HtmlString;

class EditNotificationTemplate extends EditRecord
{
    protected static string $resource = NotificationTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->previewAction(),
            $this->sendTestAction(),
            $this->resetAction(),
        ];
    }

    /**
     * Shows the message exactly as it will arrive, using the wording currently
     * typed into the form rather than what is saved.
     */
    private function previewAction(): Actions\Action
    {
        return Actions\Action::make('preview')
            ->label(__('admin.preview_message'))
            ->icon('heroicon-o-eye')
            ->color('info')
            ->modalHeading(__('admin.preview_message'))
            ->modalDescription(__('admin.preview_message_hint'))
            ->modalWidth('3xl')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('admin.close'))
            ->modalContent(fn () => new HtmlString(
                view('filament.notification-preview', [
                    'channel' => $this->record->channel,
                    'versions' => [
                        'ar' => $this->renderCurrent('ar'),
                        'en' => $this->renderCurrent('en'),
                    ],
                ])->render()
            ));
    }

    /** Fill the form's current text with sample order data. */
    private function renderCurrent(string $locale): array
    {
        $builder = app(MessageBuilder::class);
        $data = $this->data;

        // The sample values are translated too, so an English preview must not
        // come back filled with an Arabic name and city.
        $previous = app()->getLocale();
        app()->setLocale($locale);

        try {
            $tokens = $builder->sampleTokens();
        } finally {
            app()->setLocale($previous);
        }

        return [
            'subject' => $builder->replace((string) ($data["subject_{$locale}"] ?? ''), $tokens),
            'body' => $builder->replace((string) ($data["body_{$locale}"] ?? ''), $tokens),
            'button' => $builder->replace((string) ($data["button_label_{$locale}"] ?? ''), $tokens),
        ];
    }

    /** Sends the message being edited, to an address or number of your choosing. */
    private function sendTestAction(): Actions\Action
    {
        $isEmail = $this->record->channel === 'email';

        return Actions\Action::make('send_test')
            ->label(__('admin.send_test_message'))
            ->icon($isEmail ? 'heroicon-o-envelope' : 'heroicon-o-chat-bubble-left-right')
            ->color('success')
            ->form([
                Forms\Components\TextInput::make('to')
                    ->label(__('admin.send_to'))
                    ->required()
                    ->email($isEmail)
                    ->tel(! $isEmail)
                    ->default(fn () => $isEmail
                        ? (\App\Models\StoreSetting::current()->admin_notification_email ?: \App\Models\StoreSetting::current()->email)
                        : \App\Models\StoreSetting::current()->whatsapp),

                Forms\Components\Radio::make('locale')
                    ->label(__('admin.language'))
                    ->options([
                        'ar' => __('admin.arabic'),
                        'en' => __('admin.english'),
                    ])
                    ->default('ar')
                    ->inline(),
            ])
            ->action(fn (array $data) => $isEmail
                ? $this->sendTestEmail($data)
                : $this->sendTestWhatsapp($data));
    }

    private function sendTestEmail(array $data): void
    {
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

        $message = $this->renderCurrent($data['locale']);

        try {
            Mail::to($data['to'])->send(new StoreNotificationMail(
                $message['subject'] ?: __('admin.test_email_subject'),
                $message['body'],
                $message['button'] ? url('/') : null,
                $message['button'] ?: null,
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
    }

    private function sendTestWhatsapp(array $data): void
    {
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

        $message = $this->renderCurrent($data['locale']);
        $result = $gateway->send($data['to'], $message['body'], 'template_test');

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
    }

    private function resetAction(): Actions\Action
    {
        return Actions\Action::make('reset')
            ->label(__('admin.reset_to_default'))
            ->icon('heroicon-o-arrow-path')
            ->color('gray')
            ->requiresConfirmation()
            ->modalDescription(__('admin.reset_to_default_confirm'))
            ->action(function (): void {
                $this->record->resetToDefault();

                Notification::make()
                    ->title(__('admin.templates_reset'))
                    ->success()
                    ->send();

                $this->fillForm();
            });
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
