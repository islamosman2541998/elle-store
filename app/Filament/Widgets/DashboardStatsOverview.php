<?php

namespace App\Filament\Widgets;

use App\Models\BlogPost;
use App\Models\ContactMessage;
use App\Models\Customer;
use App\Models\NewsletterSubscriber;
use App\Models\Order;
use App\Models\Page;
use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $ordersCount = Order::query()->count();

        $ordersTotal = (float) Order::query()->sum('grand_total');

        $pendingOrdersCount = Order::query()
            ->where('status', 'pending')
            ->count();

        $productsCount = Product::query()->count();

        $lowStockProductsCount = Product::query()
            ->where('manage_stock', true)
            ->whereColumn('stock_quantity', '<=', 'low_stock_alert')
            ->count();

        $customersCount = Customer::query()->count();

        $newMessagesCount = ContactMessage::query()
            ->where('status', 'new')
            ->count();

        $newsletterSubscribersCount = NewsletterSubscriber::query()
            ->where('status', 'subscribed')
            ->count();

        $pagesCount = Page::query()->count();

        $publishedBlogPostsCount = BlogPost::query()
            ->where('status', 'published')
            ->count();

        return [
            Stat::make(__('admin.total_orders'), number_format($ordersCount))
                ->description(__('admin.total_orders_description'))
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('primary'),

            Stat::make(__('admin.total_sales'), number_format($ordersTotal, 2) . ' EGP')
                ->description(__('admin.total_sales_description'))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make(__('admin.pending_orders'), number_format($pendingOrdersCount))
                ->description(__('admin.pending_orders_description'))
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make(__('admin.products'), number_format($productsCount))
                ->description(__('admin.products_description'))
                ->descriptionIcon('heroicon-m-cube')
                ->color('info'),

            Stat::make(__('admin.low_stock_products'), number_format($lowStockProductsCount))
                ->description(__('admin.low_stock_products_description'))
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($lowStockProductsCount > 0 ? 'danger' : 'success'),

            Stat::make(__('admin.customers'), number_format($customersCount))
                ->description(__('admin.customers_description'))
                ->descriptionIcon('heroicon-m-users')
                ->color('gray'),

            Stat::make(__('admin.new_contact_messages'), number_format($newMessagesCount))
                ->description(__('admin.new_contact_messages_description'))
                ->descriptionIcon('heroicon-m-envelope')
                ->color($newMessagesCount > 0 ? 'warning' : 'success'),

            Stat::make(__('admin.newsletter_subscribers'), number_format($newsletterSubscribersCount))
                ->description(__('admin.newsletter_subscribers_description'))
                ->descriptionIcon('heroicon-m-envelope-open')
                ->color('success'),

            Stat::make(__('admin.content_pages'), number_format($pagesCount))
                ->description(__('admin.content_pages_description'))
                ->descriptionIcon('heroicon-m-document-text')
                ->color('info'),

            Stat::make(__('admin.published_blog_posts'), number_format($publishedBlogPostsCount))
                ->description(__('admin.published_blog_posts_description'))
                ->descriptionIcon('heroicon-m-newspaper')
                ->color('primary'),
        ];
    }
}