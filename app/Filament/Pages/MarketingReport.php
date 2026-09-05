<?php

namespace App\Filament\Pages;

use App\Models\ContactMessage;
use App\Models\Coupon;
use App\Models\FlashSale;
use App\Models\FreeShippingOffer;
use App\Models\NewsletterSubscriber;
use App\Models\ProductDiscount;
use App\Models\TrackingEventLog;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MarketingReport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static string $view = 'filament.pages.marketing-report';

    protected static ?int $navigationSort = 5;

    public ?string $date_from = null;

    public ?string $date_to = null;

    public ?string $tracking_status = null;

    public ?string $contact_status = null;

    public ?string $newsletter_status = null;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.reports_logs');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.marketing_report');
    }

    public function getTitle(): string
    {
        return __('admin.marketing_report');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('date_from')
                    ->label(__('admin.date_from'))
                    ->native(false)
                    ->live(),

                Forms\Components\DatePicker::make('date_to')
                    ->label(__('admin.date_to'))
                    ->native(false)
                    ->live(),

                Forms\Components\Select::make('tracking_status')
                    ->label(__('admin.tracking_status'))
                    ->options([
                        'success' => __('admin.success'),
                        'failed' => __('admin.failed'),
                    ])
                    ->searchable()
                    ->nullable()
                    ->live(),

                Forms\Components\Select::make('contact_status')
                    ->label(__('admin.contact_status'))
                    ->options(fn() => ContactMessage::statuses())
                    ->searchable()
                    ->nullable()
                    ->live(),

                Forms\Components\Select::make('newsletter_status')
                    ->label(__('admin.newsletter_status'))
                    ->options(fn() => NewsletterSubscriber::statuses())
                    ->searchable()
                    ->nullable()
                    ->live(),
            ])
            ->columns(5);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_csv')
                ->label(__('admin.export_csv'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(fn() => $this->exportCsv()),
        ];
    }

    public function getContactMessagesQuery()
    {
        return ContactMessage::query()
            ->when($this->date_from, fn($query) => $query->whereDate('created_at', '>=', $this->date_from))
            ->when($this->date_to, fn($query) => $query->whereDate('created_at', '<=', $this->date_to))
            ->when($this->contact_status, fn($query) => $query->where('status', $this->contact_status));
    }

    public function getNewsletterQuery()
    {
        return NewsletterSubscriber::query()
            ->when($this->date_from, fn($query) => $query->whereDate('created_at', '>=', $this->date_from))
            ->when($this->date_to, fn($query) => $query->whereDate('created_at', '<=', $this->date_to))
            ->when($this->newsletter_status, fn($query) => $query->where('status', $this->newsletter_status));
    }

    public function getTrackingLogsQuery()
    {
        return TrackingEventLog::query()
            ->when($this->date_from, fn($query) => $query->whereDate('created_at', '>=', $this->date_from))
            ->when($this->date_to, fn($query) => $query->whereDate('created_at', '<=', $this->date_to))
            ->when($this->tracking_status, fn($query) => $query->where('status', $this->tracking_status));
    }

    public function getSummary(): array
    {
        return [
            'active_coupons_count' => Coupon::query()
                ->where('is_active', true)
                ->count(),

            'coupons_used_count' => (int) Coupon::query()
                ->sum('used_count'),

            'active_flash_sales_count' => FlashSale::query()
                ->where('is_active', true)
                ->count(),

            'active_product_discounts_count' => ProductDiscount::query()
                ->where('is_active', true)
                ->count(),

            'active_free_shipping_offers_count' => FreeShippingOffer::query()
                ->where('is_active', true)
                ->count(),

            'contact_messages_count' => (clone $this->getContactMessagesQuery())->count(),

            'new_contact_messages_count' => (clone $this->getContactMessagesQuery())
                ->where('status', 'new')
                ->count(),

            'newsletter_subscribers_count' => (clone $this->getNewsletterQuery())
                ->where('status', 'subscribed')
                ->count(),

            'tracking_events_count' => (clone $this->getTrackingLogsQuery())->count(),

            'tracking_success_count' => (clone $this->getTrackingLogsQuery())
                ->where('status', 'success')
                ->count(),

            'tracking_failed_count' => (clone $this->getTrackingLogsQuery())
                ->where('status', 'failed')
                ->count(),
        ];
    }

    public function getCoupons()
    {
        return Coupon::query()
            ->latest()
            ->limit(20)
            ->get();
    }

    public function getContactMessages()
    {
        return $this->getContactMessagesQuery()
            ->latest()
            ->limit(20)
            ->get();
    }

    public function getNewsletterSubscribers()
    {
        return $this->getNewsletterQuery()
            ->latest()
            ->limit(20)
            ->get();
    }

    public function getTrackingLogs()
    {
        return $this->getTrackingLogsQuery()
            ->latest()
            ->limit(20)
            ->get();
    }

    public function exportCsv(): StreamedResponse
    {
        $fileName = 'marketing-report-' . now()->format('Y-m-d-H-i') . '.csv';

        $summary = $this->getSummary();

        return response()->streamDownload(function () use ($summary) {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['Metric', 'Value']);

            fputcsv($handle, ['Active Coupons', $summary['active_coupons_count']]);
            fputcsv($handle, ['Coupons Used Count', $summary['coupons_used_count']]);
            fputcsv($handle, ['Active Flash Sales', $summary['active_flash_sales_count']]);
            fputcsv($handle, ['Active Product Discounts', $summary['active_product_discounts_count']]);
            fputcsv($handle, ['Active Free Shipping Offers', $summary['active_free_shipping_offers_count']]);
            fputcsv($handle, ['Contact Messages', $summary['contact_messages_count']]);
            fputcsv($handle, ['New Contact Messages', $summary['new_contact_messages_count']]);
            fputcsv($handle, ['Newsletter Subscribers', $summary['newsletter_subscribers_count']]);
            fputcsv($handle, ['Tracking Events', $summary['tracking_events_count']]);
            fputcsv($handle, ['Tracking Success', $summary['tracking_success_count']]);
            fputcsv($handle, ['Tracking Failed', $summary['tracking_failed_count']]);

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}